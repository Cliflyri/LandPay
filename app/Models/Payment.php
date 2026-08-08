<?php

namespace App\Models;

use App\Enums\OverpaymentDisposition;
use App\Enums\PaymentAllocationType;
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

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'payer_client_id');
    }

    public function emailDeliveries(): HasMany
    {
        return $this->hasMany(EmailDelivery::class);
    }

    public function purposeLabel(): string
    {
        $this->loadMissing('allocations');

        $hasInvoiceAllocation = $this->allocations->contains(
            fn (PaymentAllocation $allocation): bool => $allocation->allocation_type === PaymentAllocationType::InvoiceItem,
        );
        $hasAdditionalAllocation = $this->allocations->contains(
            fn (PaymentAllocation $allocation): bool => $allocation->allocation_type !== PaymentAllocationType::InvoiceItem,
        );

        return match (true) {
            $hasInvoiceAllocation && $hasAdditionalAllocation => 'Invoice + additional',
            $hasInvoiceAllocation => 'Invoice payment',
            default => 'Additional / principal',
        };
    }

    public function invoiceAllocations(): \Illuminate\Support\Collection
    {
        $this->loadMissing('allocations.invoice');

        return $this->allocations->whereNotNull('invoice_id')->unique('invoice_id')->values();
    }

    public function hasAdditionalAllocation(): bool
    {
        $this->loadMissing('allocations');

        return $this->allocations->contains(
            fn (PaymentAllocation $allocation): bool => $allocation->allocation_type !== PaymentAllocationType::InvoiceItem,
        );
    }
}
