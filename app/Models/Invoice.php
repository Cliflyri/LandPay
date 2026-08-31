<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasPublicUuid;

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'first_viewed_at' => 'datetime',
            'operationally_closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }
    public function billingTerms(): BelongsTo { return $this->belongsTo(PaymentPlanBillingTerm::class,'payment_plan_billing_term_id'); }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->whereNull('retired_at');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(InvoiceReminder::class);
    }
    public function emailDeliveries(): HasMany { return $this->hasMany(EmailDelivery::class); }
    public function accessLink(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(InvoiceAccessLink::class); }
}
