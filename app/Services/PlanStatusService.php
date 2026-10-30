<?php

namespace App\Services;

use App\Models\PaymentPlan;
use App\Enums\InvoiceStatus;
use Illuminate\Support\Carbon;

class PlanStatusService
{
    public function forPlan(PaymentPlan $plan): string
    {
        $plan->loadMissing(['invoices', 'currentBillingTerms']);
        $balances = app(FinancialBalanceService::class);
        $due = $plan->invoices->map(fn ($invoice) => [
            'amount' => $balances->invoiceBalance($invoice), 'due_date' => $invoice->due_date,
        ])->filter(fn ($item) => $item['amount'] > 0);
        $hasFirst = $plan->invoices->contains(fn ($invoice) =>
            $invoice->status !== InvoiceStatus::Voided && str_starts_with($invoice->invoice_number, 'FP-'));
        if (!$hasFirst && $plan->first_payment_amount !== null && $plan->first_due_date !== null
            && Carbon::today()->gte($plan->first_due_date)) {
            $due->push(['amount' => (int) $plan->first_payment_amount, 'due_date' => $plan->first_due_date]);
        }
        $oldest = $due->sortBy('due_date')->first()['due_date'] ?? (!$hasFirst ? $plan->first_due_date : null);
        return $this->label($plan, $oldest, (int) $due->sum('amount'));
    }
    public function label(PaymentPlan $plan, ?Carbon $oldestDueDate, int $currentBalanceDue): string
    {
        if ($plan->status === 'draft') {
            return 'Draft';
        }
        if ($plan->status === 'paused') {
            return 'Paused';
        }
        if ($plan->status === 'terminated') {
            return 'Terminated';
        }
        if ($plan->status === 'closed') {
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
