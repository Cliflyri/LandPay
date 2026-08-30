<?php

namespace Tests\Feature\Admin;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SquarePaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_cap_is_saved_as_no_cap_without_blocking_other_fee_settings(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        AppSetting::putEncrypted('square_api_secret', 'secret');

        $this->actingAs($admin)->put(route('admin.payment-methods.provider.update', 'square'), [
            'environment' => 'live',
            'public_id' => 'LOCATION',
            'application_id' => 'APPLICATION',
            'checkout_experience' => 'hosted',
            'processing_fee_enabled' => '0',
            'processing_fee_percent' => '2.90',
            'processing_fee_amount' => '0.30',
            'processing_fee_cap' => '0.00',
            'processing_fee_adjust' => '1',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('', AppSetting::valueFor('square_processing_fee_cap'));
        $this->assertSame('2.90', AppSetting::valueFor('square_processing_fee_percent'));
        $this->assertSame('30', AppSetting::valueFor('square_processing_fee_amount'));
        $this->assertSame('1', AppSetting::valueFor('square_processing_fee_adjust'));
    }
}
