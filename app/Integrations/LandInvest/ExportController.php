<?php

namespace App\Integrations\LandInvest;

use App\Models\PaymentPlan;
use Illuminate\Http\Response;

class ExportController
{
    public function __invoke(Export $export, ExportConnection $connection): Response
    {
        return response()->view('admin.actions.landinvest-export', $export->snapshot() + [
            'connection' => $connection->metadata(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
