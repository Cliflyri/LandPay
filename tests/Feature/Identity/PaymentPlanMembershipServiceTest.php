<?php

namespace Tests\Feature\Identity;

use App\Models\Client;
use App\Models\PaymentPlan;
use App\Models\User;
use App\Services\PaymentPlanMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentPlanMembershipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_duplicate_active_membership(): void
    {
        [$actor, $client, $plan] = $this->records('draft');
        $service = app(PaymentPlanMembershipService::class);
        $service->add($plan, $client, $actor, 'primary', '2026-07-26');

        $this->expectException(ValidationException::class);
        $service->add($plan, $client, $actor, 'co_client', '2026-07-26');
    }

    public function test_an_active_plan_cannot_be_left_without_a_primary_client(): void
    {
        [$actor, $client, $plan] = $this->records('draft');
        $service = app(PaymentPlanMembershipService::class);
        $membership = $service->add($plan, $client, $actor, 'primary', '2026-07-26');
        $plan->update(['status' => 'active', 'activated_at' => now()]);

        try {
            $service->end($membership, $actor, '2026-07-27', 'Requested change');
            $this->fail('Expected primary-client validation to fail.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('payment_plan_clients', [
                'id' => $membership->id,
                'effective_to' => null,
            ]);
        }
    }

    public function test_an_active_plan_rejects_a_second_primary_client(): void
    {
        [$actor, $client, $plan] = $this->records('draft');
        $service = app(PaymentPlanMembershipService::class);
        $service->add($plan, $client, $actor, 'primary', '2026-07-26');
        $plan->update(['status' => 'active', 'activated_at' => now()]);
        $secondClient = $this->client($actor, 'Second', 'Client');

        $this->expectException(ValidationException::class);
        $service->add($plan->fresh(), $secondClient, $actor, 'primary', '2026-07-26');
    }

    private function records(string $status): array
    {
        $actor = User::factory()->create();
        $client = $this->client($actor, 'Primary', 'Client');
        $plan = PaymentPlan::query()->create([
            'plan_number' => 'LP-1001',
            'title' => 'Test purchase',
            'original_purchase_balance' => 100000,
            'customary_monthly_payment' => 10000,
            'monthly_due_day' => 1,
            'first_due_date' => '2026-08-01',
            'plan_start_date' => '2026-07-26',
            'status' => $status,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);

        return [$actor, $client, $plan];
    }

    private function client(User $actor, string $firstName, string $lastName): Client
    {
        return Client::query()->create([
            'client_type' => 'individual',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);
    }
}