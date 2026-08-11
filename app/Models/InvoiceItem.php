<?php

namespace App\Models;

use App\Enums\InvoiceItemType;
use App\Models\Concerns\IsAppendOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use IsAppendOnly;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['item_type' => InvoiceItemType::class, 'waived_at' => 'datetime', 'retired_at' => 'datetime'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function sourceTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'source_transaction_id');
    }
}
