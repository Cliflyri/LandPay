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
        $contractBalance = $this->balances->contractBalance($plan);
        if ($contractBalance <= 0) {
            return 0;
        }

        return $contractBalance + $this->monthlyFees->summaryForMonth($plan, $date ?? now())['remaining'];
    }
}
