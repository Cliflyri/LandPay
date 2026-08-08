<?php

namespace App\Models;

use App\Enums\OverpaymentDisposition;
use App\Enums\PaymentMethod;
use App\Models\Concerns\IsAppendOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use IsAppendOnly;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'overpayment_disposition' => OverpaymentDisposition::class,
            'received_date' => 'date',
            'decision_selected_at' => 'datetime',
        ];
    }

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
