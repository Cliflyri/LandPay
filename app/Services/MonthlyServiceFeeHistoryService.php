<?php

namespace App\Services;

use App\Models\PaymentPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyServiceFeeHistoryService
{
    public function summaryForMonth(PaymentPlan $plan, Carbon $date): array
    {
        $monthStart = $date->copy()->startOfMonth()->toDateString();
        $monthEnd = $date->copy()->endOfMonth()->toDateString();

        $feeItems = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.payment_plan_id', $plan->id)
            ->whereNotNull('invoices.period_start')
            ->whereNotNull('invoices.period_end')
            ->whereDate('invoices.period_start', '<=', $monthEnd)
            ->whereDate('invoices.period_end', '>=', $monthStart)
            ->where('invoices.status', '!=', 'voided')
            ->whereIn('invoice_items.item_type', ['monthly_service_fee', 'administrative_fee']);

        $itemIds = (clone $feeItems)->pluck('invoice_items.id');
        $hasAssessment = $itemIds->isNotEmpty();
        $assessed = (int) (clone $feeItems)->sum('invoice_items.amount');

        if ($hasAssessment) {
            $remaining = max(0, (int) DB::table('transaction_effects')
                ->whereIn('invoice_item_id', $itemIds)
                ->where('effect_type', 'invoice_due')
                ->sum('amount_delta'));
        } else {
            $terms = $plan->billingTerms()
                ->whereDate('effective_from', '<=', $monthEnd)
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $monthStart))
                ->latest('effective_from')
                ->first();
            $assessed = (int) ($terms?->monthly_service_fee ?? $plan->monthly_service_fee ?? 0);
            $remaining = $assessed;
        }

        $entries = DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->join('financial_transactions', 'financial_transactions.id', '=', 'payments.financial_transaction_id')
            ->join('invoice_items', 'invoice_items.id', '=', 'payment_allocations.invoice_item_id')
            ->join('invoices', 'invoices.id', '=', 'payment_allocations.invoice_id')
            ->where('financial_transactions.payment_plan_id', $plan->id)
            ->whereIn('invoice_items.item_type', ['monthly_service_fee', 'administrative_fee'])
            ->whereNotNull('invoices.period_start')
            ->whereNotNull('invoices.period_end')
            ->whereDate('invoices.period_start', '<=', $monthEnd)
            ->whereDate('invoices.period_end', '>=', $monthStart)
            ->where('invoices.status', '!=', 'voided')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('financial_transactions as reversals')->whereColumn('reversals.reversal_of_transaction_id', 'financial_transactions.id'))
            ->orderBy('payments.received_date')
            ->orderBy('payments.id')
            ->select(['payments.id as payment_id', 'payments.received_date', 'invoices.id as invoice_id', 'invoices.invoice_number', 'payment_allocations.amount'])
            ->get();

        $directEntries = DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->join('financial_transactions', 'financial_transactions.id', '=', 'payments.financial_transaction_id')
            ->where('financial_transactions.payment_plan_id', $plan->id)
            ->where('payment_allocations.allocation_type', 'service_fee')
            ->whereDate('payment_allocations.billing_month', $monthStart)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('financial_transactions as reversals')->whereColumn('reversals.reversal_of_transaction_id', 'financial_transactions.id'))
            ->orderBy('payments.received_date')
            ->orderBy('payments.id')
            ->select(['payments.id as payment_id', 'payments.received_date', 'payment_allocations.amount'])
            ->get()
            ->each(function ($entry): void {
                $entry->invoice_id = null;
                $entry->invoice_number = null;
            });
        $directApplied = (int) $directEntries->sum('amount');
        $remaining = max(0, $remaining - $directApplied);
        $entries = $entries->concat($directEntries)->sortBy(['received_date', 'payment_id'])->values();

        $applied = max(0, $assessed - $remaining);

        $satisfaction = DB::table('monthly_service_fee_satisfactions')
            ->leftJoin('users as created_by', 'created_by.id', '=', 'monthly_service_fee_satisfactions.created_by_user_id')
            ->where('monthly_service_fee_satisfactions.payment_plan_id', $plan->id)
            ->whereDate('monthly_service_fee_satisfactions.billing_month', $monthStart)
            ->whereNull('monthly_service_fee_satisfactions.revoked_at')
            ->select(['monthly_service_fee_satisfactions.id', 'monthly_service_fee_satisfactions.note', 'monthly_service_fee_satisfactions.billing_month', 'created_by.name as created_by_name', 'monthly_service_fee_satisfactions.created_at'])
            ->first();

        if ($satisfaction !== null) {
            $remaining = 0;
        }

        return [
            'monthLabel' => $date->format('F Y'),
            'monthValue' => $date->format('Y-m'),
            'assessed' => $assessed,
            'total' => $applied,
            'remaining' => $remaining,
            'count' => $entries->count(),
            'entries' => $entries,
            'hasAssessment' => $hasAssessment,
            'satisfaction' => $satisfaction,
        ];
    }
}
