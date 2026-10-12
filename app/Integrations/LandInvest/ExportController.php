<?php

namespace App\Integrations\LandInvest;

use App\Models\PaymentPlan;
use Illuminate\Http\Response;

class ExportController
{
    public function __invoke(Export $export): Response
    {
        // Include historical/deleted plans so inactive records do not silently disappear.
        $plans = PaymentPlan::withTrashed()->with([
            'closing',
            'memberships' => fn ($query) => $query->where('role', 'primary')->whereNull('effective_to')->orderBy('id')->with('client'),
        ])->orderBy('apn')->orderBy('id')->get();

        return response()->view('admin.actions.landinvest-export', [
            'csv' => $export->csv($plans),
            'planCount' => $plans->count(),
            'missingApns' => $plans->filter(fn ($plan) => trim((string) $plan->apn) === '')->count(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
