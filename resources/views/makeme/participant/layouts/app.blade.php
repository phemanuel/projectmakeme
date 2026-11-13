<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#25142f">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Contestant Portal | Project MakeMe Live')</title>
    <link rel="stylesheet" href="{{ asset('css/makeme-participant-app.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/nyfew-participant-theme.css') }}?v=2">
</head>
<body>
@php
    // Authenticated participant; never resolve another contestant from a URL or form field.
    $participant = auth('makeme')->user();
    $firstName = trim((string) ($participant?->first_name ?? 'Contestant')) ?: 'Contestant';
    $participantNumber = $participant?->participant_number ?? 'Pending';
@endphp
<div class="cp-shell">
    <input type="checkbox" id="cp-sidebar-toggle" class="cp-sidebar-toggle" aria-label="Toggle navigation">
    <label class="cp-mobile-backdrop" for="cp-sidebar-toggle" aria-hidden="true"></label>

    <aside class="cp-sidebar" aria-label="Contestant navigation">
        <div class="cp-sidebar-head">
            <a class="cp-brand" href="{{ route('makeme.participant.dashboard') }}">
                <img src="{{ asset('images/nyfew-logo.jpeg') }}" alt="NYFEW">
                <span><strong>PROJECT MAKEME</strong><small>LIVE · CONTESTANT PORTAL</small></span>
            </a>
            <label for="cp-sidebar-toggle" class="cp-mobile-close" aria-label="Close navigation">&times;</label>
        </div>
        <div class="cp-sidebar-scroll">
            <p class="cp-nav-heading">MY BACKSTAGE</p>
            <nav class="cp-nav" aria-label="Main navigation">
                <a class="cp-nav-link {{ request()->routeIs('makeme.participant.dashboard') ? 'active' : '' }}"
                   href="{{ route('makeme.participant.dashboard') }}"
                   @if(request()->routeIs('makeme.participant.dashboard')) aria-current="page" @endif>
                    <span class="cp-nav-icon" aria-hidden="true">◫</span><span>Overview</span>
                </a>

                {{-- Only link to routes after they are implemented. --}}
                @if(\Illuminate\Support\Facades\Route::has('makeme.participant.marketplace.index'))
                    <a class="cp-nav-link {{ request()->routeIs('makeme.participant.marketplace.*') ? 'active' : '' }}"
                       href="{{ route('makeme.participant.marketplace.index') }}"
                       @if(request()->routeIs('makeme.participant.marketplace.*')) aria-current="page" @endif>
                        <span class="cp-nav-icon" aria-hidden="true">▦</span><span>Marketplace</span>
                    </a>
                @else
                    <div class="cp-nav-link cp-nav-disabled" aria-disabled="true"><span class="cp-nav-icon" aria-hidden="true">▦</span><span>Marketplace</span><small>Soon</small></div>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('makeme.participant.kolo.index'))
                    <a class="cp-nav-link {{ request()->routeIs('makeme.participant.kolo.*') ? 'active' : '' }}"
                       href="{{ route('makeme.participant.kolo.index') }}"
                       @if(request()->routeIs('makeme.participant.kolo.*')) aria-current="page" @endif>
                        <span class="cp-nav-icon" aria-hidden="true">◈</span><span>Kolo Money</span>
                    </a>
                @else
                    <div class="cp-nav-link cp-nav-disabled" aria-disabled="true"><span class="cp-nav-icon" aria-hidden="true">◈</span><span>Kolo Money</span><small>Soon</small></div>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('makeme.participant.orders.index'))
                    <a class="cp-nav-link {{ request()->routeIs('makeme.participant.orders.*') ? 'active' : '' }}"
                       href="{{ route('makeme.participant.orders.index') }}"
                       @if(request()->routeIs('makeme.participant.orders.*')) aria-current="page" @endif>
                        <span class="cp-nav-icon" aria-hidden="true">▤</span><span>My Orders</span>
                    </a>
                @else
                    <div class="cp-nav-link cp-nav-disabled" aria-disabled="true"><span class="cp-nav-icon" aria-hidden="true">▤</span><span>My Orders</span><small>Soon</small></div>
                @endif
            </nav>

            <p class="cp-nav-heading cp-nav-heading-second">MY ACCOUNT</p>
            <nav class="cp-nav" aria-label="Account navigation">
                @if(\Illuminate\Support\Facades\Route::has('makeme.participant.profile.index'))
                    <a class="cp-nav-link {{ request()->routeIs('makeme.participant.profile.*') ? 'active' : '' }}"
                       href="{{ route('makeme.participant.profile.index') }}"
                       @if(request()->routeIs('makeme.participant.profile.*')) aria-current="page" @endif>
                        <span class="cp-nav-icon" aria-hidden="true">♙</span><span>My Profile</span>
                    </a>
                @else
                    <div class="cp-nav-link cp-nav-disabled" aria-disabled="true"><span class="cp-nav-icon" aria-hidden="true">♙</span><span>My Profile</span><small>Soon</small></div>
                @endif
            </nav>
        </div>

        <div class="cp-sidebar-footer">
            <div class="cp-sidebar-pass">
                <span class="cp-pass-mark" aria-hidden="true">✦</span>
                <div><strong>Official Contestant</strong><small>{{ $participantNumber }}</small></div>
            </div>
            <form method="POST" action="{{ route('makeme.participant.logout') }}">
                @csrf
                <button type="submit" class="cp-signout"><span aria-hidden="true">↪</span> Sign out</button>
            </form>
        </div>
    </aside>

    <div class="cp-main-wrap">
        <header class="cp-topbar">
            <div class="cp-topbar-left">
                <label for="cp-sidebar-toggle" class="cp-menu-button" aria-label="Open navigation">☰</label>
                <div class="cp-breadcrumb">
                    <span>Contestant Portal</span><b>/</b><strong>@yield('breadcrumb', 'Overview')</strong>
                </div>
            </div>
            
            {{-- PARTICIPANT PROFILE DROPDOWN --}}
            @php
                $currentParticipant = auth('makeme')->user();

                $profileName = trim(implode(' ', array_filter([
                    $currentParticipant?->first_name,
                    $currentParticipant?->other_name,
                    $currentParticipant?->surname,
                ]))) ?: 'Contestant';

                $profileInitial = mb_strtoupper(
                    mb_substr($profileName, 0, 1)
                );

                $profileNumber = $currentParticipant?->participant_number
                    ?? 'MakeMe Live Contestant';
            @endphp

            <div class="cp-profile-dropdown" id="cpProfileDropdown">

                <button
                    type="button"
                    class="cp-profile-trigger"
                    id="cpProfileTrigger"
                    aria-haspopup="true"
                    aria-expanded="false"
                    aria-controls="cpProfileMenu"
                >
                   
            <span class="cp-profile-avatar">
                @if($currentParticipant?->photo_url)
                    <img
                        src="{{ $currentParticipant->photo_url }}"
                        alt="{{ $profileName }}"
                        class="cp-avatar-image"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >

                    <span class="cp-avatar-fallback" style="display:none;">
                        {{ $profileInitial }}
                    </span>
                @else
                    <span class="cp-avatar-fallback">
                        {{ $profileInitial }}
                    </span>
                @endif
            </span>


                    <span class="cp-profile-trigger-info">
                        <strong>{{ $profileName }}</strong>
                        <small>Contestant</small>
                    </span>

                    <svg
                        class="cp-profile-chevron"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>

                <div
                    class="cp-profile-menu"
                    id="cpProfileMenu"
                    hidden
                >

                    <div class="cp-profile-menu-header">
                       
                <span class="cp-profile-menu-avatar">
                    @if($currentParticipant?->photo_url)
                        <img
                            src="{{ $currentParticipant->photo_url }}"
                            alt="{{ $profileName }}"
                            class="cp-avatar-image"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >

                        <span class="cp-avatar-fallback" style="display:none;">
                            {{ $profileInitial }}
                        </span>
                    @else
                        <span class="cp-avatar-fallback">
                            {{ $profileInitial }}
                        </span>
                    @endif
                </span>


                        <div>
                            <strong>{{ $profileName }}</strong>
                            <small>{{ $profileNumber }}</small>
                        </div>
                    </div>

                    <div class="cp-profile-menu-divider"></div>

                    <a
                        href="{{ route('makeme.participant.dashboard') }}"
                        class="cp-profile-menu-item"
                    >
                        <span>⌂</span>
                        Dashboard
                    </a>

                    @if(Route::has('makeme.participant.profile'))
                        <a
                            href="{{ route('makeme.participant.profile') }}"
                            class="cp-profile-menu-item"
                        >
                            <span>♙</span>
                            My Profile
                        </a>
                    @endif

                    @if(Route::has('makeme.participant.kolo.index'))
                        <a
                            href="{{ route('makeme.participant.kolo.index') }}"
                            class="cp-profile-menu-item"
                        >
                            <span>◈</span>
                            Kolo Money
                        </a>
                    @endif

                    <div class="cp-profile-menu-divider"></div>

                    <form
                        method="POST"
                        action="{{ route('makeme.participant.logout') }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="cp-profile-menu-item cp-profile-signout"
                        >
                            <span>↪</span>
                            Sign Out
                        </button>
                    </form>

                </div>
            </div>

        </header>

        <main class="cp-content">
            @yield('content')
        </main>

        <footer class="cp-footer">
            <span>© {{ date('Y') }} NYFEW · Project MakeMe Live</span>
            <span>Create · Compete · Shine</span>
        </footer>
    </div>
</div>

@stack('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    const dropdown = document.getElementById('cpProfileDropdown');
    const trigger = document.getElementById('cpProfileTrigger');
    const menu = document.getElementById('cpProfileMenu');

    if (!dropdown || !trigger || !menu) {
        return;
    }

    function setDropdownState(isOpen) {
        menu.hidden = !isOpen;

        trigger.setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );

        dropdown.classList.toggle('is-open', isOpen);
    }

    // Open or close when the avatar is clicked
    trigger.addEventListener('click', function (event) {
        event.stopPropagation();
        setDropdownState(menu.hidden);
    });

    // Close when clicking outside
    document.addEventListener('click', function (event) {
        if (!dropdown.contains(event.target)) {
            setDropdownState(false);
        }
    });

    // Close using Escape key
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !menu.hidden) {
            setDropdownState(false);
            trigger.focus();
        }
    });

    // Close after selecting a navigation link
    menu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            setDropdownState(false);
        });
    });

});
</script>

</body>
</html>

</body>
</html>
