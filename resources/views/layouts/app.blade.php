<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16x16.png') }}">
    <link rel="icon" href="{{ asset('images/favicon/favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('images/favicon/site.webmanifest') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#173f40">
    <meta name="description" content="@yield('meta_description', 'LandPay makes private payment plans simple, transparent, and easy to manage.')">
    <title>@yield('title', config('app.name', 'LandPay'))</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('assets/css/landpay.css') }}?v={{ filemtime(public_path('assets/css/landpay.css')) }}">
    @stack('styles')
</head>
<body class="@yield('body_class') {{ request()->routeIs('portal.*', 'secure-invoice.*') ? 'portal-page' : '' }}">
<a class="skip-link" href="#main-content">Skip to main content</a>
@if(session('portal_impersonation'))<div class="portal-admin-banner" role="status"><span><strong>Administrator view:</strong> Viewing the client portal for {{session('portal_impersonation.client_name')}} in read-only mode.</span><form method="post" action="{{route('admin.portal-access.destroy')}}">@csrf @method('DELETE')<button class="btn btn-sm btn-light" type="submit">Return to administration</button></form></div>@endif
<header class="site-header" data-site-header>
    <nav class="navbar navbar-expand-lg" aria-label="Primary navigation">
        <div class="container site-container">
            <a class="navbar-brand" href="{{ url('/') }}" aria-label="LandPay home">
                <img src="{{ asset('images/landpay-logo.png') }}" alt="LandPay" width="405" height="280">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landpayNavigation" aria-controls="landpayNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="landpayNavigation">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="{{ url('/#how-it-works') }}">How it works</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/#features') }}">What you can do</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/#help') }}">Get help</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-outline-brand" href="{{ auth('client')->check() ? route('portal.dashboard') : route('portal.login') }}">Client portal</a></li>
                    @if(request()->is('admin', 'admin/*') && auth()->check())
                        <li class="nav-item ms-lg-2 mt-2 mt-lg-0"><a class="btn btn-brand" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
</header>
<main id="main-content">
    @yield('content')
</main>
<footer class="site-footer" id="help">
    <div class="container site-container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-5">
                <img class="footer-logo" src="{{ asset('images/landpay-logo.png') }}" alt="LandPay" width="405" height="280">
                @if($footerCompanyPhone = \App\Models\AppSetting::valueFor('company_phone'))
                    <p class="footer-copy mb-1">{{$footerCompanyPhone}}</p>
                @endif
                <p class="footer-copy mb-0">Private payment plans, managed with clarity and care.</p>
            </div>
            <div class="col-lg-4">
                <p class="footer-label">Need help with your payment plan?</p>
                <p class="mb-0">Contact your plan administrator using the information on your latest statement.</p>
            </div>
            <div class="col-lg-3 text-lg-end">
                <a class="footer-link" href="{{ url('/#portal') }}">Client portal</a>
                <p class="footer-legal mb-0">&copy; {{ date('Y') }} {{ \App\Models\AppSetting::valueFor('company_name', config('app.name', 'LandPay')) }}</p>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="{{ asset('assets/js/landpay.js') }}"></script>
@stack('scripts')
</body>
</html>