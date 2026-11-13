<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | NYFEW MakeMe Live</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/makeme-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/makeme-admin-phase2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/makeme-admin-navigation.css') }}">

    <link rel="icon"
        type="image/x-icon"
        href="{{ asset('images/makeme-favicon.ico') }}">

        @stack('styles')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="portalSidebar" aria-label="Main navigation">
        <a class="sidebar-brand" href="{{ route('admin.makeme.dashboard') }}">
            <img src="{{ asset('images/nyfew-logo.jpeg') }}" alt="NYFEW Project MakeMe Live">
            <span><strong>PROJECT MAKEME</strong><small>LIVE SHOW · ADMIN</small></span>
        </a>
        <div class="nav-caption">MANAGEMENT</div>
        
        <nav class="side-nav">

            @php
                $admin = auth()->user();

                $isActiveAdmin = $admin
                    && (int) $admin->user_type === 1
                    && (int) $admin->user_status === 1
                    && $admin->applicant_type !== 'WAITLIST';

                $isSuperAdmin = $isActiveAdmin
                    && $admin->hasRole('Super Admin');

                $canAccess = function (string $permission) use (
                    $admin,
                    $isActiveAdmin,
                    $isSuperAdmin
                ): bool {
                    return $isActiveAdmin && (
                        $isSuperAdmin ||
                        $admin->can($permission)
                    );
                };
            @endphp

            @if($canAccess('makeme.dashboard.view'))
                <a class="nav-link {{ request()->routeIs('admin.makeme.dashboard') ? 'is-active' : '' }}"
                href="{{ route('admin.makeme.dashboard') }}">
                    <span class="nav-icon">◫</span>
                    Overview
                </a>
            @endif

            @if($canAccess('makeme.participants.view'))
                <a class="nav-link {{ request()->routeIs('admin.makeme.participants.*') ? 'is-active' : '' }}"
                href="{{ route('admin.makeme.participants.index') }}">
                    <span class="nav-icon">◎</span>
                    Participants
                </a>
            @endif

            @if($canAccess('makeme.products.view'))
                <a class="nav-link {{ request()->routeIs('admin.makeme.products.*') ? 'is-active' : '' }}"
                href="{{ route('admin.makeme.products.index') }}">
                    <span class="nav-icon">▦</span>
                    Products &amp; Stock
                </a>
            @endif

            @if($canAccess('makeme.wallets.view'))
                <a class="nav-link {{ request()->routeIs('admin.makeme.wallets.*') ? 'is-active' : '' }}"
                href="{{ route('admin.makeme.wallets.index') }}">
                    <span class="nav-icon">◈</span>
                    Kolo Money
                </a>
            @endif

            @if($canAccess('makeme.orders.view'))
                <a class="nav-link {{ request()->routeIs('admin.makeme.orders.*') ? 'is-active' : '' }}"
                href="{{ route('admin.makeme.orders.index') }}">
                    <span class="nav-icon">▤</span>
                    Orders
                </a>
            @endif

            @if($canAccess('makeme.reports.view'))
                <a class="nav-link {{ request()->routeIs('admin.makeme.reports.*') ? 'is-active' : '' }}"
                href="{{ route('admin.makeme.reports.index') }}">
                    <span class="nav-icon">▥</span>
                    Reports
                </a>
            @endif

            @if($isSuperAdmin)
                <div class="nav-caption">
                    SYSTEM ADMINISTRATION
                </div>

                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}"
                href="{{ route('admin.users.index') }}">
                    <span class="nav-icon">♙</span>
                    Admin Users
                </a>

                <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}"
                href="{{ route('admin.roles.index') }}">
                    <span class="nav-icon">⚿</span>
                    Roles &amp; Permissions
                </a>
            @endif

        </nav>

        <div class="sidebar-bottom"><span class="live-dot"></span> NYFEW · Reality TV Show</div>
    </aside>
    <div class="sidebar-shade" id="sidebarShade"></div>
    <div class="main-area">
        <header class="topbar">
            <div class="topbar-start">
                <button type="button" class="icon-button menu-button" id="menuToggle" aria-label="Open navigation" aria-controls="portalSidebar" aria-expanded="false">☰</button>
                <span class="eyebrow">PROJECT MAKEME LIVE</span>
            </div>
            
            <div class="makeme-admin-profile">

                <button type="button"
                        class="makeme-profile-trigger"
                        id="makemeProfileTrigger"
                        aria-expanded="false"
                        aria-controls="makemeProfileDropdown">

                    <span class="makeme-profile-avatar">
                        {{ strtoupper(substr(auth()->user()->first_name ?? 'A', 0, 1)) }}
                    </span>

                    <span class="makeme-profile-info">
                        <strong>
                            {{ auth()->user()->first_name ?? 'Administrator' }}
                        </strong>
                        <small>
                            {{ auth()->user()->getRoleNames()->first() ?? 'Administrator' }}
                        </small>
                    </span>

                    <span class="makeme-dropdown-arrow">⌄</span>
                </button>

                <div class="makeme-profile-dropdown"
                    id="makemeProfileDropdown">

                    <div class="makeme-dropdown-header">
                        <strong>
                            {{ auth()->user()->first_name ?? 'Administrator' }}
                            {{ auth()->user()->surname ?? '' }}
                        </strong>
                        <small>{{ auth()->user()->email }}</small>
                    </div>

                    <div class="makeme-dropdown-divider"></div>

                    <form action="{{ route('makeme.logout') }}"
                        method="POST">
                        @csrf

                        <button type="submit"
                                class="makeme-dropdown-logout">
                            <span>↪</span>
                            Sign Out
                        </button>
                    </form>

                </div>
            </div>

        </header>
        <main class="page-content">@yield('content')</main>
    </div>
</div>
<script>
    (() => {
        const sidebar = document.getElementById('portalSidebar');
        const shade = document.getElementById('sidebarShade');
        const toggle = document.getElementById('menuToggle');
        function close() { document.body.classList.remove('menu-open'); toggle.setAttribute('aria-expanded', 'false'); }
        toggle.addEventListener('click', () => { const active = document.body.classList.toggle('menu-open'); toggle.setAttribute('aria-expanded', String(active)); });
        shade.addEventListener('click', close);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    })();
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const trigger = document.getElementById('makemeProfileTrigger');
    const dropdown = document.getElementById('makemeProfileDropdown');

    if (!trigger || !dropdown) return;

    function closeDropdown() {
        dropdown.classList.remove('show');
        trigger.setAttribute('aria-expanded', 'false');
    }

    trigger.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = dropdown.classList.toggle('show');
        trigger.setAttribute('aria-expanded', String(isOpen));
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.makeme-admin-profile')) {
            closeDropdown();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDropdown();
        }
    });

});
</script>

@stack('scripts')
</body>
</html>
