<?php

namespace App\Services;

use App\Models\AdminNotice;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class AutomaticInvoiceService
{
    public function __construct(private readonly MonthlyInvoiceService $invoices, private readonly InvoiceEmailService $email) {}

    public function run(?Carbon $through = null): array
    {
        $through ??= Carbon::today();
        $result = ['created' => 0, 'emailed' => 0, 'failed' => 0];
        PaymentPlan::query()->where('status', 'active')->with(['billingTerms', 'pauses', 'createdBy', 'updatedBy'])->each(function (PaymentPlan $plan) use ($through, &$result): void {
            foreach ($this->missingDates($plan, $through) as $date) {
                try {
                    $terms = $this->termsFor($plan, $date);
                    $actor = $plan->updatedBy ?? $plan->createdBy;
                    if ($terms === null || $actor === null) continue;
                    if ($this->invoices->uninvoicedPrincipal($plan) <= 0) continue;
                    
$invoiceNumber = $plan->accelerated_testing_mode
    ? 'INV-'.$plan->id.'-'.$date->format('Ymd')
    : 'INV-'.$plan->id.'-'.$date->format('Ym');

$periodStart = $plan->accelerated_testing_mode
    ? $date->copy()
    : $date->copy()->startOfMonth();

$periodEnd = $plan->accelerated_testing_mode
    ? $date->copy()
    : $date->copy()->endOfMonth();

$invoice = $this->invoices->issue(
    $plan,
    $terms,
    $actor,
    $invoiceNumber,
    $periodStart,
    $periodEnd,
    $date,
    0,
    null,
    true,
);

                    $result['created']++;
                    if ($plan->scheduled_invoice_email_enabled) {
                        try { $this->email->send($invoice, $actor, 'inline'); $result['emailed']++; }
                        catch (Throwable $e) { $this->notice($plan, $invoice, 'Automatic invoice email failed', $e); $result['failed']++; }
                    }
                } catch (Throwable $e) { $this->notice($plan, null, 'Automatic invoice generation failed', $e); $result['failed']++; }
            }
        });
        return $result;
    }

    public function nextDate(PaymentPlan $plan, ?Carbon $from = null): ?Carbon
    {
        if ($plan->status !== 'active') return null;
        $from ??= Carbon::today();
        $plan->loadMissing(['billingTerms', 'pauses']);

if ($plan->accelerated_testing_mode) {
    for (
        $date = $from->copy()->startOfDay();
        $date->lte($from->copy()->addYear());
        $date->addDay()
    ) {
        $invoiceNumber = 'INV-'.$plan->id.'-'.$date->format('Ymd');

        if (
            ! $this->pausedOn($plan, $date)
            && ! Invoice::query()
                ->where('payment_plan_id', $plan->id)
                ->where('invoice_number', $invoiceNumber)
                ->exists()
        ) {
            return $date->copy();
        }
    }

    return null;
}


        for ($month = $from->copy()->startOfMonth(); $month->lte($from->copy()->addMonths(24)->startOfMonth()); $month->addMonth()) {
            $date = $this->dateForMonth($plan, $month);
            if ($date && $date->gte($from) && ! $this->pausedOn($plan, $date)) return $date;
        }
        return null;
    }

    private function missingDates(PaymentPlan $plan, Carbon $through): Collection
    {
        $anchor = ($plan->first_due_date ?? $plan->plan_start_date)?->copy()->startOfDay();
        if ($anchor === null) return collect();

if ($plan->accelerated_testing_mode) {
    $date = $through->copy()->startOfDay();
    $invoiceNumber = 'INV-'.$plan->id.'-'.$date->format('Ymd');

    if (
        $date->lte($anchor)
        || $this->pausedOn($plan, $date)
        || Invoice::query()
            ->where('payment_plan_id', $plan->id)
            ->where('invoice_number', $invoiceNumber)
            ->exists()
    ) {
        return collect();
    }

    return collect([$date]);
}

        $existing = Invoice::query()->where('payment_plan_id', $plan->id)->pluck('invoice_number')->flip();
        $dates = collect();
        for ($month = $anchor->copy()->startOfMonth(); $month->lte($through->copy()->startOfMonth()); $month->addMonth()) {
            $date = $this->dateForMonth($plan, $month);
            if ($date && $date->gt($anchor) && $date->lte($through) && ! $this->pausedOn($plan, $date) && ! $existing->has('INV-'.$plan->id.'-'.$date->format('Ym'))) $dates->push($date);
        }
        return $dates;
    }

    private function dateForMonth(PaymentPlan $plan, Carbon $month): ?Carbon
    {
        $terms = $this->termsFor($plan, $month->copy()->endOfMonth());
        return $terms ? $month->copy()->day(min((int) $terms->invoice_day, $month->daysInMonth)) : null;
    }

    private function termsFor(PaymentPlan $plan, Carbon $date): ?PaymentPlanBillingTerm
    {
        return $plan->billingTerms->filter(fn ($term) => $term->effective_from->lte($date) && ($term->effective_to === null || $term->effective_to->gte($date->copy()->startOfMonth())))->sortByDesc('effective_from')->first();
    }

    private function pausedOn(PaymentPlan $plan, Carbon $date): bool
    {
        return $plan->pauses->contains(fn ($pause) => $date->gte($pause->pause_date) && ($pause->resume_date === null || $date->lt($pause->resume_date)));
    }

    private function notice(PaymentPlan $plan, ?Invoice $invoice, string $title, Throwable $e): void
    {
        AdminNotice::query()->create(['type' => 'billing_automation_failure', 'title' => $title, 'message' => 'Plan '.$plan->plan_number.($invoice ? ', invoice '.$invoice->invoice_number : '').': '.str($e->getMessage())->limit(400)]);
        report($e);
    }
}
