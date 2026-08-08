<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlan extends Model
{
    use HasPublicUuid;

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return [
            'first_due_date' => 'date',
            'plan_start_date' => 'date',
            'maturity_date' => 'date',
            'activated_at' => 'datetime',
            'automated_reminders_enabled' => 'boolean',
            'automatic_invoice_email_enabled' => 'boolean',
            'accelerated_testing_mode' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(PaymentPlanClient::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function currentBillingTerms(): HasOne
    {
        return $this->hasOne(PaymentPlanBillingTerm::class)->whereNull('effective_to')->latestOfMany();
    }

    public function billingTerms(): HasMany
    {
        return $this->hasMany(PaymentPlanBillingTerm::class);
    }

    public function pauses(): HasMany { return $this->hasMany(PaymentPlanPause::class); }
    public function currentPause(): HasOne { return $this->hasOne(PaymentPlanPause::class)->whereNull('resume_date')->latestOfMany(); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by_user_id'); }
}
