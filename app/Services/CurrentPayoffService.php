<?php

namespace App\Services;

use App\Models\PaymentPlan;
use Illuminate\Support\Carbon;

class CurrentPayoffService
{
    public function __construct(
        private readonly FinancialBalanceService $balances,
        private readonly MonthlyServiceFeeHistoryService $monthlyFees,
    ) {}

    public function amount(PaymentPlan $plan, ?Carbon $date = null): int
    {
        return $this->balances->contractBalance($plan)
            + $this->monthlyFees->summaryForMonth($plan, $date ?? now())['remaining'];
    }
}
