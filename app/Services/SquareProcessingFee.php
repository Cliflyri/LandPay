<?php

namespace App\Services;

use App\Models\AppSetting;

class SquareProcessingFee
{
    public function clientConfiguration(): array
    {
        return [
            'experience' => AppSetting::valueFor('square_checkout_experience', 'hosted'),
            'application_id' => AppSetting::valueFor('square_application_id', ''),
            'location_id' => AppSetting::valueFor('square_public_id', ''),
            'environment' => AppSetting::valueFor('square_environment', 'sandbox'),
            'enabled' => AppSetting::valueFor('square_processing_fee_enabled', '0') === '1',
            'basis_points' => (int) round((float) AppSetting::valueFor('square_processing_fee_percent', '0') * 100),
            'fixed_amount' => max(0, (int) AppSetting::valueFor('square_processing_fee_amount', '0')),
            'cap' => filled(AppSetting::valueFor('square_processing_fee_cap', '')) ? (int) AppSetting::valueFor('square_processing_fee_cap') : null,
            'adjust' => AppSetting::valueFor('square_processing_fee_adjust', '0') === '1',
        ];
    }

    public function calculate(int $baseAmount, string $cardType): int
    {
        if (AppSetting::valueFor('square_checkout_experience', 'hosted') !== 'landpay'
            || AppSetting::valueFor('square_processing_fee_enabled', '0') !== '1'
            || strtoupper($cardType) !== 'CREDIT') {
            return 0;
        }

        $basisPoints = (int) round((float) AppSetting::valueFor('square_processing_fee_percent', '0') * 100);
        $fixed = max(0, (int) AppSetting::valueFor('square_processing_fee_amount', '0'));
        $adjust = AppSetting::valueFor('square_processing_fee_adjust', '0') === '1';
        $fee = $adjust && $basisPoints < 10000
            ? (int) ceil((($baseAmount + $fixed) * 10000) / (10000 - $basisPoints)) - $baseAmount
            : (int) round(($baseAmount * $basisPoints) / 10000) + $fixed;
        $cap = AppSetting::valueFor('square_processing_fee_cap', '');

        return max(0, filled($cap) ? min($fee, (int) $cap) : $fee);
    }
}
