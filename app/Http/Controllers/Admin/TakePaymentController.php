<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientPaymentIntent;
use App\Models\PaymentPlan;
use App\Services\FinancialBalanceService;
use App\Services\PaymentMethodConfigurationService;
use App\Services\PaymentReceiptService;
use App\Services\PaymentService;
use App\Services\SquareCardPaymentService;
use App\Services\SquareProcessingFee;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TakePaymentController extends Controller
{
    public function __construct(
        private readonly PaymentMethodConfigurationService $methods,
        private readonly FinancialBalanceService $balances,
        private readonly PaymentService $payments,
        private readonly SquareProcessingFee $fees,
        private readonly SquareCardPaymentService $cards,
    ) {}

    public function create(Request $request)
    {
        $this->requireCardCheckout();
        $clientId = $request->integer('client_id');
        if (! $clientId) {
            return view('admin.take-payment.select', [
                'clients' => Client::query()->whereNull('archived_at')->whereHas('memberships', fn ($q) => $q
                    ->whereNull('effective_to')->whereDate('effective_from', '<=', today())
                    ->whereHas('paymentPlan', fn ($p) => $p->whereIn('status', ['active', 'paused'])))
                    ->with(['memberships.paymentPlan'])->orderBy('last_name')->orderBy('first_name')->get(),
            ]);
        }
        $client = Client::query()->whereNull('archived_at')->findOrFail($clientId);
        $plans = $this->clientPlans($client)->with('invoices')->get();
        abort_if($plans->isEmpty(), 422, 'This client has no active or paused plans.');
        $input = $request->old() ?: [];
        $selected = $plans->firstWhere('id', (int) ($input['payment_plan_id'] ?? $request->integer('plan'))) ?? $plans->first();
        $invoiceBalances = $plans->flatMap->invoices->mapWithKeys(fn ($invoice) => [$invoice->id => max(0, $this->balances->invoiceBalance($invoice))]);
        $planBalances = $plans->mapWithKeys(fn ($plan) => [$plan->id => $plan->invoices->sum(fn ($invoice) => in_array($invoice->status->value, ['issued', 'partially_paid'], true) ? $invoiceBalances[$invoice->id] : 0)]);
        $invoice = $selected->invoices->first(fn ($invoice) => $invoice->id === (int) ($input['invoice_id'] ?? $request->integer('invoice')) && in_array($invoice->status->value, ['issued', 'partially_paid'], true) && $invoiceBalances[$invoice->id] > 0);
        $input += ['payment_plan_id' => $selected->id, 'invoice_id' => $invoice?->id, 'amount' => number_format(($invoice ? $invoiceBalances[$invoice->id] : $planBalances[$selected->id]) / 100, 2, '.', ''), 'method' => 'card'];
        $key = (string) Str::uuid();
        $request->session()->put('admin_card.'.$key, ['client_id' => $client->id, 'user_id' => $request->user()->id]);
        return view('portal.make-payment.simple', [
            'adminPayment' => true, 'checkoutKey' => $key, 'paymentClient' => $client,
            'plans' => $plans, 'selectedPlan' => $selected, 'planBalances' => $planBalances,
            'invoiceBalances' => $invoiceBalances, 'input' => $input,
            'methods' => [$this->methods->method('card')], 'general' => array_replace($this->methods->general(), ['allow_custom_amount' => true]),
            'square' => $this->fees->clientConfiguration(), 'squarePostalCode' => null,
            'activeStates' => [], 'pendingNotifications' => collect(), 'secureAccess' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['checkout_key' => ['required', 'uuid']]);
        $sessionKey = 'admin_card.'.$data['checkout_key'];
        $attempt = $request->session()->get($sessionKey);
        abort_unless(is_array($attempt) && $attempt['user_id'] === $request->user()->id, 419);
        // Repeated submissions return the existing result, never create another charge.
        if (isset($attempt['intent_id'])) {
            return redirect()->route('admin.take-payment.show', ClientPaymentIntent::findOrFail($attempt['intent_id']));
        }
        $this->requireCardCheckout();
        $data += $request->validate([
            'client_id' => ['required', 'integer', Rule::in([$attempt['client_id']])],
            'payment_plan_id' => ['required', 'integer'], 'invoice_id' => ['nullable', 'integer'],
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'overpayment_disposition' => ['nullable', Rule::in(['principal', 'next_invoice_credit'])],
            'square_source_id' => ['required', 'string', 'max:255'],
            'square_card_type' => ['required', Rule::in(['CREDIT', 'DEBIT', 'PREPAID', 'UNKNOWN'])],
        ]);
        $client = Client::query()->whereNull('archived_at')->findOrFail($data['client_id']);
        $plan = $this->clientPlans($client)->findOrFail($data['payment_plan_id']);
        $invoiceId = $data['invoice_id'] ?? null;
        if ($invoiceId) {
            $invoice = $plan->invoices()->whereIn('status', ['issued', 'partially_paid'])->findOrFail($invoiceId);
            abort_unless($this->balances->invoiceBalance($invoice) > 0, 422, 'This invoice has no remaining balance.');
        }
        $base = Money::toCents($data['amount']);
        $this->payments->preview($plan, $base, 'regular', $data['overpayment_disposition'] ?? null, invoiceId: $invoiceId);
        $fee = $this->fees->calculate($base, $data['square_card_type']);
        $intent = DB::transaction(function () use ($request, $client, $plan, $invoiceId, $base, $fee, $data) {
            $intent = ClientPaymentIntent::create([
                'payment_plan_id' => $plan->id, 'invoice_id' => $invoiceId, 'client_id' => $client->id,
                'method' => 'card', 'amount' => $base + $fee, 'base_amount' => $base,
                'processing_fee_amount' => $fee, 'card_type' => $data['square_card_type'],
                'payment_type' => 'regular', 'overpayment_disposition' => $data['overpayment_disposition'] ?? null,
                'provider' => 'square', 'status' => 'announced', 'expires_at' => now()->addDays($this->methods->general()['intent_expiry_days']),
            ]);
            AuditLog::create([
                'actor_type' => 'administrator', 'actor_user_id' => $request->user()->id,
                'event' => 'payment.admin_phone_initiated', 'auditable_type' => ClientPaymentIntent::class, 'auditable_id' => $intent->id,
                'after_values' => ['client_id' => $client->id, 'payment_plan_id' => $plan->id, 'invoice_id' => $invoiceId, 'amount' => $base + $fee],
            ]);
            return $intent;
        });
        $request->session()->put($sessionKey, $attempt + ['intent_id' => $intent->id]);
        // Persist the attempt before contacting Square, including if the request is interrupted.
        $request->session()->save();
        try {
            $this->cards->pay($intent, $data['square_source_id'], $request->user());
        } catch (ValidationException $exception) {
            return redirect()->route('admin.take-payment.show', $intent)->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            report($exception);
            $intent->refresh();
            if ($intent->status !== 'received') $intent->update(['status' => 'review_required']);
        }
        return redirect()->route('admin.take-payment.show', $intent);
    }

    public function show(ClientPaymentIntent $intent)
    {
        $this->requireAdminIntent($intent);
        return view('admin.take-payment.show', ['intent' => $intent->load(['client', 'paymentPlan', 'invoice', 'payment.emailDeliveries'])]);
    }

    public function receipt(Request $request, ClientPaymentIntent $intent, PaymentReceiptService $receipts)
    {
        $this->requireAdminIntent($intent);
        abort_unless($intent->status === 'received' && $intent->payment_id, 422);
        $data = $request->validate(['recipients' => ['required', 'string', 'max:1500']]);
        $emails = array_values(array_unique(array_filter(array_map(fn ($email) => strtolower(trim($email)), preg_split('/[,;\r\n]+/', $data['recipients'])))));
        validator(['emails' => $emails], ['emails' => ['required', 'array', 'min:1', 'max:5'], 'emails.*' => ['required', 'email', 'max:254']])->validate();
        $sent = $failed = [];
        foreach ($emails as $email) {
            try {
                $receipts->send($intent->payment, $request->user(), recipientEmail: $email);
                $sent[] = $email;
            } catch (ValidationException $exception) {
                $failed[] = $email;
            }
        }
        $redirect = back();
        if ($sent) $redirect->with('success', 'Receipt emailed to '.implode(', ', $sent).'.');
        if ($failed) $redirect->with('warning', 'Receipt could not be sent to '.implode(', ', $failed).'. Review the delivery history below before retrying. The payment is unchanged.')->withInput(['recipients' => implode(', ', $failed)]);
        return $redirect;
    }

    private function clientPlans(Client $client)
    {
        return PaymentPlan::query()->whereIn('status', ['active', 'paused'])->whereHas('memberships', fn ($q) => $q
            ->where('client_id', $client->id)->whereNull('effective_to')->whereDate('effective_from', '<=', today()));
    }

    private function requireCardCheckout(): void
    {
        abort_unless($this->methods->method('card')['enabled'] && $this->methods->general()['card_provider'] === 'square'
            && $this->fees->clientConfiguration()['experience'] === 'landpay', 422, 'Take payment requires the existing LandPay Square card checkout to be enabled.');
    }

    private function requireAdminIntent(ClientPaymentIntent $intent): void
    {
        abort_unless(AuditLog::query()->where('event', 'payment.admin_phone_initiated')
            ->where('auditable_type', ClientPaymentIntent::class)->where('auditable_id', $intent->id)->exists(), 404);
    }
}
