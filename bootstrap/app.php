<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('portal', 'portal/*') ? route('portal.login') : route('admin.login')
        );
        $middleware->redirectUsersTo(
            fn (Request $request): string => $request->is('portal', 'portal/*') ? route('portal.dashboard') : route('admin.dashboard')
        );
        $middleware->alias(['portal.read-only' => \App\Http\Middleware\EnsurePortalImpersonationIsReadOnly::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*'),
        );
    })->create();