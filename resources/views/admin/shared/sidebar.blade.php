@php($noticeLinkBackground = false)
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
            class="admin-sidebar-link admin-notice-link {{ $noticeLinkBackground ? 'admin-notice-link-background' : '' }} {{ request()->routeIs('admin.notices.*') ? 'active' : '' }}"
            data-admin-notice-link
            href="{{ route('admin.notices.index') }}"
            aria-label="{{ $openAdminNoticeCount }} open administrator {{ Str::plural('notice', $openAdminNoticeCount) }}"
        >
            <span aria-hidden="true">
                <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                    <path d="M10 21h4"></path>
                </svg>
            </span>
            Notices
            <span class="admin-notice-badge {{ $openAdminNoticeCount > 0 ? '' : 'd-none' }}" data-admin-notice-badge>{{ $openAdminNoticeCount }} open</span>
        </a>


    <a
        class="admin-sidebar-link {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}"
        href="{{ route('admin.messages.index') }}"
    >
        <span aria-hidden="true">✉</span>
        Messages
        @php($messageBadge = collect([
            $unreadSecureMessageCount > 0 ? $unreadSecureMessageCount.' unread' : null,
            $starredSecureMessageCount > 0 ? '★ '.$starredSecureMessageCount : null,
        ])->filter()->join(' · '))
        <span class="admin-notice-badge {{$messageBadge === '' ? 'd-none' : ''}}" data-admin-message-badge>{{$messageBadge}}</span>
    </a>

<div style="border-top: 1px solid rgba(255,255,255,0.15); margin: 8px 12px;"></div>

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
        <span class="sidebar-icon" aria-hidden="true">&#9823;</span>
        Clients
    </a>

<a
    class="admin-sidebar-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}"
    href="{{ route('admin.plans.index') }}"
>
    <span aria-hidden="true">▤</span>
    Payment plans
</a>

{{--
    <a
        class="admin-sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
        href="{{ route('admin.settings.index') }}"
    
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
--}}

            <a
            class="admin-sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
            href="{{ route('admin.settings.index') }}"
        >
            <span aria-hidden="true">⚙</span>
            Settings
        </a>

    <a class="admin-sidebar-link admin-sidebar-divider {{ request()->routeIs('admin.documents.*') ? 'active' : '' }}" href="{{ route('admin.documents.index') }}">
        <span aria-hidden="true">&#128196;</span>
        Documents
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
            {{ auth()->user()?->name ?? 'Administrator' }}
            <small>Administrator</small>
        </div>
    </div>

    <div class="admin-sidebar-footer-links">


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
