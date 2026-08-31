<?php

namespace App\Http\Controllers\Admin;

use App\Services\MonthlyServiceFeeHistoryService;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use App\Services\FinancialBalanceService;
use App\Services\FirstPaymentInvoiceService;
use App\Services\InvoiceEditService;
use App\Services\InvoiceEmailService;
use App\Services\InvoiceReminderService;
use App\Services\InvoiceVoidService;
use App\Services\ManualInvoiceService;
use App\Services\MonthlyInvoiceService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly MonthlyInvoiceService $invoices,
        private readonly FinancialBalanceService $balances,
        private readonly FirstPaymentInvoiceService $firstPaymentInvoices,
        private readonly InvoiceReminderService $reminders,
        private readonly InvoiceVoidService $voids,
        private readonly ManualInvoiceService $manualInvoices,
        private readonly MonthlyServiceFeeHistoryService $monthlyServiceFeeHistory,
        private readonly InvoiceEditService $invoiceEdits,
        private readonly InvoiceEmailService $invoiceEmails,
    ) {}

    public function create(PaymentPlan $plan): View
    {
        return $this->form($plan);
    }

    public function preview(Request $request, PaymentPlan $plan): View
    {
        $data = $this->validateInvoice($request);

        return $this->form($plan, $this->invoicePreview($plan, $data), $data);
    }

    public function store(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $data = $this->validateInvoice($request);
        $preview = $this->invoicePreview($plan, $data);
        $invoice = $this->invoices->issue(
            $plan,
            $preview['terms'],
            $request->user(),
            $preview['invoice_number_base'],
            $preview['period_start'],
            $preview['period_end'],
            $preview['issue_date'],
            $preview['monthly_fee_waiver'],
            $data['waiver_reason'] ?? null,
        );

        $message = 'Monthly invoice issued successfully.';
        if ($plan->automatic_invoice_email_enabled) {
            $delivery = $this->invoiceEmails->send($invoice, $request->user(), 'inline');
            $message .= ' Invoice emailed to '.$delivery->recipient_email.'.';
        }

        return redirect()->route('admin.invoices.show', $invoice)->with('success', $message);
    }

    public function manualCreate(PaymentPlan $plan): View
    {
        return $this->manualForm($plan);
    }

    public function manualPreview(Request $request, PaymentPlan $plan): View
    {
        $data = $this->validateManualInvoice($request);

        return $this->manualForm($plan, $this->manualPreviewValues($plan, $data), $data);
    }

    public function manualStore(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $data = $this->validateManualInvoice($request);
        $preview = $this->manualPreviewValues($plan, $data);
        $invoice = $this->manualInvoices->issue($plan, $request->user(), $data['issue_date'], $preview['items']);

        $message = 'Invoice issued successfully.';
        if ($plan->automatic_invoice_email_enabled) {
            $delivery = $this->invoiceEmails->send($invoice, $request->user(), 'inline');
            $message .= ' Invoice emailed to '.$delivery->recipient_email.'.';
        }

        return redirect()->route('admin.invoices.show', $invoice)->with('success', $message);
    }

    private function manualForm(PaymentPlan $plan, ?array $preview = null, array $input = []): View
    {
        $terms = $plan->currentBillingTerms()->firstOrFail();

        $plan->load([
    'memberships' => fn ($query) => $query
        ->whereNull('effective_to')
        ->with('client'),
]);

$primaryClient = $plan->memberships
    ->firstWhere('role', 'primary')
    ?->client;

$primaryClientName = $primaryClient?->organization_name
    ?: trim(collect([
        $primaryClient?->first_name,
        $primaryClient?->middle_name,
        $primaryClient?->last_name,
    ])->filter()->join(' '))
    ?: 'No primary client';
    
        $selectedDate = Carbon::parse(
            $input['issue_date'] ?? now()->toDateString()
        );

        $monthlyServiceFeeSummary = $this->monthlyServiceFeeHistory
            ->summaryForMonth($plan, $selectedDate);        

        return view('admin.invoices.manual-create', [
            'plan' => $plan,
            'primaryClientName' => $primaryClientName,
            'preview' => $preview,
            'input' => $input,
            'monthlyServiceFeeSummary' => $monthlyServiceFeeSummary,
            'contractBalance' => $this->balances->contractBalance($plan),
            'serviceFee' => (int) $terms->monthly_service_fee,
            'dueDays' => (int) $terms->due_days_after_issue,
        ]);
    }

    private function validateManualInvoice(Request $request): array
    {
        return $request->validate([
            'issue_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.type' => ['required', 'in:principal,fee,other'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.amount' => ['required', 'decimal:0,2', 'gt:0'],
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['paymentPlan.memberships.client', 'items', 'emailDeliveries', 'accessLink']);
        $balance = $this->balances->invoiceBalance($invoice);
        $subtotal = (int) $invoice->items->sum('standard_amount');
        $waivers = (int) $invoice->items->sum('waived_amount');
        $invoiceAmount = (int) $invoice->items->sum('amount');
        $paidToDate = (int) DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->join('financial_transactions', 'financial_transactions.id', '=', 'payments.financial_transaction_id')
            ->where('payment_allocations.invoice_id', $invoice->id)
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('financial_transactions as reversals')
                ->whereColumn('reversals.reversal_of_transaction_id', 'financial_transactions.id'))
            ->sum('payment_allocations.amount');
        $creditApplied = $this->balances->invoiceCreditApplied($invoice);
        $adjustments = $balance - $invoiceAmount + $paidToDate + $creditApplied;

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'balance' => $balance,
            'summary' => compact('subtotal', 'waivers', 'invoiceAmount', 'creditApplied', 'paidToDate', 'adjustments'),
            'canDelete' => $this->voids->canVoid($invoice),
            'canCreateFirstPayment' => $invoice->status === InvoiceStatus::Voided
                && $invoice->items->contains(fn ($item) => $item->description === 'First payment')
                && ! Invoice::query()
                    ->where('payment_plan_id', $invoice->payment_plan_id)
                    ->where('status', '!=', InvoiceStatus::Voided->value)
                    ->whereHas('items', fn ($query) => $query->where('description', 'First payment'))
                    ->exists(),
            'reminderRecipient' => $this->reminders->recipientMembership($invoice),
        ]);
    }


    public function edit(Invoice $invoice): View
    {
        abort_if($invoice->status === InvoiceStatus::Voided, 404);
        $invoice->load(['paymentPlan.memberships.client', 'items']);

        return view('admin.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.type' => ['required', 'in:scheduled_purchase_payment,documentation_fee,monthly_service_fee,late_fee_stage_1,late_fee_stage_2,administrative_fee,other'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.amount' => ['required', 'decimal:0,2'],
        ]);
        $items = collect($data['items'])->map(fn (array $item): array => [
            'id' => isset($item['id']) ? (int) $item['id'] : null,
            'type' => $item['type'],
            'description' => trim($item['description']),
            'amount' => Money::toSignedCents($item['amount']),
        ])->values()->all();

        $this->invoiceEdits->update(
            $invoice,
            $request->user(),
            $data['issue_date'],
            $data['due_date'],
            $items,
        );

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }
    public function createFirstPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->load(['items', 'paymentPlan']);
        if ($invoice->status !== InvoiceStatus::Voided
            || ! $invoice->items->contains(fn ($item) => $item->description === 'First payment')) {
            throw ValidationException::withMessages([
                'invoice' => 'Only a voided first-payment invoice can be recreated.',
            ]);
        }

        $amount = (int) $invoice->items->where('description', 'First payment')->sum('amount');
        $newInvoice = $this->firstPaymentInvoices->issue(
            $invoice->paymentPlan,
            $request->user(),
            $amount,
            $invoice->issue_date,
            $invoice->due_date,
        );

        $message = 'First-payment invoice created successfully.';
        if ($invoice->paymentPlan->automatic_invoice_email_enabled) {
            $delivery = $this->invoiceEmails->send($newInvoice, $request->user(), 'inline');
            $message .= ' Invoice emailed to '.$delivery->recipient_email.'.';
        }

        return redirect()->route('admin.invoices.show', $newInvoice)
            ->with('success', $message);
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $plan = $invoice->paymentPlan;

        $this->voids->void($invoice, $request->user(), $data['reason']);

        return redirect()->route('admin.plans.show', $plan)
            ->with('success', 'Invoice deleted. Its obligation was removed and any applied account credit was restored.');
    }

    private function form(PaymentPlan $plan, ?array $preview = null, array $input = []): View
    {
        return view('admin.invoices.create', [
            'plan' => $plan,
            'preview' => $preview,
            'input' => $input,
            'suggestedMonth' => $this->suggestedMonth($plan),
            'contractBalance' => $this->balances->contractBalance($plan),
        ]);
    }

    private function validateInvoice(Request $request): array
    {
        return $request->validate([
            'billing_month' => ['required', 'date_format:Y-m'],
            'monthly_fee_waiver' => ['nullable', 'decimal:0,2', 'min:0'],
            'waiver_reason' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function manualPreviewValues(PaymentPlan $plan, array $data): array
    {
        if (! in_array($plan->status, ['active', 'paused'], true)) {
            throw ValidationException::withMessages(['payment_plan' => 'Only an active or paused payment plan may receive an invoice.']);
        }

        $items = collect($data['items'])->map(fn (array $item): array => [
            'type' => $item['type'],
            'description' => trim($item['description']),
            'amount' => Money::toCents($item['amount']),
        ])->values()->all();
        $principal = collect($items)->where('type', 'principal')->sum('amount');
        $contractBalance = $this->balances->contractBalance($plan);
        if ($principal > $contractBalance) {
            throw ValidationException::withMessages(['items' => 'Plan-payment line items cannot exceed the remaining contract balance.']);
        }
        $issueDate = Carbon::parse($data['issue_date']);
        $terms = $plan->currentBillingTerms()->firstOrFail();

        return [
            'issue_date' => $issueDate,
            'due_date' => $issueDate->copy()->addDays((int) $terms->due_days_after_issue),
            'items' => $items,
            'principal' => $principal,
            'total' => collect($items)->sum('amount'),
        ];
    }

    private function invoicePreview(PaymentPlan $plan, array $data): array
    {
        if (! in_array($plan->status, ['active', 'paused'], true)) {
            throw ValidationException::withMessages(['payment_plan' => 'Only an active or paused payment plan may generate a monthly invoice.']);
        }

        $month = Carbon::createFromFormat('Y-m-d', $data['billing_month'].'-01')->startOfMonth();
        $periodStart = $month->copy();
        $periodEnd = $month->copy()->endOfMonth();
        $terms = PaymentPlanBillingTerm::query()
            ->where('payment_plan_id', $plan->id)
            ->whereDate('effective_from', '<=', $periodEnd)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $periodStart))
            ->latest('effective_from')
            ->firstOrFail();
        $issueDate = $month->copy()->day(min((int) $terms->invoice_day, $month->daysInMonth));
        $invoiceNumberBase = 'INV-'.$plan->id.'-'.$month->format('Ym');
        $existing = Invoice::query()
            ->where('payment_plan_id', $plan->id)
            ->whereDate('period_start', $periodStart)
            ->whereDate('period_end', $periodEnd)
            ->where('generation_source', 'administrator')
            ->where('status', '!=', InvoiceStatus::Voided->value)
            ->whereHas('items', fn ($query) => $query->where('item_type', 'scheduled_purchase_payment'))
            ->first();
        $invoiceNumber = $existing?->invoice_number ?? $this->nextAvailableInvoiceNumber($invoiceNumberBase);

        return ['existing' => $existing, 'invoice_number_base' => $invoiceNumberBase] + $this->previewValues($plan, $terms, $month, $issueDate, $invoiceNumber, $data);
    }

    private function previewValues(PaymentPlan $plan, PaymentPlanBillingTerm $terms, Carbon $month, Carbon $issueDate, string $invoiceNumber, array $data): array
    {
        $uninvoicedPrincipal = $this->invoices->uninvoicedPrincipal($plan);
        if ($uninvoicedPrincipal <= 0) {
            throw ValidationException::withMessages(['payment_plan' => 'The remaining contract principal is already billed or paid.']);
        }

        $scheduledAmount = min((int) $terms->scheduled_payment_amount, $uninvoicedPrincipal);
        $monthlyFeeStandard = (int) $terms->monthly_service_fee;
        $monthlyFeeWaiver = Money::toCents($data['monthly_fee_waiver'] ?? '0');
        if ($monthlyFeeWaiver > $monthlyFeeStandard) {
            throw ValidationException::withMessages(['monthly_fee_waiver' => 'Monthly fee waiver is outside the allowed amount.']);
        }
        if ($monthlyFeeWaiver > 0 && blank($data['waiver_reason'] ?? null)) {
            throw ValidationException::withMessages(['waiver_reason' => 'A waiver reason is required.']);
        }

        return [
            'terms' => $terms,
            'invoice_number' => $invoiceNumber,
            'period_start' => $month->copy(),
            'period_end' => $month->copy()->endOfMonth(),
            'issue_date' => $issueDate,
            'due_date' => $issueDate->copy()->addDays((int) $terms->due_days_after_issue),
            'scheduled_amount' => $scheduledAmount,
            'monthly_fee_standard' => $monthlyFeeStandard,
            'monthly_fee_waiver' => $monthlyFeeWaiver,
            'total' => $scheduledAmount + $monthlyFeeStandard - $monthlyFeeWaiver,
        ];
    }

    private function nextAvailableInvoiceNumber(string $baseNumber): string
    {
        $invoiceNumber = $baseNumber;
        if (! Invoice::query()->where('invoice_number', $invoiceNumber)->exists()) {
            return $invoiceNumber;
        }

        $sequence = 2;
        do {
            $invoiceNumber = $baseNumber.'-'.$sequence++;
        } while (Invoice::query()->where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }

    private function suggestedMonth(PaymentPlan $plan): string
    {
        $latestPeriod = Invoice::query()
            ->where('payment_plan_id', $plan->id)
            ->whereNotNull('period_start')
            ->max('period_start');

        return $latestPeriod
            ? Carbon::parse($latestPeriod)->addMonthNoOverflow()->format('Y-m')
            : now()->format('Y-m');
    }
}
