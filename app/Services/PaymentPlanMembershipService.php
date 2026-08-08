<?php

namespace App\Services;

use App\Models\Client;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanClient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentPlanMembershipService
{
    public function add(
        PaymentPlan $plan,
        Client $client,
        User $actor,
        string $role,
        Carbon|string $effectiveFrom,
        bool $receivesInvoices = true,
        ?string $contactRiskAcknowledgmentMethod = null,
    ): PaymentPlanClient {
        return DB::transaction(function () use ($plan, $client, $actor, $role, $effectiveFrom, $receivesInvoices, $contactRiskAcknowledgmentMethod) {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->getKey());
            $this->validateRole($role);

            $memberships = PaymentPlanClient::query()
                ->where('payment_plan_id', $lockedPlan->id)
                ->whereNull('effective_to')
                ->lockForUpdate();

            if ((clone $memberships)->where('client_id', $client->id)->exists()) {
                throw ValidationException::withMessages(['client_id' => 'This client already has an active membership on the payment plan.']);
            }

            if ($role === 'primary' && (clone $memberships)->where('role', 'primary')->exists()) {
                throw ValidationException::withMessages(['role' => 'The payment plan already has an active primary client.']);
            }

            $membership = PaymentPlanClient::query()->create([
                'payment_plan_id' => $lockedPlan->id,
                'client_id' => $client->id,
                'role' => $role,
                'responsibility' => 'joint',
                'receives_invoices' => $receivesInvoices,
                'effective_from' => $effectiveFrom,
                'contact_risk_acknowledged_at' => $contactRiskAcknowledgmentMethod ? now() : null,
                'contact_risk_acknowledgment_method' => $contactRiskAcknowledgmentMethod,
                'created_by_user_id' => $actor->id,
            ]);

            $this->assertActivePlanHasOnePrimary($lockedPlan);

            return $membership;
        });
    }

    public function end(PaymentPlanClient $membership, User $actor, Carbon|string $effectiveTo, string $reason): void
    {
        DB::transaction(function () use ($membership, $actor, $effectiveTo, $reason): void {
            $plan = PaymentPlan::query()->lockForUpdate()->findOrFail($membership->payment_plan_id);
            $lockedMembership = PaymentPlanClient::query()->lockForUpdate()->findOrFail($membership->id);

            if ($lockedMembership->effective_to !== null) {
                throw ValidationException::withMessages(['membership' => 'This membership has already ended.']);
            }

            $lockedMembership->update([
                'effective_to' => $effectiveTo,
                'end_reason' => trim($reason),
                'ended_by_user_id' => $actor->id,
            ]);

            $this->assertActivePlanHasOnePrimary($plan);
        });
    }

    private function assertActivePlanHasOnePrimary(PaymentPlan $plan): void
    {
        if ($plan->status !== 'active') {
            return;
        }

        $count = PaymentPlanClient::query()
            ->where('payment_plan_id', $plan->id)
            ->where('role', 'primary')
            ->whereNull('effective_to')
            ->count();

        if ($count !== 1) {
            throw ValidationException::withMessages(['role' => 'An active payment plan must have exactly one active primary client.']);
        }
    }

    private function validateRole(string $role): void
    {
        if (! in_array($role, ['primary', 'co_client'], true)) {
            throw ValidationException::withMessages(['role' => 'Role must be primary or co_client.']);
        }
    }
}