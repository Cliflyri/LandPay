<?php

namespace App\Models;

use App\Enums\BillingFrequency;
use App\Enums\LateFeeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlanBillingTerm extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'frequency' => BillingFrequency::class,
            'stage_one_fee_type' => LateFeeType::class,
            'stage_two_fee_type' => LateFeeType::class,
            'stage_one_enabled' => 'boolean',
            'stage_two_enabled' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }
}
