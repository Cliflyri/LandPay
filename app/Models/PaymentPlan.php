<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
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
            'scheduled_invoice_email_enabled' => 'boolean',
            'automated_reminders_enabled' => 'boolean',
            'automatic_invoice_email_enabled' => 'boolean',
            'accelerated_testing_mode' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public static function normalizeAdminStatusFilter(?string $status): string
    {
        return in_array($status, ['active', 'draft', 'terminated', 'closed', 'all'], true) ? $status : 'active';
    }

    public function scopeForAdminListing(Builder $query, ?string $status = 'active', ?string $search = null): Builder
    {
        $status = self::normalizeAdminStatusFilter($status);
        $statuses = match ($status) {
            'active' => ['active', 'paused'],
            'draft' => ['draft'],
            'terminated' => ['terminated'],
            'closed' => ['closed'],
            default => null,
        };
        if ($statuses !== null) $query->whereIn('payment_plans.status', $statuses);
        $terms = preg_split('/\s+/', trim((string) $search), -1, PREG_SPLIT_NO_EMPTY);
        if ($terms === false || $terms === []) return $query;

        foreach ($terms as $term) {
            $like = '%'.$term.'%';
            $query->where(function (Builder $query) use ($like): void {
                $query->where('payment_plans.plan_number', 'like', $like)
                    ->orWhere('payment_plans.apn', 'like', $like)
                    ->orWhereHas('memberships.client', function (Builder $client) use ($like): void {
                        $client->where('organization_name', 'like', $like)
                            ->orWhere('preferred_name', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('middle_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('primary_phone', 'like', $like)
                            ->orWhere('secondary_phone', 'like', $like);
                    });
            });
        }

        return $query;
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
