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

        $entries = DB::table('payment_allocations')
            ->join(
                'payments',
                'payments.id',
                '=',
                'payment_allocations.payment_id'
            )
            ->join(
                'financial_transactions',
                'financial_transactions.id',
                '=',
                'payments.financial_transaction_id'
            )
            ->join(
                'invoice_items',
                'invoice_items.id',
                '=',
                'payment_allocations.invoice_item_id'
            )
            ->join(
                'invoices',
                'invoices.id',
                '=',
                'payment_allocations.invoice_id'
            )
            ->where(
                'financial_transactions.payment_plan_id',
                $plan->id
            )
            ->whereIn('invoice_items.item_type', [
                'monthly_service_fee',
                'administrative_fee',
            ])
            ->whereBetween(
                'payments.received_date',
                [$monthStart, $monthEnd]
            )
            ->whereNotExists(
                fn ($query) => $query
                    ->selectRaw('1')
                    ->from('financial_transactions as reversals')
                    ->whereColumn(
                        'reversals.reversal_of_transaction_id',
                        'financial_transactions.id'
                    )
            )
            ->orderBy('payments.received_date')
            ->orderBy('payments.id')
            ->select([
                'payments.id as payment_id',
                'payments.received_date',
                'invoices.id as invoice_id',
                'invoices.invoice_number',
                'payment_allocations.amount',
            ])
            ->get();

        return [
            'monthLabel' => $date->format('F Y'),
            'total' => (int) $entries->sum('amount'),
            'count' => $entries->count(),
            'entries' => $entries,
        ];
    }
}