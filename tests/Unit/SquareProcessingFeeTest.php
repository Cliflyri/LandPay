<?php

namespace Tests\Unit;

use App\Models\AppSetting;
use App\Services\SquareProcessingFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SquareProcessingFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_fee_applies_only_to_credit_and_can_be_grossed_up(): void
    {
        AppSetting::putMany([
            'square_checkout_experience' => 'landpay',
            'square_processing_fee_enabled' => '1',
            'square_processing_fee_percent' => '2.9',
            'square_processing_fee_amount' => '30',
            'square_processing_fee_cap' => '',
            'square_processing_fee_adjust' => '0',
        ]);
        $fees = app(SquareProcessingFee::class);

        $this->assertSame(320, $fees->calculate(10000, 'CREDIT'));
        $this->assertSame(0, $fees->calculate(10000, 'DEBIT'));

        AppSetting::putMany(['square_processing_fee_adjust' => '1']);
        $this->assertSame(330, $fees->calculate(10000, 'CREDIT'));

        AppSetting::putMany(['square_processing_fee_cap' => '250']);
        $this->assertSame(250, $fees->calculate(10000, 'CREDIT'));

        AppSetting::putMany(['square_processing_fee_enabled' => '0']);
        $this->assertSame(0, $fees->calculate(10000, 'CREDIT'));
    }
}
