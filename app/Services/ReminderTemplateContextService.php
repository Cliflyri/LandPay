<?php

namespace App\Services;

use App\Enums\InvoiceItemType;
use App\Enums\LateFeeType;
use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Support\Carbon;

class ReminderTemplateContextService
{
    /** @return array<string, string> */
    public function for(Invoice $invoice, Carbon $reminderDate, ?string $triggerType): array
    {
        $invoice->loadMissing(['billingTerms', 'items']);
        $terms = $invoice->billingTerms ?? $invoice->paymentPlan->currentBillingTerms;
        $days = $invoice->due_date->copy()->startOfDay()->diffInDays($reminderDate->copy()->startOfDay(), false);
        $lateItems = $invoice->items->whereIn('item_type', [
            InvoiceItemType::LateFeeStageOne,
            InvoiceItemType::LateFeeStageTwo,
        ]);
        $lateTotal = (int) $lateItems->sum('amount');
        $next = $this->nextFee($invoice, $terms, $reminderDate);

        return [
            'reminder_date' => $reminderDate->format('F j, Y'),
            'days_until_due' => $days < 0 ? (string) abs($days) : '',
            'days_past_due' => $days > 0 ? (string) $days : '',
            'grace_period_end_date' => $terms ? $invoice->due_date->copy()->addDays((int) $terms->grace_days)->format('F j, Y') : '',
            'next_late_fee_date' => $next['date'] ?? '',
            'next_late_fee_amount' => $next['amount'] ?? '',
            'next_late_fee_description' => $next['description'] ?? '',
            'late_fees_assessed' => $lateTotal > 0 ? 'Late fees totaling '.Money::format($lateTotal).' have already been added to this invoice.' : '',
            'past_due_reminder_number' => preg_match('/^past_due_(\d+)$/', (string) $triggerType, $match) ? $match[1] : '',
        ];
    }

    /** @return array{date:string,amount:string,description:string}|array{} */
    private function nextFee(Invoice $invoice, $terms, Carbon $reminderDate): array
    {
        if (! $terms) {
            return [];
        }
        foreach ([1, 2] as $stage) {
            $prefix = $stage === 1 ? 'stage_one' : 'stage_two';
            $itemType = $stage === 1 ? InvoiceItemType::LateFeeStageOne : InvoiceItemType::LateFeeStageTwo;
            $date = $invoice->due_date->copy()->addDays((int) $terms->{$prefix.'_days_late'});
            if (! $terms->{$prefix.'_enabled'} || $invoice->items->contains('item_type', $itemType) || $date->lte($reminderDate)) {
                continue;
            }
            $type = $terms->{$prefix.'_fee_type'};
            $base = (int) $invoice->items->where('item_type', InvoiceItemType::ScheduledPurchasePayment)->sum('amount');
            $amount = $type === LateFeeType::Fixed
                ? (int) $terms->{$prefix.'_fixed_amount'}
                : max((int) round($base * (float) $terms->{$prefix.'_percentage_rate'} / 100), (int) $terms->{$prefix.'_minimum_amount'});
            $formatted = Money::format($amount);
            $description = $type === LateFeeType::Percentage
                ? $terms->{$prefix.'_percentage_rate'}.'% late fee, currently estimated at '.$formatted.', is scheduled for '.$date->format('F j, Y').'.'
                : 'A '.$formatted.' late fee is scheduled for '.$date->format('F j, Y').'.';

            return ['date' => $date->format('F j, Y'), 'amount' => $formatted, 'description' => $description];
        }

        return [];
    }
}
