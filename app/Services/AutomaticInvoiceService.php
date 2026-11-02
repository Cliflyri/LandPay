<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\AdminNotice;
use App\Models\AuditLog;
use App\Models\PaymentPlanPause;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class AutomaticInvoiceService
{
    public function __construct(private readonly MonthlyInvoiceService $invoices, private readonly InvoiceEmailService $email, private readonly InvoiceSmsService $sms) {}

    public function run(?Carbon $through = null): array
    {
        $through ??= Carbon::today();
        $result = ['created' => 0, 'emailed' => 0, 'failed' => 0];
        $this->resumeScheduledPlans($through->min(Carbon::today()));
        Invoice::query()
            ->with(['paymentPlan.createdBy', 'paymentPlan.updatedBy'])
            ->whereDate('issue_date', '<=', $through)
            ->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])
            ->whereHas('paymentPlan', fn ($query) => $query->where('status', 'active')->where('scheduled_invoice_email_enabled', true))
            ->whereDoesntHave('emailDeliveries', fn ($query) => $query->where('template_slug', 'invoice-email')->where('status', 'sent'))
            ->each(function (Invoice $invoice) use (&$result): void {
                $actor = $invoice->paymentPlan->updatedBy ?? $invoice->paymentPlan->createdBy;
                if ($actor === null) return;
                try {
                    $this->email->send($invoice, $actor, 'inline');
                    $result['emailed']++;
                } catch (Throwable $e) {
                    $this->notice($invoice->paymentPlan, $invoice, 'Automatic invoice email failed', $e);
                    $result['failed']++;
                }
            });
        PaymentPlan::query()->where('status', 'active')->with(['billingTerms', 'pauses', 'createdBy', 'updatedBy'])->each(function (PaymentPlan $plan) use ($through, &$result): void {
            foreach ($this->missingDates($plan, $through) as $date) {
                try {
                    $terms = $this->termsFor($plan, $date);
                    $actor = $plan->updatedBy ?? $plan->createdBy;
                    if ($terms === null || $actor === null) {
                        continue;
                    }
                    if ($this->invoices->uninvoicedPrincipal($plan) <= 0) {
                        continue;
                    }

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
                    try { $this->sms->sendInvoiceCreated($invoice); } catch (Throwable $e) { $this->notice($plan, $invoice, 'Automatic invoice SMS failed', $e); }
                    if ($plan->scheduled_invoice_email_enabled) {
                        try {
                            $this->email->send($invoice, $actor, 'inline');
                            $result['emailed']++;
                        } catch (Throwable $e) {
                            $this->notice($plan, $invoice, 'Automatic invoice email failed', $e);
                            $result['failed']++;
                        }
                    }
                } catch (Throwable $e) {
                    $this->notice($plan, null, 'Automatic invoice generation failed', $e);
                    $result['failed']++;
                }
            }
        });

        return $result;
    }

    private function resumeScheduledPlans(Carbon $through): void
    {
        PaymentPlan::query()->where('status', 'paused')
            ->whereHas('currentPause', fn ($query) => $query->whereDate('planned_resume_date', '<=', $through))
            ->each(function (PaymentPlan $plan) use ($through): void {
                DB::transaction(function () use ($plan, $through): void {
                    $locked = PaymentPlan::query()->lockForUpdate()->find($plan->id);
                    if ($locked === null || $locked->status !== 'paused') return;
                    $pause = PaymentPlanPause::query()->where('payment_plan_id', $locked->id)
                        ->whereNull('resume_date')->lockForUpdate()->latest('id')->first();
                    if ($pause === null || $pause->planned_resume_date === null || $pause->planned_resume_date->gt($through)) return;
                    $date = $pause->planned_resume_date->toDateString();
                    $pause->update(['resume_date' => $date, 'resume_note' => 'Automatically resumed on the scheduled date.',
                        'resumed_by_user_id' => null, 'resumed_at' => now()]);
                    $locked->update(['status' => 'active', 'paused_on_hold' => false]);
                    AuditLog::query()->create([
                        'actor_type' => 'system', 'actor_user_id' => null, 'event' => 'payment_plan.automatically_resumed',
                        'auditable_type' => PaymentPlan::class, 'auditable_id' => $locked->id,
                        'before_values' => ['status' => 'paused'],
                        'after_values' => ['status' => 'active', 'pause_id' => $pause->id, 'resume_date' => $date],
                    ]);
                    AdminNotice::query()->create([
                        'type' => 'payment_plan_automatically_resumed', 'payment_plan_id' => $locked->id,
                        'title' => 'Plan automatically resumed',
                        'message' => 'Plan '.$locked->plan_number.' automatically resumed effective '.$pause->planned_resume_date->format('F j, Y').'. Regular invoice scheduling and enabled automated reminders have resumed.',
                    ]);
                }, 3);
            });
    }

    public function nextDate(PaymentPlan $plan, ?Carbon $from = null): ?Carbon
    {
        if ($plan->status !== 'active') {
            return null;
        }
        $from ??= Carbon::today();
        $plan->loadMissing(['billingTerms', 'pauses']);
        if ($plan->first_scheduled_invoice_date?->gt($from)) {
            $from = $plan->first_scheduled_invoice_date->copy();
        }
        if ($plan->invoicing_resumes_on?->gt($from)) {
            $from = $plan->invoicing_resumes_on->copy();
        }
        $activatedOn = $plan->activated_at?->copy()->startOfDay();
        if ($activatedOn?->gt($from)) {
            $from = $activatedOn;
        }

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

        $existing = Invoice::query()->where('payment_plan_id', $plan->id)->pluck('invoice_number')->flip();
        for ($month = $from->copy()->startOfMonth(); $month->lte($from->copy()->addMonths(24)->startOfMonth()); $month->addMonth()) {
            $date = $this->dateForMonth($plan, $month);
            if ($date && $date->gte($from) && ! $this->pausedOn($plan, $date) && ! $existing->has('INV-'.$plan->id.'-'.$date->format('Ym'))) {
                return $date;
            }
        }

        return null;
    }

    private function missingDates(PaymentPlan $plan, Carbon $through): Collection
    {
        $legacyAnchor = ($plan->first_due_date ?? $plan->plan_start_date)?->copy()->startOfDay();
        if ($legacyAnchor === null) {
            return collect();
        }
        $notBefore = $plan->first_scheduled_invoice_date?->copy()->startOfDay()
            ?? $legacyAnchor->copy()->addDay();
        if ($plan->invoicing_resumes_on?->gt($notBefore)) {
            $notBefore = $plan->invoicing_resumes_on->copy();
        }
        $activatedOn = $plan->activated_at?->copy()->startOfDay();
        if ($activatedOn?->gt($notBefore)) {
            $notBefore = $activatedOn;
        }

        if ($plan->accelerated_testing_mode) {
            $date = $through->copy()->startOfDay();
            $invoiceNumber = 'INV-'.$plan->id.'-'.$date->format('Ymd');

            if (
                $date->lt($notBefore)
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
        for ($month = $notBefore->copy()->startOfMonth(); $month->lte($through->copy()->startOfMonth()); $month->addMonth()) {
            $date = $this->dateForMonth($plan, $month);
            if ($date && $date->gte($notBefore) && $date->lte($through) && ! $this->pausedOn($plan, $date) && ! $existing->has('INV-'.$plan->id.'-'.$date->format('Ym'))) {
                $dates->push($date);
            }
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
        AdminNotice::query()->create(['type' => 'billing_automation_failure', 'payment_plan_id' => $plan->id, 'invoice_id' => $invoice?->id, 'client_id' => $plan->memberships()->where('role','primary')->whereNull('effective_to')->whereDate('effective_from','<=',today())->value('client_id'), 'title' => $title, 'message' => 'Plan '.$plan->plan_number.($invoice ? ', invoice '.$invoice->invoice_number : '').': '.str($e->getMessage())->limit(400)]);
        report($e);
    }
}
