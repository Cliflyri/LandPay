<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalAccountIsEnabled
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $account = Auth::guard('client')->user();
        if ($account !== null && ! $account->enabled) {
            Auth::guard('client')->logout();
            $request->session()->forget('portal_impersonation');

            return redirect()->route('portal.login')
                ->with('status', 'Portal access has been revoked. Please contact the administrator.');
        }

        return $next($request);
    }
}