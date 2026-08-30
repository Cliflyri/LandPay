<?php

namespace Tests\Unit;

use App\Http\Middleware\SquarePaymentContentSecurityPolicy;
use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SquarePaymentContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_is_only_added_for_landpay_square_checkout(): void
    {
        $middleware = app(SquarePaymentContentSecurityPolicy::class);
        $next = fn () => new Response('ok');

        AppSetting::putMany(['card_provider' => 'square', 'square_checkout_experience' => 'hosted']);
        $this->assertFalse($middleware->handle(Request::create('/portal/make-payment'), $next)->headers->has('Content-Security-Policy'));

        AppSetting::putMany(['square_checkout_experience' => 'landpay', 'square_environment' => 'live']);
        $policy = $middleware->handle(Request::create('/portal/make-payment'), $next)->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://web.squarecdn.com', $policy);
        $this->assertStringContainsString('https://pci-connect.squareup.com', $policy);
        $this->assertStringContainsString('nonce-', $policy);
        $this->assertStringNotContainsString('squareupsandbox.com', $policy);
    }
}
