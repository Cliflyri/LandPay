<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SquarePaymentContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AppSetting::valueFor('card_provider', 'disabled') !== 'square'
            || AppSetting::valueFor('square_checkout_experience', 'hosted') !== 'landpay') {
            return $next($request);
        }

        $nonce = base64_encode(random_bytes(18));
        view()->share('cspNonce', $nonce);
        $sandbox = AppSetting::valueFor('square_environment', 'sandbox') !== 'live';
        $sdk = $sandbox ? 'https://sandbox.web.squarecdn.com' : 'https://web.squarecdn.com';
        $pci = $sandbox ? 'https://pci-connect.squareupsandbox.com' : 'https://pci-connect.squareup.com';
        $policy = implode('; ', [
            'default-src \'self\'',
            'script-src \'self\' \'nonce-'.$nonce.'\' https://cdn.jsdelivr.net '.$sdk,
            'style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net '.$sdk,
            'frame-src \'self\' '.$sdk,
            'connect-src \'self\' https://cdn.jsdelivr.net '.$sdk.' '.$pci.' https://o160250.ingest.sentry.io',
            'font-src \'self\' data: https://square-fonts-production-f.squarecdn.com https://d1g145x70srn7h.cloudfront.net',
            'img-src \'self\' data: https:',
            'form-action \'self\'',
            'base-uri \'self\'',
            'object-src \'none\'',
            'frame-ancestors \'self\'',
        ]);

        $response = $next($request);
        $response->headers->set('Content-Security-Policy', $policy);

        return $response;
    }
}
