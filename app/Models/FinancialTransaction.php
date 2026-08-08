<?php

namespace App\Models;

use App\Enums\FinancialActorType;
use App\Enums\FinancialTransactionType;
use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\IsAppendOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinancialTransaction extends Model
{
    use HasPublicUuid, IsAppendOnly;

    public const UPDATED_AT = null;

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return [
            'type' => FinancialTransactionType::class,
            'actor_type' => FinancialActorType::class,
            'effective_date' => 'date',
            'posted_at' => 'datetime',
            'authorized_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function effects(): HasMany
    {
        return $this->hasMany(TransactionEffect::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_transaction_id');
    }

    public function reversedBy(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_transaction_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
