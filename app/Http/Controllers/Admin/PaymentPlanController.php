<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingDefault;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\FinancialTransaction;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use App\Services\AccountCreditApplicationService;
use App\Services\AutomaticInvoiceService;
use App\Services\ContractAmountAmendmentService;
use App\Services\ContractOpeningService;
use App\Services\CurrentPayoffService;
use App\Services\FinancialBalanceService;
use App\Services\FirstPaymentInvoiceService;
use App\Services\OpeningPrincipalCreditService;
use App\Services\PaymentPlanMembershipService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentPlanController extends Controller
{
    public function __construct(
        private readonly PaymentPlanMembershipService $memberships,
        private readonly ContractOpeningService $opening,
        private readonly FinancialBalanceService $balances,
        private readonly CurrentPayoffService $payoffs,
        private readonly FirstPaymentInvoiceService $firstPaymentInvoices,
        private readonly ContractAmountAmendmentService $contractAmounts,
        private readonly OpeningPrincipalCreditService $openingPrincipalCredit,
        private readonly AutomaticInvoiceService $automaticInvoices,
        private readonly AccountCreditApplicationService $accountCredits,
    ) {}

public function index(Request $request): View
{
    $planStatus = PaymentPlan::normalizeAdminStatusFilter($request->string('status')->value());
    $planSearch = trim($request->string('search')->value());

    $plans = PaymentPlan::query()
        ->forAdminListing($planStatus, $planSearch)
        ->with([
            'memberships.client.portalAccount',
            'currentBillingTerms',
            'invoices.items',
        ])
        ->latest()
        ->paginate(25)
        ->withQueryString();

    $plans->getCollection()->each(function (PaymentPlan $plan): void {
        $outstandingInvoiceBalance = $plan->invoices->sum(
            fn ($invoice) => max(0, $this->balances->invoiceBalance($invoice))
        );

        $plan->setAttribute(
            'ready_to_close',
            in_array($plan->status, ['active', 'paused'], true)
                && $this->balances->contractBalance($plan) <= 0
                && $outstandingInvoiceBalance <= 0
        );
    });
    $plans->setCollection($plans->getCollection()->sortByDesc(
        fn (PaymentPlan $plan) => ((int) $plan->ready_to_close * 2) + (int) $plan->accelerated_testing_mode
    )->values());

    return view('admin.plans.index', compact('plans', 'planStatus', 'planSearch'));
}

    public function create(): View
    {
        return view('admin.plans.create', [
            'clients' => Client::query()->whereNull('archived_at')->orderBy('last_name')->orderBy('first_name')->get(),
            'billingDefaults' => BillingDefault::query()->latest('id')->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_number' => ['required', 'string', 'max:40', 'unique:payment_plans,plan_number'],
            'title' => ['required', 'string', 'max:180'],
            'asset_description' => ['nullable', 'string'],
            'primary_client_id' => ['required', 'exists:clients,id'],
            'co_client_ids' => ['nullable', 'array'],
            'co_client_ids.*' => ['integer', 'distinct', 'different:primary_client_id', 'exists:clients,id'],
            'purchase_price' => ['required', 'decimal:0,2', 'gt:0'],
            'documentation_fee_standard' => ['required', 'decimal:0,2'],
            'documentation_fee_waived' => ['required', 'decimal:0,2'],
            'documentation_fee_waiver_reason' => ['nullable', 'string', 'max:500'],
            'previous_principal_paid' => ['nullable', 'decimal:0,2', 'min:0'],
            'first_payment_amount' => ['nullable', 'required_with:first_payment_due_date,create_first_payment_invoice', 'decimal:0,2', 'gt:0'],
            'first_payment_due_date' => ['nullable', 'required_with:first_payment_amount,create_first_payment_invoice', 'date', 'after_or_equal:contract_start_date'],
            'create_first_payment_invoice' => ['sometimes', 'accepted'],
            'scheduled_payment_amount' => ['required', 'decimal:0,2', 'gt:0'],
            'monthly_service_fee' => ['required', 'decimal:0,2'],
            'invoice_day' => ['required', 'integer', 'between:1,31'],
            'due_days_after_issue' => ['required', 'integer', 'between:0,60'],
            'grace_days' => ['required', 'integer', 'between:0,60'],
            'contract_start_date' => ['required', 'date'],
            'stage_one_fee_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'stage_one_fee_value' => ['required', 'numeric', 'min:0'],
            'stage_one_minimum_amount' => ['nullable', 'required_if:stage_one_fee_type,percentage', 'decimal:0,2'],
            'stage_two_enabled' => ['nullable', 'boolean'],
            'stage_two_days_late' => ['nullable', 'required_if:stage_two_enabled,1', 'integer', 'between:1,365'],
            'stage_two_fee_type' => ['nullable', 'required_if:stage_two_enabled,1', Rule::in(['fixed', 'percentage'])],
            'stage_two_fee_value' => ['nullable', 'required_if:stage_two_enabled,1', 'numeric', 'min:0'],
            'stage_two_minimum_amount' => ['nullable', 'required_if:stage_two_fee_type,percentage', 'decimal:0,2'],
            'default_eligibility_days' => ['required', 'integer', 'between:1,730'],
            'contact_risk_acknowledged' => ['accepted'],
        ]);

        $docStandard = Money::toCents($data['documentation_fee_standard']);
        $docWaived = Money::toCents($data['documentation_fee_waived']);
        if ($docWaived > $docStandard) {
            throw ValidationException::withMessages(['documentation_fee_waived' => 'The waived amount cannot exceed the documentation fee.']);
        }
        if ($docWaived > 0 && blank($data['documentation_fee_waiver_reason'] ?? null)) {
            throw ValidationException::withMessages(['documentation_fee_waiver_reason' => 'Enter a reason for the documentation-fee waiver.']);
        }

        $previousPaid = filled($data['previous_principal_paid'] ?? null) ? Money::toCents($data['previous_principal_paid']) : 0;
        if ($previousPaid > Money::toCents($data['purchase_price']) + $docStandard - $docWaived) {
            throw ValidationException::withMessages(['previous_principal_paid' => 'This adjustment cannot exceed the initial contract balance or create a customer credit.']);
        }

        $stageOneDaysLate = (int) $data['grace_days'] + 1;
        if (($data['stage_two_enabled'] ?? false) && (int) $data['stage_two_days_late'] <= $stageOneDaysLate) {
            throw ValidationException::withMessages(['stage_two_days_late' => 'Stage two must occur after the stage-one late fee.']);
        }

        $actor = $request->user();
        $plan = DB::transaction(function () use ($data, $actor, $docStandard, $docWaived, $previousPaid, $stageOneDaysLate): PaymentPlan {
            $purchase = Money::toCents($data['purchase_price']);
            $scheduled = Money::toCents($data['scheduled_payment_amount']);
            $monthlyFee = Money::toCents($data['monthly_service_fee']);
            $firstPayment = filled($data['first_payment_amount'] ?? null) ? Money::toCents($data['first_payment_amount']) : null;

            $plan = PaymentPlan::query()->create([
                'plan_number' => trim($data['plan_number']),
                'apn' => trim($data['plan_number']),
                'title' => $data['title'],
                'asset_description' => $data['asset_description'] ?? null,
                'original_purchase_balance' => 1,
                'first_payment_amount' => $firstPayment,
                'customary_monthly_payment' => $scheduled,
                'monthly_service_fee' => $monthlyFee,
                'monthly_due_day' => $data['invoice_day'],
                'first_due_date' => $data['first_payment_due_date'] ?? null,
                'plan_start_date' => $data['contract_start_date'],
                'grace_period_days' => $data['grace_days'],
                'status' => 'draft',
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $this->memberships->add($plan, Client::findOrFail($data['primary_client_id']), $actor, 'primary', $data['contract_start_date'], contactRiskAcknowledgmentMethod: 'admin_contract_acceptance');
            foreach ($data['co_client_ids'] ?? [] as $clientId) {
                $this->memberships->add($plan, Client::findOrFail($clientId), $actor, 'co_client', $data['contract_start_date'], contactRiskAcknowledgmentMethod: 'admin_contract_acceptance');
            }

            PaymentPlanBillingTerm::query()->create([
                'payment_plan_id' => $plan->id,
                'frequency' => 'monthly',
                'invoice_day' => $data['invoice_day'],
                'due_days_after_issue' => $data['due_days_after_issue'],
                'grace_days' => $data['grace_days'],
                'scheduled_payment_amount' => $scheduled,
                'monthly_service_fee' => $monthlyFee,
                'stage_one_enabled' => true,
                'stage_one_fee_type' => $data['stage_one_fee_type'],
                'stage_one_fixed_amount' => $data['stage_one_fee_type'] === 'fixed' ? Money::toCents((string) $data['stage_one_fee_value']) : null,
                'stage_one_percentage_rate' => $data['stage_one_fee_type'] === 'percentage' ? $data['stage_one_fee_value'] : null,
                'stage_one_minimum_amount' => $data['stage_one_fee_type'] === 'percentage' ? Money::toCents($data['stage_one_minimum_amount']) : 0,
                'stage_one_days_late' => $stageOneDaysLate,
                'stage_two_enabled' => (bool) ($data['stage_two_enabled'] ?? false),
                'stage_two_fee_type' => ($data['stage_two_enabled'] ?? false) ? $data['stage_two_fee_type'] : null,
                'stage_two_fixed_amount' => ($data['stage_two_enabled'] ?? false) && $data['stage_two_fee_type'] === 'fixed' ? Money::toCents((string) $data['stage_two_fee_value']) : null,
                'stage_two_percentage_rate' => ($data['stage_two_enabled'] ?? false) && $data['stage_two_fee_type'] === 'percentage' ? $data['stage_two_fee_value'] : null,
                'stage_two_minimum_amount' => ($data['stage_two_enabled'] ?? false) && $data['stage_two_fee_type'] === 'percentage' ? Money::toCents($data['stage_two_minimum_amount']) : 0,
                'stage_two_days_late' => ($data['stage_two_enabled'] ?? false) ? $data['stage_two_days_late'] : null,
                'default_eligibility_days' => $data['default_eligibility_days'],
                'effective_from' => $data['contract_start_date'],
                'created_by_user_id' => $actor->id,
            ]);

            $this->opening->open($plan, $actor, $purchase, $docStandard, $docWaived, $data['contract_start_date'], $data['documentation_fee_waiver_reason'] ?? null);
            $this->openingPrincipalCredit->post($plan, $actor, $previousPaid, $data['contract_start_date']);
            $plan->update(['status' => 'active', 'activated_at' => now()]);

            if ($data['create_first_payment_invoice'] ?? false) {
                $this->firstPaymentInvoices->issue($plan, $actor, $firstPayment, $data['contract_start_date'], $data['first_payment_due_date']);
            }

            return $plan;
        }, 3);

        return redirect()->route('admin.plans.show', $plan)->with('success', 'Payment plan activated successfully.');
    }

    public function show(PaymentPlan $plan): View
    {
        $plan->load(['memberships.client', 'currentBillingTerms', 'billingTerms.createdBy', 'invoices.items']);
        $contractBalance = $this->balances->contractBalance($plan);
        $openInvoiceBalance = (int) $plan->invoices->sum(fn ($invoice) => max(0, $this->balances->invoiceBalance($invoice)));
        $monthlyPrincipal = (int) ($plan->currentBillingTerms?->scheduled_payment_amount ?? $plan->customary_monthly_payment ?? 0);
        $nextInvoiceDate = $this->automaticInvoices->nextDate($plan);
        $estimatedPayoff = match (true) {
            $contractBalance <= 0 => 'Paid off',
            $monthlyPrincipal <= 0 || $nextInvoiceDate === null => 'Not available',
            default => $nextInvoiceDate->copy()
                ->addMonthsNoOverflow(max(0, (int) ceil($contractBalance / $monthlyPrincipal) - 1))
                ->format('F Y'),
        };

        $payments = Payment::query()
            ->with(['financialTransaction.reversedBy', 'payer', 'allocations.invoice'])
            ->whereHas('financialTransaction', fn ($query) => $query->where('payment_plan_id', $plan->id))
            ->newestFirst()
            ->get();

        $runningContractBalance = 0;
        $ledgerRows = collect();
        FinancialTransaction::query()
            ->with(['invoice', 'effects', 'payment.allocations.invoiceItem', 'payment.allocations.invoice', 'reversalOf.payment.allocations.invoiceItem', 'reversalOf.payment.allocations.invoice'])
            ->where('payment_plan_id', $plan->id)
            ->orderBy('effective_date')->orderBy('id')->get()
            ->each(function (FinancialTransaction $transaction) use (&$runningContractBalance, $ledgerRows): void {
                $purchaseDelta = (int) $transaction->effects
                    ->filter(fn ($effect) => $effect->effect_type->value === 'purchase_balance')
                    ->sum('amount_delta');
                $creditDelta = (int) $transaction->effects
                    ->filter(fn ($effect) => $effect->effect_type->value === 'client_credit')
                    ->sum('amount_delta');
                $runningContractBalance += $purchaseDelta;

                if (in_array($transaction->type->value, ['opening_purchase_balance', 'opening_principal_credit'], true)) return;

                $payment = $transaction->payment ?? $transaction->reversalOf?->payment;
                $allocations = $payment?->allocations ?? collect();
                $sign = $transaction->type->value === 'reversal' ? -1 : 1;
                $effectFee = in_array($transaction->type->value, ['payment', 'credit_application', 'reversal', 'refund'], true)
                    ? -(int) $transaction->effects
                        ->filter(fn ($effect) => $effect->effect_type->value === 'invoice_due'
                            && $effect->component->value !== 'scheduled_purchase_payment')
                        ->sum('amount_delta')
                    : 0;
                $directFee = $sign * (int) $allocations
                    ->filter(fn ($allocation) => $allocation->allocation_type->value === 'service_fee')
                    ->sum('amount');
                $fee = $effectFee + $directFee;

                if ($purchaseDelta === 0 && $creditDelta === 0 && $fee === 0
                    && ! in_array($transaction->type->value, ['payment', 'reversal', 'refund'], true)) return;

                $invoices = collect([$transaction->invoice])
                    ->merge($allocations->whereNotNull('invoice_id')->pluck('invoice'))
                    ->filter()->unique('id')->values();
                $amount = match ($transaction->type->value) {
                    'payment' => (int) $transaction->gross_amount,
                    'reversal' => -(int) ($payment?->gross_amount ?? $transaction->gross_amount),
                    default => 0,
                };

                $ledgerRows->push([
                    'date' => $transaction->effective_date,
                    'description' => $transaction->description,
                    'payment' => $payment,
                    'invoices' => $invoices,
                    'amount' => $amount,
                    'fee' => $fee,
                    'principal' => -$purchaseDelta,
                    'credit' => $creditDelta,
                    'balance' => $runningContractBalance,
                    'reversal' => $transaction->type->value === 'reversal',
                ]);
            });

        return view('admin.plans.show', [
            'plan' => $plan,
            'contractBalance' => $contractBalance,
            'estimatedPayoff' => $estimatedPayoff,
            'currentPayoff' => $this->payoffs->amount($plan),
            'paidInValue' => $this->balances->administratorPaidInValue($plan),
            'clientCredit' => max(0, $this->balances->clientCredit($plan)),
            'openInvoiceBalance' => $openInvoiceBalance,
            'previousPaid' => $this->openingPrincipalCredit->amount($plan),
            'payments' => $payments,
            'ledgerRows' => $ledgerRows,
            'ledgerPayments' => (int) $ledgerRows->sum('amount'),
            'ledgerFees' => (int) $ledgerRows->sum('fee'),
            'ledgerPrincipal' => (int) $ledgerRows->sum('principal'),
            'ledgerCredit' => (int) $ledgerRows->sum('credit'),
        ]);
    }

    public function applyAccountCredit(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $applied = $this->accountCredits->applyToOpenInvoices($plan, $request->user());
        return back()->with('success', $applied > 0 ? Money::format($applied).' account credit applied to open invoices.' : 'No account credit could be applied.');
    }
    public function edit(PaymentPlan $plan): View
    {
        $plan->load(['currentBillingTerms', 'memberships.client']);

        return view('admin.plans.edit', ['plan' => $plan, 'terms' => $plan->currentBillingTerms, 'previousPaid' => $this->openingPrincipalCredit->amount($plan), 'contractBalance' => $this->balances->contractBalance($plan)]);
    }

    public function update(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $terms = $plan->currentBillingTerms()->firstOrFail();
        $data = $request->validate([
            'plan_number' => ['required', 'string', 'max:40', Rule::unique('payment_plans')->ignore($plan->id)],
            'title' => ['required', 'string', 'max:180'],
            'asset_description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'purchase_price' => ['required', 'decimal:0,2', 'gt:0'],
            'documentation_fee_standard' => ['required', 'decimal:0,2'],
            'documentation_fee_waived' => ['required', 'decimal:0,2'],
            'documentation_fee_waiver_reason' => ['nullable', 'string', 'max:500'],
            'previous_principal_paid' => ['nullable', 'decimal:0,2', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'active', 'paused', 'terminated', 'closed'])],
            'contract_start_date' => ['required', 'date'],
            'first_payment_amount' => ['nullable', 'decimal:0,2', 'gt:0'],
            'first_payment_due_date' => ['nullable', 'date'],
            'scheduled_payment_amount' => ['required', 'decimal:0,2', 'gt:0'],
            'monthly_service_fee' => ['required', 'decimal:0,2'],
            'invoice_day' => ['required', 'integer', 'between:1,31'],
            'due_days_after_issue' => ['required', 'integer', 'between:0,60'],
            'grace_days' => ['required', 'integer', 'between:0,60'],
            'stage_one_fee_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'stage_one_fee_value' => ['required', 'numeric', 'min:0'],
            'stage_one_minimum_amount' => ['nullable', 'required_if:stage_one_fee_type,percentage', 'decimal:0,2'],
            'scheduled_invoice_email_enabled' => ['nullable', 'boolean'],
            'automated_reminders_enabled' => ['nullable', 'boolean'],
            'automatic_invoice_email_enabled' => ['nullable', 'boolean'],
            'accelerated_testing_mode' => ['nullable', 'boolean'],
            'stage_two_enabled' => ['nullable', 'boolean'],
            'stage_two_days_late' => ['nullable', 'required_if:stage_two_enabled,1', 'integer', 'between:1,365'],
            'stage_two_fee_type' => ['nullable', 'required_if:stage_two_enabled,1', Rule::in(['fixed', 'percentage'])],
            'stage_two_fee_value' => ['nullable', 'required_if:stage_two_enabled,1', 'numeric', 'min:0'],
            'stage_two_minimum_amount' => ['nullable', 'required_if:stage_two_fee_type,percentage', 'decimal:0,2'],
            'default_eligibility_days' => ['required', 'integer', 'between:1,730'],
            'effective_from' => ['required', 'date'],
            'amendment_reason' => ['required', 'string', 'max:500'],
        ]);

        $stageOneDaysLate = (int) $data['grace_days'] + 1;
        if (($data['stage_two_enabled'] ?? false) && (int) $data['stage_two_days_late'] <= $stageOneDaysLate) {
            throw ValidationException::withMessages(['stage_two_days_late' => 'Stage two must occur after the stage-one late fee.']);
        }
        $purchasePrice = Money::toCents($data['purchase_price']);
        $documentationFeeStandard = Money::toCents($data['documentation_fee_standard']);
        $documentationFeeWaived = Money::toCents($data['documentation_fee_waived']);
        if ($documentationFeeWaived > $documentationFeeStandard) {
            throw ValidationException::withMessages(['documentation_fee_waived' => 'The waived amount cannot exceed the documentation fee.']);
        }
        if ($documentationFeeWaived > 0 && blank($data['documentation_fee_waiver_reason'] ?? null)) {
            throw ValidationException::withMessages(['documentation_fee_waiver_reason' => 'Enter a reason for the documentation-fee waiver.']);
        }
        $previousPaid = filled($data['previous_principal_paid'] ?? null) ? Money::toCents($data['previous_principal_paid']) : 0;

        DB::transaction(function () use ($request, $plan, $terms, $data, $previousPaid, $stageOneDaysLate, $purchasePrice, $documentationFeeStandard, $documentationFeeWaived): void {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $lockedTerms = PaymentPlanBillingTerm::query()->lockForUpdate()->findOrFail($terms->id);
            $before = ['plan' => $lockedPlan->only(['plan_number', 'title', 'asset_description', 'notes', 'status', 'plan_start_date', 'first_payment_amount', 'first_due_date', 'purchase_price', 'documentation_fee_standard', 'documentation_fee_waived', 'documentation_fee_waiver_reason']), 'billing_terms' => $lockedTerms->getAttributes()];
            $this->contractAmounts->amend($lockedPlan, $request->user(), $purchasePrice, $documentationFeeStandard, $documentationFeeWaived, $data['effective_from'], $data['amendment_reason'], $data['documentation_fee_waiver_reason'] ?? null);
            $this->openingPrincipalCredit->amend($lockedPlan, $request->user(), $previousPaid, $data['effective_from'], $data['amendment_reason']);
            $scheduled = Money::toCents($data['scheduled_payment_amount']);
            $monthlyFee = Money::toCents($data['monthly_service_fee']);
            $firstPayment = filled($data['first_payment_amount'] ?? null) ? Money::toCents($data['first_payment_amount']) : null;

            $lockedPlan->update([
                'plan_number' => trim($data['plan_number']), 'apn' => trim($data['plan_number']), 'title' => $data['title'],
                'asset_description' => $data['asset_description'] ?? null, 'notes' => $data['notes'] ?? null,
                'plan_start_date' => $data['contract_start_date'],
                'status' => $data['status'], 'first_payment_amount' => $firstPayment,
                'first_due_date' => $data['first_payment_due_date'] ?? null, 'customary_monthly_payment' => $scheduled,
                'monthly_service_fee' => $monthlyFee, 'monthly_due_day' => $data['invoice_day'],
                'grace_period_days' => $data['grace_days'], 'updated_by_user_id' => $request->user()->id,
                'scheduled_invoice_email_enabled' => $request->boolean('scheduled_invoice_email_enabled'),
                'automated_reminders_enabled' => $request->boolean('automated_reminders_enabled'),
                'automatic_invoice_email_enabled' => $request->boolean('automatic_invoice_email_enabled'),
                'accelerated_testing_mode' => $request->boolean('accelerated_testing_mode'),
            ]);

            $effectiveFrom = Carbon::parse($data['effective_from']);
            $lockedTerms->update(['effective_to' => $effectiveFrom->copy()->subDay()->format('Y-m-d')]);
            $newTerms = PaymentPlanBillingTerm::query()->create([
                'payment_plan_id' => $lockedPlan->id, 'frequency' => 'monthly', 'invoice_day' => $data['invoice_day'],
                'due_days_after_issue' => $data['due_days_after_issue'], 'grace_days' => $data['grace_days'],
                'scheduled_payment_amount' => $scheduled, 'monthly_service_fee' => $monthlyFee, 'stage_one_enabled' => true,
                'stage_one_fee_type' => $data['stage_one_fee_type'],
                'stage_one_fixed_amount' => $data['stage_one_fee_type'] === 'fixed' ? Money::toCents((string) $data['stage_one_fee_value']) : null,
                'stage_one_percentage_rate' => $data['stage_one_fee_type'] === 'percentage' ? $data['stage_one_fee_value'] : null,
                'stage_one_minimum_amount' => $data['stage_one_fee_type'] === 'percentage' ? Money::toCents($data['stage_one_minimum_amount']) : 0,
                'stage_one_days_late' => $stageOneDaysLate, 'stage_two_enabled' => (bool) ($data['stage_two_enabled'] ?? false),
                'stage_two_fee_type' => ($data['stage_two_enabled'] ?? false) ? $data['stage_two_fee_type'] : null,
                'stage_two_fixed_amount' => ($data['stage_two_enabled'] ?? false) && $data['stage_two_fee_type'] === 'fixed' ? Money::toCents((string) $data['stage_two_fee_value']) : null,
                'stage_two_percentage_rate' => ($data['stage_two_enabled'] ?? false) && $data['stage_two_fee_type'] === 'percentage' ? $data['stage_two_fee_value'] : null,
                'stage_two_minimum_amount' => ($data['stage_two_enabled'] ?? false) && $data['stage_two_fee_type'] === 'percentage' ? Money::toCents($data['stage_two_minimum_amount']) : 0,
                'stage_two_days_late' => ($data['stage_two_enabled'] ?? false) ? $data['stage_two_days_late'] : null,
                'default_eligibility_days' => $data['default_eligibility_days'], 'effective_from' => $effectiveFrom->format('Y-m-d'),
                'reason' => $data['amendment_reason'], 'created_by_user_id' => $request->user()->id,
            ]);

            AuditLog::query()->create([
                'actor_type' => 'administrator', 'actor_user_id' => $request->user()->id, 'event' => 'payment_plan.amended',
                'auditable_type' => PaymentPlan::class, 'auditable_id' => $lockedPlan->id, 'before_values' => $before,
                'after_values' => ['plan' => $lockedPlan->fresh()->only(array_keys($before['plan'])), 'billing_terms' => $newTerms->getAttributes(), 'reason' => $data['amendment_reason']],
                'ip_address' => $request->ip(), 'user_agent' => str($request->userAgent())->limit(500),
            ]);
        }, 3);

        return redirect()->route('admin.plans.show', $plan)->with('success', 'Payment plan amendment saved.');
    }
}
