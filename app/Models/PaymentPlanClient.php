<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlanClient extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'receives_invoices' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'contact_risk_acknowledged_at' => 'datetime',
        ];
    }

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}