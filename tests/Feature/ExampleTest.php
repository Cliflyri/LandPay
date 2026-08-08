<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landpay_homepage_is_available(): void
    {
        AppSetting::putMany(['company_name' => 'Example Plan Company', 'company_phone' => '(555) 867-5309']);
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Your payment plan,')
            ->assertSee('Client portal')
            ->assertSee('&copy; '.date('Y').' Example Plan Company', escape: false)
            ->assertSee('(555) 867-5309')
            ->assertDontSee(route('admin.login'), escape: false)
            ->assertSee('images/landpay-logo.png', escape: false);
    }
}