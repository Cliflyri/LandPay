<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Services\FinancialBalanceService;
use App\Services\AutomaticInvoiceService;
use App\Services\CurrentPayoffService;
use App\Services\ReminderAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly FinancialBalanceService $balances,
        private readonly CurrentPayoffService $payoffs,
        private readonly AutomaticInvoiceService $automaticInvoices,
        private readonly ReminderAutomationService $reminderAutomation,
    ) {}

    public function __invoke(Request $request): View
    {
        $planStatus = PaymentPlan::normalizeAdminStatusFilter($request->string('status')->value());
        $planSearch = trim($request->string('search')->value());

        $plans = PaymentPlan::query()
            ->forAdminListing($planStatus, $planSearch)
            ->leftJoin('payment_plan_clients as primary_membership', function ($join): void {
                $join->on('primary_membership.payment_plan_id', '=', 'payment_plans.id')
                    ->where('primary_membership.role', 'primary')
                    ->whereNull('primary_membership.effective_to');
            })
            ->leftJoin('clients as primary_client', 'primary_client.id', '=', 'primary_membership.client_id')
            ->select('payment_plans.*')
            ->with([
                'memberships' => fn ($query) => $query->whereNull('effective_to')->with('client.portalAccount'),
                'currentBillingTerms', 'billingTerms', 'pauses',
                'invoices' => fn ($query) => $query->with(['items', 'reminders'])->orderBy('due_date'),
            ])
            ->orderByRaw("CASE
                WHEN NULLIF(TRIM(primary_client.organization_name), '') IS NOT NULL
                    THEN TRIM(primary_client.organization_name)
                ELSE TRIM(CONCAT(
                    COALESCE(primary_client.first_name, ''),
                    ' ',
                    COALESCE(primary_client.last_name, '')
                ))
            END")
            ->orderBy('payment_plans.plan_number')
            ->paginate(25)
            ->withQueryString();

        $nextReminders = $this->reminderAutomation->eligible(Carbon::today(), true)->groupBy(fn ($item) => $item['invoice']->payment_plan_id);
        $plans->getCollection()->transform(fn (PaymentPlan $plan) => $this->dashboardRow($plan, $nextReminders->get($plan->id)?->first()));
        $plans->setCollection($plans->getCollection()->sortByDesc(
            fn (array $row) => ((int) $row['ready_to_close'] * 2) + (int) $row['plan']->accelerated_testing_mode
        )->values());

        return view('admin.dashboard', [
            'clientCount' => Client::query()->whereNull('archived_at')->count(),
            'planCount' => PaymentPlan::query()->whereIn('status', ['active', 'paused'])->count(),
            'openInvoiceCount' => Invoice::query()->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])->count(),
            'plans' => $plans,
            'planStatus' => $planStatus,
            'planSearch' => $planSearch,
            'notices' => AdminNotice::query()->whereNull('dismissed_at')->with(['client', 'changeRequest', 'paymentIntent.payment', 'secureMessageThread'])->latest()->get(),
        ]);
    }

    /** @return array<string, mixed> */
    private function dashboardRow(PaymentPlan $plan, ?array $nextReminder = null): array
    {
        $primary = $plan->memberships->firstWhere('role', 'primary');
        $recipient = $plan->memberships->first(
            fn ($membership) => $membership->receives_invoices && $membership->role === 'primary'
        ) ?? $plan->memberships->firstWhere('receives_invoices', true);
        $invoiceBalances = $plan->invoices->mapWithKeys(
            fn (Invoice $invoice) => [$invoice->id => $this->balances->invoiceBalance($invoice)]
        );
        $currentBalanceItems = $plan->invoices
            ->filter(fn (Invoice $invoice) => ($invoiceBalances[$invoice->id] ?? 0) > 0)
            ->map(fn (Invoice $invoice) => [
                'amount' => (int) $invoiceBalances[$invoice->id],
                'due_date' => $invoice->due_date,
                'invoice' => $invoice,
                'label' => $invoice->invoice_number,
            ]);
        $hasFirstPaymentInvoice = $plan->invoices->contains(
            fn (Invoice $invoice) => $invoice->status !== InvoiceStatus::Voided
                && $invoice->items->contains(fn ($item) => $item->description === 'First payment')
        );
        if (! $hasFirstPaymentInvoice
            && $plan->first_payment_amount !== null
            && $plan->first_due_date !== null
            && Carbon::today()->greaterThanOrEqualTo($plan->first_due_date)) {
            $currentBalanceItems->push([
                'amount' => (int) $plan->first_payment_amount,
                'due_date' => $plan->first_due_date,
                'invoice' => null,
                'label' => 'First payment',
            ]);
        }
        $currentBalanceItems = $currentBalanceItems->sortBy('due_date')->values();
        $currentBalanceDue = (int) $currentBalanceItems->sum('amount');
        $oldestDueInvoice = $currentBalanceItems->first(fn (array $item) => $item['invoice'] !== null)['invoice'] ?? null;
        $oldestDueDate = $currentBalanceItems->first()['due_date'] ?? null;
        if ($oldestDueDate === null && ! $hasFirstPaymentInvoice && $plan->first_due_date !== null) {
            $oldestDueDate = $plan->first_due_date;
        }
        $lastReminder = $plan->invoices->flatMap->reminders->where('status', 'sent')->sortByDesc('sent_at')->first();
        $monthlyPrincipal = (int) ($plan->currentBillingTerms?->scheduled_payment_amount ?? $plan->customary_monthly_payment ?? 0);
        $monthlyServiceFee = (int) ($plan->currentBillingTerms?->monthly_service_fee ?? $plan->monthly_service_fee ?? 0);
        $contractBalance = $this->balances->contractBalance($plan);
        return [
            'plan' => $plan,
            'primary_client' => $primary?->client,
            'client_name' => $this->clientName($primary?->client),
            'co_client_count' => $plan->memberships->where('role', 'co_client')->count(),
            'email' => $recipient?->client?->email,
            'contract_balance' => $contractBalance,
            'current_payoff' => $this->payoffs->amount($plan),
            'current_balance_due' => $currentBalanceDue,
            'ready_to_close' => in_array($plan->status, ['active', 'paused'], true)
                && $contractBalance <= 0
                && $currentBalanceDue <= 0,
            'current_balance_items' => $currentBalanceItems,
            'balance_invoice' => $oldestDueInvoice,
            'operational_status' => $this->operationalStatus($plan, $oldestDueDate, $currentBalanceDue),
            'monthly_principal' => $monthlyPrincipal,
            'monthly_total' => $monthlyPrincipal + $monthlyServiceFee,
            'reminder_last_sent' => $lastReminder ? $lastReminder->sent_at?->format('M j, Y g:i A').' ('.($lastReminder->automated ? 'Automated' : 'Manual').')' : null,
            'reminder_next_send' => $plan->status === 'paused' ? 'Paused' : ($nextReminder ? $nextReminder['send_date']->format('M j, Y') : ($plan->automated_reminders_enabled ? 'No reminder scheduled' : 'Automation disabled')),
            'next_invoice' => $plan->status === 'paused' ? 'Paused' : ($this->automaticInvoices->nextDate($plan)?->format('M j, Y') ?? 'Not scheduled'),
        ];
    }

    private function clientName(?Client $client): string
    {
        if ($client === null) {
            return 'No primary client';
        }

        if (filled($client->organization_name)) {
            return $client->organization_name;
        }

        return trim(collect([$client->first_name, $client->last_name])->filter()->join(' ')) ?: 'Unnamed client';
    }

    private function operationalStatus(PaymentPlan $plan, ?Carbon $oldestDueDate, int $currentBalanceDue): string
    {
        if ($plan->status === 'draft') {
            return 'Draft';
        }
        if ($plan->status === 'paused') {
            return 'Paused';
        }
        if (in_array($plan->status, ['closed', 'terminated'], true)) {
            return 'Closed';
        }
        if ($oldestDueDate === null || $currentBalanceDue <= 0) {
            return 'Current';
        }

        $today = Carbon::today();
        $dueDate = $oldestDueDate;
        $terms = $plan->currentBillingTerms;
        $graceDays = $terms?->grace_days ?? $plan->grace_period_days;
        $defaultDays = $terms?->default_eligibility_days;

        if ($defaultDays !== null && $today->greaterThanOrEqualTo($dueDate->copy()->addDays($defaultDays))) {
            return 'Default Eligible';
        }
        if ($today->greaterThan($dueDate->copy()->addDays($graceDays))) {
            return 'Past Due';
        }
        if ($today->greaterThanOrEqualTo($dueDate)) {
            return 'Due';
        }
        if ($today->greaterThanOrEqualTo($dueDate->copy()->subDays(7))) {
            return 'Due Soon';
        }

        return 'Current';
    }
}
