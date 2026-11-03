<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentPlanClient;

class LastPaymentMethodService
{
    /** @return array<int, string|null> */
    public function forPlans(array $planIds): array
    {
        return PaymentPlanClient::query()
            ->whereIn('payment_plan_id', $planIds)
            ->where('role', 'primary')->whereNull('effective_to')->whereDate('effective_from', '<=', today())
            ->select('payment_plan_id')
            ->selectSub(
                Payment::query()->select('payment_method')
                    ->whereColumn('payer_client_id', 'payment_plan_clients.client_id')
                    ->whereHas('financialTransaction', fn ($query) => $query->whereDoesntHave('reversedBy'))
                    ->newestFirst()->limit(1),
                'last_payment_method',
            )->get()->pluck('last_payment_method', 'payment_plan_id')->all();
    }
}
