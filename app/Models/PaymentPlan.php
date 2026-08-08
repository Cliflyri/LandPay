<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
}
