<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalImpersonationIsReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('portal_impersonation') && ! $request->isMethodSafe()) {
            abort(403, 'Administrator portal access is read-only.');
        }

        return $next($request);
    }
}
