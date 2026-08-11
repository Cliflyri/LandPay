<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16x16.png') }}">
    <link rel="icon" href="{{ asset('images/favicon/favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('images/favicon/site.webmanifest') }}">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta name="theme-color" content="#173f40">

    <meta
        name="description"
        content="@yield(
            'meta_description',
            'LandPay administrator workspace.'
        )"
    >

    <title>
        @yield('title', config('app.name', 'LandPay'))
    </title>

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous"
    >

    <link
        rel="stylesheet"
        href="{{ asset('assets/css/landpay.css') }}?v={{ filemtime(public_path('assets/css/landpay.css')) }}"
    >

    @stack('styles')
</head>

<body class="admin-layout @yield('body_class')">
<a class="skip-link" href="#main-content">
    Skip to main content
</a>

<div class="admin-shell">
    <aside class="admin-sidebar d-none d-lg-flex">
        @include('admin.shared.sidebar')
    </aside>

    <div
        class="offcanvas offcanvas-start admin-sidebar-offcanvas"
        tabindex="-1"
        id="adminSidebar"
        aria-labelledby="adminSidebarLabel"
    >
        <div class="offcanvas-header">
            <h2 class="visually-hidden" id="adminSidebarLabel">
                Administrator navigation
            </h2>

            <button
                type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="offcanvas"
                aria-label="Close navigation"
            ></button>
        </div>

        <div class="offcanvas-body">
            @include('admin.shared.sidebar')
        </div>
    </div>

    <div class="admin-workspace">
        <header class="admin-topbar">
            <button
                class="btn admin-menu-toggle d-lg-none"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#adminSidebar"
                aria-controls="adminSidebar"
                aria-label="Open administrator navigation"
            >
                ☰
            </button>

            <div class="admin-topbar-context">
                <span>LandPay</span>
                <strong>Administrator</strong>
            </div>

            <div class="admin-topbar-actions">
                <a
                    class="btn btn-sm btn-outline-brand"
                    href="{{ route('admin.dashboard') }}"
                >
                    Dashboard
                </a>
            </div>
        </header>

        <main id="main-content" class="admin-main">
            @yield('content')
        </main>
    </div>
</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"
></script>

<script src="{{ asset('assets/js/landpay.js') }}?v={{ filemtime(public_path('assets/js/landpay.js')) }}"></script>

@stack('scripts')
</body>
</html>