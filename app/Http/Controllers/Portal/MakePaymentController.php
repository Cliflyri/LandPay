<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\ClientPaymentIntent;
use App\Models\PaymentPlan;
use App\Services\FinancialBalanceService;
use App\Services\HostedPaymentService;
use App\Services\PaymentMethodConfigurationService;
use App\Services\PaymentService;
use App\Services\SquareCardPaymentService;
use App\Services\SquareProcessingFee;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MakePaymentController extends Controller
{
    public function __construct(
        private readonly PaymentMethodConfigurationService $methods,
        private readonly PaymentService $payments,
        private readonly FinancialBalanceService $balances,
        private readonly HostedPaymentService $hosted,
        private readonly SquareProcessingFee $squareFees,
        private readonly SquareCardPaymentService $squareCards,
    ) {}

    public function create(Request $request)
    {
        if (($link = $this->secureLink($request)) && $this->balances->invoiceBalance($link->invoice) <= 0) {
            return redirect()->route('secure-invoice.show')->with('status', 'This invoice has been paid.');
        }
        return $this->form($request);
    }

    public function preview(Request $request): View
    {
        $data = $this->validateInput($request);
        $plan = $this->plan($request, (int) $data['payment_plan_id']);
        $invoiceId = $this->secureLink($request)?->invoice_id;
        $this->validateSecureAmount($request, Money::toCents($data['amount']));
        $preview = $this->payments->preview($plan, Money::toCents($data['amount']), 'regular', $data['overpayment_disposition'] ?? null, invoiceId: $invoiceId);
        return $this->form($request, $preview, $data);
    }

    public function store(Request $request)
    {
        $data = $this->validateInput($request);
        [$account, $client] = $this->identity($request);
        $plan = $this->plan($request, (int) $data['payment_plan_id']);
        $link = $this->secureLink($request);
        $this->validateSecureAmount($request, Money::toCents($data['amount']));
        $method = $this->methods->method($data['method']);
        abort_unless($method['enabled'], 422);
        $this->payments->preview($plan, Money::toCents($data['amount']), 'regular', $data['overpayment_disposition'] ?? null, invoiceId: $link?->invoice_id);

        if ($data['method'] === 'card') {
            $baseAmount = Money::toCents($data['amount']);
            $square = $this->squareFees->clientConfiguration();
            $landpay = $this->methods->general()['card_provider'] === 'square' && $square['experience'] === 'landpay';
            if ($landpay) {
                $request->validate([
                    'square_source_id' => ['required', 'string', 'max:255'],
                    'square_card_type' => ['required', Rule::in(['CREDIT', 'DEBIT', 'PREPAID', 'UNKNOWN'])],
                ]);
                $fee = $this->squareFees->calculate($baseAmount, $data['square_card_type']);
            } else {
                $fee = 0;
            }
            $intent = ClientPaymentIntent::query()->create([
                'payment_plan_id' => $plan->id, 'invoice_id' => $link?->invoice_id,
                'client_id' => $client->id, 'portal_account_id' => $account?->id,
                'method' => 'card', 'amount' => $baseAmount + $fee, 'base_amount' => $baseAmount,
                'processing_fee_amount' => $fee, 'card_type' => $data['square_card_type'] ?? null,
                'payment_type' => 'regular', 'overpayment_disposition' => $data['overpayment_disposition'] ?? null,
                'client_note' => $data['client_note'] ?? null, 'status' => 'announced',
                'provider' => $landpay ? 'square' : null,
                'expires_at' => now()->addDays($this->methods->general()['intent_expiry_days']),
            ]);
            if ($landpay) {
                $intent = $this->squareCards->pay($intent, $data['square_source_id']);
                return redirect()->route($link ? 'secure-invoice.payment.show' : 'portal.make-payment.show', $intent);
            }
            $intent = $this->hosted->create($intent);
            return redirect()->away($intent->checkout_url);
        }

        $intent = DB::transaction(function () use ($account, $client, $plan, $link, $data, $method): ClientPaymentIntent {
            PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $created = ClientPaymentIntent::query()->create([
                'payment_plan_id' => $plan->id, 'invoice_id' => $link?->invoice_id,
                'client_id' => $client->id, 'portal_account_id' => $account?->id,
                'method' => $data['method'], 'amount' => Money::toCents($data['amount']),
                'payment_type' => 'regular', 'overpayment_disposition' => $data['overpayment_disposition'] ?? null,
                'client_note' => $data['client_note'] ?? null, 'status' => 'announced',
                'expires_at' => now()->addDays($this->methods->general()['intent_expiry_days']),
            ]);
            $name = $client->organization_name ?: trim($client->first_name.' '.$client->last_name);
            AdminNotice::query()->create([
                'type' => 'client_payment_announced', 'client_id' => $client->id,
                'client_payment_intent_id' => $created->id, 'title' => 'Payment intended',
                'message' => $name.' intends to pay '.Money::format($created->amount).' by '.$method['name'].' for plan '.$plan->plan_number.'.'.(filled($created->client_note) ? ' Client note: '.$created->client_note : ''),
            ]);
            return $created;
        }, 3);

        if ($request->expectsJson()) {
            return response()->json([
                'intent_id' => $intent->uuid,
                'message' => 'Admin notified of '.Money::format($intent->amount).' '.$method['name'].' payment.',
                'cancel_url' => route($link ? 'secure-invoice.payment.cancel' : 'portal.make-payment.cancel', $intent),
            ]);
        }
        return redirect()->route($link ? 'secure-invoice.payment.create' : 'portal.make-payment.create', $link ? [] : ['plan' => $intent->payment_plan_id])
            ->with('success', 'Administrator notified of your intended payment.');
    }

    public function show(Request $request, ClientPaymentIntent $intent): View
    {
        $this->authorizeIntent($request, $intent);
        $intent->load('paymentPlan');
        return view('portal.make-payment.confirmation', [
            'intent' => $intent, 'method' => $this->methods->method($intent->method),
            'secureAccess' => (bool) $this->secureLink($request),
        ]);
    }

    public function cancel(Request $request, ClientPaymentIntent $intent)
    {
        $this->authorizeIntent($request, $intent);
        DB::transaction(function () use ($intent): void {
            $locked = ClientPaymentIntent::query()->lockForUpdate()->findOrFail($intent->id);
            abort_unless($locked->status === 'announced', 409);
            $locked->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            AdminNotice::query()->where('client_payment_intent_id', $locked->id)->whereNull('dismissed_at')->update(['dismissed_at' => now()]);
        });
        if ($request->expectsJson()) return response()->json(['cancelled' => true]);
        $secure = (bool) $this->secureLink($request);
        return redirect()->route($secure ? 'secure-invoice.payment.create' : 'portal.make-payment.create', $secure ? [] : [
            'plan' => $intent->payment_plan_id, 'amount' => number_format($intent->amount / 100, 2, '.', ''), 'method' => $intent->method,
        ])->with('success', 'Payment notification cancelled.');
    }

    private function form(Request $request, ?array $preview = null, array $input = []): View
    {
        $oldInput = $request->old();
        if (is_array($oldInput)) $input = array_replace($oldInput, $input);
        [$account, $client] = $this->identity($request);
        $link = $this->secureLink($request);
        $storedPostalCode = trim((string) $client->postal_code);
        $postalDigits = preg_replace('/\D/', '', $storedPostalCode);
        $squarePostalCode = preg_match('/^\d{5}(?:[- ]?\d{4})?$/', $storedPostalCode) && in_array(strlen($postalDigits), [5, 9], true)
            ? (strlen($postalDigits) === 9 ? substr($postalDigits, 0, 5).'-'.substr($postalDigits, 5) : $postalDigits) : null;

        $plans = $link
            ? PaymentPlan::query()->whereKey($link->invoice->payment_plan_id)->whereIn('status', ['active', 'paused'])->with('invoices')->get()
            : PaymentPlan::query()->whereIn('id', $account->activePlanIds())->whereIn('status', ['active', 'paused'])->with('invoices')->get();
        abort_if($plans->isEmpty(), 403);
        $methods = $this->methods->enabled();
        $configuredMethods = collect($methods)->keyBy('key');
        $pendingNotifications = $link ? collect() : ClientPaymentIntent::query()
            ->where('client_id', $client->id)->whereIn('payment_plan_id', $plans->pluck('id'))
            ->where('status', 'announced')->where('method', '!=', 'card')
            ->whereHas('adminNotice', fn ($notice) => $notice->whereNull('dismissed_at'))->latest('id')->get()
            ->map(function (ClientPaymentIntent $intent) use ($configuredMethods) {
                $intent->setAttribute('method_name', $configuredMethods->get($intent->method)['name'] ?? str($intent->method)->replace('_', ' ')->title());
                return $intent;
            });

        $requestedPlan = (int) ($input['payment_plan_id'] ?? $request->integer('plan'));
        $selected = $plans->firstWhere('id', $requestedPlan) ?? $plans->first();
        $balances = $link
            ? collect([$selected->id => max(0, $this->balances->invoiceBalance($link->invoice))])
            : $plans->mapWithKeys(fn (PaymentPlan $plan) => [$plan->id => (int) $plan->invoices->sum(fn ($invoice) => max(0, $this->balances->invoiceBalance($invoice)))]);
        $requestedAmount = $request->query('amount');
        $amount = is_string($requestedAmount) && preg_match('/^(?:0|[1-9]\d*)(?:\.\d{1,2})?$/', $requestedAmount) && Money::toCents($requestedAmount) > 0
            ? number_format(Money::toCents($requestedAmount) / 100, 2, '.', '')
            : number_format($balances[$selected->id] / 100, 2, '.', '');
        $requestedMethod = $request->query('method');
        $methodKeys = collect($methods)->pluck('key');
        $input += ['payment_plan_id' => $selected->id, 'amount' => $amount, 'method' => $methodKeys->contains($requestedMethod) ? $requestedMethod : ($methods[0]['key'] ?? null)];

        return view('portal.make-payment.simple', [
            'plans' => $plans, 'planBalances' => $balances, 'selectedPlan' => $selected,
            'methods' => $methods, 'general' => $this->methods->general(),
            'square' => $this->squareFees->clientConfiguration(), 'squarePostalCode' => $squarePostalCode,
            'activeStates' => [], 'pendingNotifications' => $pendingNotifications, 'input' => $input,
            'secureAccess' => (bool) $link,
        ]);
    }

    private function identity(Request $request): array
    {
        if ($link = $this->secureLink($request)) return [$link->client->portalAccount, $link->client];
        $account = $request->user('client');
        return [$account, $account->client];
    }

    private function plan(Request $request, int $id): PaymentPlan
    {
        if ($link = $this->secureLink($request)) abort_unless($id === $link->invoice->payment_plan_id, 404);
        else abort_unless(in_array($id, $request->user('client')->activePlanIds(), true), 404);
        return PaymentPlan::findOrFail($id);
    }

    private function secureLink(Request $request)
    {
        return $request->attributes->get('secureInvoiceLink');
    }

    private function validateSecureAmount(Request $request, int $amount): void
    {
        $link = $this->secureLink($request);
        if (! $link) return;
        $balance = max(0, $this->balances->invoiceBalance($link->invoice));
        if ($balance < 1) throw ValidationException::withMessages(['amount' => 'This invoice has already been paid.']);
        if ($amount > $balance) throw ValidationException::withMessages(['amount' => 'Payment cannot exceed this invoice’s current balance.']);
    }

    private function authorizeIntent(Request $request, ClientPaymentIntent $intent): void
    {
        if ($link = $this->secureLink($request)) {
            abort_unless($intent->client_id === $link->client_id && $intent->invoice_id === $link->invoice_id, 404);
            return;
        }
        abort_unless($intent->client_id === $request->user('client')->client_id, 404);
    }

    private function validateInput(Request $request): array
    {
        return $request->validate([
            'payment_plan_id' => ['required', 'integer'], 'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'method' => ['required', Rule::in(PaymentMethodConfigurationService::METHODS)],
            'overpayment_disposition' => ['nullable', Rule::in(['principal', 'next_invoice_credit'])],
            'client_note' => ['nullable', 'string', 'max:1000'],
            'square_source_id' => ['nullable', 'string', 'max:255'], 'square_card_type' => ['nullable', 'string', 'max:20'],
        ]);
    }
}
