<?php

namespace App\Http\Controllers\Admin;

use App\Services\MonthlyServiceFeeHistoryService;
use App\Enums\OverpaymentDisposition;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\ClientPaymentIntent;
use App\Models\PaymentPlan;
use App\Services\PaymentReceiptService;
use App\Services\FinancialBalanceService;
use App\Services\PaymentService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentReceiptService $receipts,
        private readonly FinancialBalanceService $balances,
        private readonly MonthlyServiceFeeHistoryService $monthlyServiceFeeHistory,
    ) {}

    public function intentPreview(ClientPaymentIntent $intent): View
    {
        abort_unless($intent->status === 'announced', 409);
        $plan = $intent->paymentPlan()->firstOrFail();
        $data = [
            'client_payment_intent_id' => $intent->id,
            'received_date' => now()->toDateString(),
            'amount' => number_format($intent->amount / 100, 2, '.', ''),
            'payment_type' => 'regular',
            'payment_method' => $intent->method,
            'payer_client_id' => $intent->client_id,
            'external_reference' => $intent->client_reference,
            'overpayment_disposition' => $intent->overpayment_disposition,
            'client_note' => $intent->client_note,
            'idempotency_token' => (string) Str::uuid(),
        ];
        $preview = $this->payments->preview($plan, $intent->amount, 'regular', $intent->overpayment_disposition);

        return $this->form($plan, $preview, $data);
    }

    public function create(PaymentPlan $plan): View
    {
        return $this->form($plan, null, request()->only(['client_payment_intent_id','amount','payment_method','payer_client_id','external_reference','overpayment_disposition','client_note']));
    }

    public function preview(Request $request, PaymentPlan $plan): View
    {
        $data = $this->validatePayment($request, $plan, false);
        $preview = $this->payments->preview(
            $plan,
            Money::toCents($data['amount']),
            $data['payment_type'],
            $data['overpayment_disposition'] ?? null,
        );

        return $this->form($plan, $preview, $data);
    }

    public function store(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $data = $this->validatePayment($request, $plan, true);
        $post = fn (): Payment => $this->payments->post(
            $plan,
            $request->user(),
            Money::toCents($data['amount']),
            $data['payment_type'],
            PaymentMethod::from($data['payment_method']),
            $data['received_date'],
            isset($data['payer_client_id']) ? (int) $data['payer_client_id'] : null,
            $data['external_reference'] ?? null,
            isset($data['overpayment_disposition']) ? OverpaymentDisposition::from($data['overpayment_disposition']) : null,
            'payment:'.$data['idempotency_token'],
        );
        $payment = isset($data['client_payment_intent_id'])
            ? DB::transaction(function () use ($data, $plan, $request, $post): Payment {
                $intent = ClientPaymentIntent::query()->lockForUpdate()->findOrFail((int) $data['client_payment_intent_id']);
                abort_unless($intent->payment_plan_id === $plan->id && $intent->status === 'announced', 409);
                $payment = $post();
                $intent->update(['status' => 'received', 'payment_id' => $payment->id, 'received_at' => now()]);
                \App\Models\AdminNotice::query()
                    ->where('client_payment_intent_id', $intent->id)
                    ->whereNull('dismissed_at')
                    ->update(['dismissed_at' => now(), 'dismissed_by_user_id' => $request->user()->id]);

                return $payment;
            })
            : $post();

        $message = 'Payment posted successfully.';
        if ($request->boolean('email_receipt')) {
            $delivery = $this->receipts->send($payment, $request->user());
            $message .= ' Receipt emailed to '.$delivery->recipient_email.'.';
        }
        return redirect()->route('admin.payments.show', $payment)->with('success', $message);
    }

    public function show(Payment $payment): View
    {
        $payment->load(['financialTransaction.paymentPlan.memberships.client', 'financialTransaction.effects', 'allocations.invoice', 'allocations.invoiceItem', 'payer', 'emailDeliveries']);
        $reversal = $payment->financialTransaction->reversedBy()->first();

        return view('admin.payments.show', ['payment' => $payment, 'reversal' => $reversal]);
    }

    public function reverse(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $hadReceipt = $payment->emailDeliveries()->where('template_slug', 'payment-receipt')->where('status', 'sent')->exists();
        $this->payments->reverse($payment, $request->user(), $data['reason']);
        if ($hadReceipt) {
            $this->receipts->send($payment->fresh(), $request->user(), true);
        }

        return redirect()->route('admin.payments.show', $payment)->with('success', 'Payment canceled successfully. Its financial effects were reversed and the original record was preserved.');
    }

    /** @param array<string, mixed>|null $preview @param array<string, mixed> $input */
    private function form(PaymentPlan $plan, ?array $preview = null, array $input = []): View
    {
        $plan->load(['memberships' => fn ($query) => $query->whereNull('effective_to')->with('client')]);
        $primaryClient = $plan->memberships->firstWhere('role', 'primary')?->client;
        $primaryClientName = $primaryClient?->organization_name
            ?: trim(collect([$primaryClient?->first_name, $primaryClient?->middle_name, $primaryClient?->last_name])->filter()->join(' '))
            ?: 'No primary client';

        $invoiceBalance = $plan->invoices()->get()->sum(fn ($invoice) => max(0, $this->balances->invoiceBalance($invoice)));
        $uninvoicedFirstPaymentDue = $this->payments->uninvoicedDueFirstPaymentAmount($plan);

        $selectedDate = \Illuminate\Support\Carbon::parse(
            $input['received_date'] ?? now()->toDateString()
        );

        $monthlyServiceFeeSummary = $this->monthlyServiceFeeHistory
            ->summaryForMonth($plan, $selectedDate);

        return view('admin.payments.create', [
            'plan' => $plan,
            'primaryClientName' => $primaryClientName,
            'preview' => $preview,
            'input' => $input,
            'monthlyServiceFeeSummary' => $monthlyServiceFeeSummary,
            'contractBalance' => $this->balances->contractBalance($plan),
            'invoiceBalance' => $invoiceBalance,
            'uninvoicedFirstPaymentDue' => $uninvoicedFirstPaymentDue,
            'currentlyPayable' => $invoiceBalance + $uninvoicedFirstPaymentDue,
            'methods' => PaymentMethod::cases(),
            'idempotencyToken' => $input['idempotency_token'] ?? (string) Str::uuid(),
        ]);
    }

    /** @return array<string, mixed> */
    private function validatePayment(Request $request, PaymentPlan $plan, bool $posting): array
    {
        return $request->validate([
            'received_date' => ['required', 'date'],
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'payment_type' => ['required', Rule::in(['regular', 'principal_only'])],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'email_receipt' => ['nullable', 'boolean'],
            'payer_client_id' => ['nullable', 'integer', Rule::exists('payment_plan_clients', 'client_id')->where(fn ($query) => $query->where('payment_plan_id', $plan->id)->whereNull('effective_to'))],
            'external_reference' => ['nullable', 'string', 'max:150'],
            'overpayment_disposition' => [$posting ? 'nullable' : 'nullable', Rule::enum(OverpaymentDisposition::class)],
            'idempotency_token' => ['required', 'uuid'],
            'client_payment_intent_id' => ['nullable', 'integer', 'exists:client_payment_intents,id'],
            'client_note' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
