<?php

namespace App\Integrations\LandInvest;

use App\Models\PaymentPlan;
use App\Services\FinancialBalanceService;

class Export
{
    public function __construct(private readonly FinancialBalanceService $balances) {}

    public function status(PaymentPlan $plan): string
    {
        if ($plan->trashed() || ! in_array($plan->status, ['active', 'paused', 'closed'], true)) {
            return 'no_active_contract';
        }
        if ($plan->closing?->status === 'completed') {
            return 'sale_completed';
        }
        if ($this->balances->contractBalance($plan) <= 0) {
            return 'paid_off';
        }
        return match ($plan->status) {
            'active' => 'under_contract',
            'paused' => 'paused',
            default => 'no_active_contract',
        };
    }

    public function csv(iterable $plans): string
    {
        $stream = fopen('php://temp', 'w+');
        try {
            fputcsv($stream, ['apn', 'county', 'contract_status', 'plan_reference', 'primary_client_name'], ',', '"', '', "\r\n");
            foreach ($plans as $plan) {
                $client = $plan->memberships->firstWhere('role', 'primary')?->client;
                $name = $client ? (trim((string) $client->organization_name) ?: trim($client->first_name.' '.$client->last_name)) : '';
                fputcsv($stream, [(string) $plan->apn, (string) $plan->property_county, $this->status($plan), (string) $plan->getRouteKey(), $name], ',', '"', '', "\r\n");
            }
            rewind($stream);
            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }
}
