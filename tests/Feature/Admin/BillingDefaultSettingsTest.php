<?php

namespace Tests\Feature\Admin;

use App\Models\BillingDefault;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingDefaultSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_defaults_used_by_future_intake_forms(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->put(route('admin.settings.billing.update'), [
            'scheduled_payment_amount' => '500.00', 'monthly_service_fee' => '15.00',
            'due_days_after_issue' => 5, 'grace_days' => 3,
            'stage_one_fee_type' => 'fixed', 'stage_one_fee_value' => '25.00',
            'stage_two_enabled' => '1', 'stage_two_days_late' => 30,
            'stage_two_fee_type' => 'fixed', 'stage_two_fee_value' => '50.00',
            'default_eligibility_days' => 60,
        ])->assertRedirect(route('admin.settings.index', ['section' => 'billing']));

        $defaults = BillingDefault::query()->latest('id')->firstOrFail();
        $this->assertSame(2500, $defaults->stage_one_fixed_amount);
        $this->assertTrue($defaults->stage_two_enabled);
        $this->assertSame(5000, $defaults->stage_two_fixed_amount);
        $this->assertSame(30, $defaults->stage_two_days_late);

        $this->get(route('admin.plans.create'))->assertSee('value="25.00"', false);
        $this->get(route('admin.contract-setups.create'))->assertSee('value="50.00"', false);
    }
}
