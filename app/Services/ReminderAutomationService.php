<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\AppSetting;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class ReminderAutomationService
{
    public function __construct(
        private readonly FinancialBalanceService $balances,
        private readonly InvoiceReminderService $reminders,
    ) {}

    /** @return array{enabled:bool,before_days:int,on_due:bool,after_interval:int,after_max:int} */
    public function settings(): array
    {
        return [
            'enabled' => AppSetting::valueFor('reminders_automated_enabled', '0') === '1',
            'before_days' => (int) AppSetting::valueFor('reminders_before_days', '3'),
            'on_due' => AppSetting::valueFor('reminders_on_due', '1') === '1',
            'after_interval' => max(1, (int) AppSetting::valueFor('reminders_after_interval', '7')),
            'after_max' => max(0, (int) AppSetting::valueFor('reminders_after_max', '3')),
        ];
    }

    /** @return Collection<int, array{invoice:Invoice,trigger_type:string,send_date:Carbon}> */
    public function eligible(?Carbon $date = null, bool $includeFuture = false): Collection
    {
        $date ??= Carbon::today();
        $settings = $this->settings();
        if (! $settings['enabled'] && ! $includeFuture) {
            return collect();
        }
        return Invoice::query()
            ->with('paymentPlan.memberships.client')
            ->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])
            ->whereHas('paymentPlan', fn ($query) => $query->where('automated_reminders_enabled', true)->where('status', 'active'))
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Invoice $invoice) => $this->balances->invoiceBalance($invoice) > 0)
            ->map(function (Invoice $invoice) use ($date, $settings, $includeFuture): ?array {
                $trigger = $this->triggerFor($invoice, $date, $settings, $includeFuture);
                if ($trigger === null) return null;
                if (InvoiceReminder::query()->where('invoice_id', $invoice->id)->where('automated', true)->whereDate('trigger_date', $trigger['send_date'])->where('trigger_type', $trigger['trigger_type'])->exists()) return null;
                return ['invoice' => $invoice, 'trigger_type' => $trigger['trigger_type'], 'send_date' => $trigger['send_date']];
            })->filter()->values();
    }

    /** @return array{sent:int,failed:int,skipped:int} */
    public function run(?Carbon $date = null): array
    {
        if (! $this->settings()['enabled']) return ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($this->eligible($date) as $candidate) {
            try {
                $this->reminders->send($candidate['invoice'], null, true, $candidate['send_date'], $candidate['trigger_type']);
                $result['sent']++;
            } catch (Throwable $exception) {
                report($exception);
                $result['failed']++;
            }
        }
        return $result;
    }

    /** @param array{before_days:int,on_due:bool,after_interval:int,after_max:int} $settings @return array{trigger_type:string,send_date:Carbon}|null */
    private function triggerFor(Invoice $invoice, Carbon $date, array $settings, bool $future): ?array
    {
        $due = $invoice->due_date->copy()->startOfDay();
        if ($future) {
            if ($settings['before_days'] > 0 && $date->lte($due->copy()->subDays($settings['before_days']))) return ['trigger_type' => 'before_due', 'send_date' => $due->copy()->subDays($settings['before_days'])];
            if ($settings['on_due'] && $date->lte($due)) return ['trigger_type' => 'due_date', 'send_date' => $due];
            $next = max(1, $date->diffInDays($due, false) < 0 ? intdiv($due->diffInDays($date), $settings['after_interval']) + 1 : 1);
            if ($next <= $settings['after_max']) return ['trigger_type' => 'past_due_'.$next, 'send_date' => $due->copy()->addDays($next * $settings['after_interval'])];
            return null;
        }
        if ($settings['before_days'] > 0 && $date->isSameDay($due->copy()->subDays($settings['before_days']))) return ['trigger_type' => 'before_due', 'send_date' => $date->copy()];
        if ($settings['on_due'] && $date->isSameDay($due)) return ['trigger_type' => 'due_date', 'send_date' => $date->copy()];
        $late = $due->diffInDays($date, false);
        if ($late > 0 && $late % $settings['after_interval'] === 0 && intdiv($late, $settings['after_interval']) <= $settings['after_max']) return ['trigger_type' => 'past_due_'.intdiv($late, $settings['after_interval']), 'send_date' => $date->copy()];
        return null;
    }
}
