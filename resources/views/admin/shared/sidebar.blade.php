<div class="admin-sidebar-brand">
    <a href="{{ route('admin.dashboard') }}" aria-label="LandPay administrator dashboard">
        <img
            src="{{ asset('images/landpay-logo.png') }}"
            alt="LandPay"
            width="405"
            height="280"
        >
    </a>
</div>

<nav class="admin-sidebar-nav" aria-label="Administrator navigation">
    <a
        class="admin-sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
        href="{{ route('admin.dashboard') }}"
    >
        <span aria-hidden="true">▦</span>
        Dashboard
    </a>

    <a
        class="admin-sidebar-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}"
        href="{{ route('admin.clients.index') }}"
    >
        <span aria-hidden="true">●</span>
        Clients
    </a>

<a
    class="admin-sidebar-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}"
    href="{{ route('admin.plans.index') }}"
>
    <span aria-hidden="true">▤</span>
    Payment plans
</a>

    <a
        class="admin-sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
        href="{{ route('admin.settings.index') }}"
    >
<span aria-hidden="true">
    <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7"></rect>
        <rect x="14" y="3" width="7" height="7"></rect>
        <rect x="3" y="14" width="7" height="7"></rect>
        <rect x="14" y="14" width="7" height="7"></rect>
    </svg>
</span>
        Settings
    </a>
</nav>

<div class="admin-sidebar-footer">
    <div class="admin-sidebar-account">
        <div class="admin-sidebar-avatar" aria-hidden="true">
            {{ str(auth()->user()?->name ?? 'Administrator')
                ->explode(' ')
                ->filter()
                ->take(2)
                ->map(fn ($part) => str($part)->substr(0, 1))
                ->join('') }}
        </div>

        <div class="admin-sidebar-user">
            <strong>{{ auth()->user()?->name ?? 'Administrator' }}</strong>
            <small>Administrator</small>
        </div>
    </div>

    <div class="admin-sidebar-footer-links">
        <a
            class="admin-sidebar-footer-link"
            href="{{ route('admin.settings.index') }}"
        >
            <span aria-hidden="true">⚙</span>
            Settings
        </a>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf

            <button
                class="admin-sidebar-footer-link admin-sidebar-signout"
                type="submit"
            >
                <span aria-hidden="true">↪</span>
                Sign out
            </button>
        </form>
    </div>
</div>