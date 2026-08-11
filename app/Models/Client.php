<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasPublicUuid;

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    public function scopeMatchingAdminSearch(Builder $query, ?string $search, bool $showAllPlans = false): Builder
    {
        $terms = preg_split('/\s+/', trim((string) $search), -1, PREG_SPLIT_NO_EMPTY);
        if ($terms === false || $terms === []) return $query;

        foreach ($terms as $term) {
            $like = '%'.$term.'%';
            $query->where(function (Builder $query) use ($like, $showAllPlans): void {
                $query->where('organization_name', 'like', $like)
                    ->orWhere('preferred_name', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('middle_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('primary_phone', 'like', $like)
                    ->orWhere('secondary_phone', 'like', $like)
                    ->orWhereHas('memberships', function (Builder $membership) use ($like, $showAllPlans): void {
                        $membership->whereNull('effective_to')->whereHas('paymentPlan', function (Builder $plan) use ($like, $showAllPlans): void {
                            $plan->when(! $showAllPlans, fn (Builder $plan) => $plan->whereNotIn('status', ['closed', 'terminated']))
                                ->where(fn (Builder $plan) => $plan->where('apn', 'like', $like)->orWhere('plan_number', 'like', $like));
                        });
                    });
            });
        }

        return $query;
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(PaymentPlanClient::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function portalAccount(): HasOne
    {
        return $this->hasOne(PortalAccount::class);
    }

    public function portalInvitations(): HasMany
    {
        return $this->hasMany(PortalInvitation::class);
    }
}