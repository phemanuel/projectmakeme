@extends('makeme.participant.layouts.app')

@section('title', 'Overview | Project MakeMe Live')
@section('breadcrumb', 'Overview')

@section('content')
@php
    $participant = auth('makeme')->user();
    $firstName = trim((string) ($participant?->first_name ?? 'Contestant')) ?: 'Contestant';
    $participantNumber = $participant?->participant_number ?? 'Pending';
    $walletBalance = (int) ($walletBalance ?? 0);
    $availableProducts = (int) ($availableProducts ?? 0);
    $products = $products ?? collect();

    $fullName = trim(implode(' ', array_filter([
        $participant->first_name ?? null,
        $participant->other_name ?? null,
        $participant->surname ?? null,
    ]))) ?: $firstName;

    $skillCategory = ucwords(str_replace(
        ['_', '-'], ' ', (string) ($participant->skill_category ?? 'Not specified')
    ));
@endphp
<div class="cp-page-head"><div><p class="cp-overline">CONTESTANT COMMAND CENTRE</p><h1>Welcome back, {{ $firstName }}<span>.</span></h1><p>Here's your MakeMe Live experience at a glance.</p></div><span class="cp-date">{{ now()->format('D, d M Y') }}</span></div>

            <section class="cp-show-banner" aria-label="MakeMe Live welcome">
                <div class="cp-banner-content"><span class="cp-banner-kicker"><span class="cp-banner-kicker-dot"></span> PROJECT MAKEME LIVE</span><h2>Your talent. <em>Your stage.</em></h2><p>Your backstage access to the creative experience. Keep an eye on your credits, contestant details and the Marketplace.</p></div>
                <div class="cp-banner-art" aria-hidden="true"><span>MAKE<br>ME</span><b>LIVE</b></div>
            </section>

            <section class="cp-metrics" aria-label="Account overview">
                <div class="cp-metric"><span class="cp-metric-icon cp-icon-pink">◈</span><div><small>Kolo Money Balance</small><strong>{{ number_format($walletBalance) }}</strong><span>Available credits</span></div></div>
                <div class="cp-metric"><span class="cp-metric-icon cp-icon-gold">▦</span><div><small>Available Products</small><strong>{{ number_format($availableProducts) }}</strong><span>In the Marketplace</span></div></div>
                <div class="cp-metric"><span class="cp-metric-icon cp-icon-purple">✦</span><div><small>Contestant Status</small><strong class="cp-status-value">Active</strong><span>Ready for the experience</span></div></div>
            </section>

            <div class="cp-columns">
                <section class="cp-panel cp-money-panel" aria-labelledby="cp-money-title">
                    <div class="cp-panel-heading"><div><p class="cp-overline">OFFICIAL WALLET SPONSOR</p><h3 id="cp-money-title">Kolo Money</h3></div><img src="{{ asset('images/sponsors/kolo-money-logo.png') }}" alt="Kolo Money logo" class="cp-kolo-logo" onerror="this.hidden=true"></div>
                    <div class="cp-money-balance"><span>Your available credits</span><strong>{{ number_format($walletBalance) }}</strong><small>KOLO CREDITS</small></div>
                    <div class="cp-money-foot"><span>Use your credits for eligible supplies when checkout opens.</span><span aria-hidden="true">✦</span></div>
                </section>           

                
                
                {{-- CONTESTANT PROFILE --}}
                <section
                    class="cp-panel cp-profile-panel"
                    aria-labelledby="cp-profile-title"
                    style="min-width:0; overflow:hidden;"
                >

                    {{-- HEADER --}}
                    <div class="cp-panel-heading">
                        <div>
                            <p class="cp-overline">YOUR IDENTITY</p>
                            <h3 id="cp-profile-title">Contestant Profile</h3>
                        </div>

                        <span class="cp-profile-badge">
                            Verified access
                        </span>
                    </div>

                    @php
                        $contestantInitial = mb_strtoupper(
                            mb_substr($participant->first_name ?: 'C', 0, 1)
                        );

                        $contestantPhoto = $participant->photo_url;
                    @endphp

                    {{-- TWO-COLUMN PROFILE LAYOUT --}}
                    <div
                        class="cp-profile-layout"
                        style="
                            display:grid !important;
                            grid-template-columns:110px minmax(0,1fr) !important;
                            gap:16px;
                            align-items:start;
                            width:100%;
                            min-width:0;
                            padding-top:10px;
                        "
                    >

                        {{-- LEFT: CONTESTANT PHOTOGRAPH --}}
                        <div
                            class="cp-profile-photo-column"
                            style="
                                width:110px !important;
                                min-width:110px !important;
                                max-width:110px !important;
                                margin:0;
                                padding:0;
                            "
                        >

                            <div
                                class="cp-profile-main-photo"
                                style="
                                    position:relative;
                                    display:block;
                                    width:110px !important;
                                    height:140px !important;
                                    min-width:110px !important;
                                    max-width:110px !important;
                                    min-height:140px !important;
                                    max-height:140px !important;
                                    overflow:hidden !important;
                                    border-radius:12px;
                                    background:#76256f;
                                    box-sizing:border-box;
                                    border:2px solid #f2e8f0;
                                "
                            >

                                @if($contestantPhoto)

                                    <img
                                        src="{{ $contestantPhoto }}"
                                        alt="{{ $fullName }} - Contestant Photograph"
                                        style="
                                            display:block;
                                            width:100% !important;
                                            height:100% !important;
                                            max-width:100% !important;
                                            max-height:100% !important;
                                            object-fit:cover !important;
                                            object-position:center 25%;
                                            margin:0;
                                            padding:0;
                                            border:0;
                                        "
                                        onerror="
                                            this.style.display='none';
                                            this.nextElementSibling.style.display='flex';
                                        "
                                    >

                                    <div
                                        class="cp-profile-photo-fallback"
                                        style="
                                            display:none;
                                            width:100%;
                                            height:100%;
                                            align-items:center;
                                            justify-content:center;
                                            background:linear-gradient(135deg,#c52c76,#76256f);
                                            color:#fff;
                                            font-size:38px;
                                            font-weight:800;
                                        "
                                    >
                                        {{ $contestantInitial }}
                                    </div>

                                @else

                                    <div
                                        class="cp-profile-photo-fallback"
                                        style="
                                            display:flex;
                                            width:100%;
                                            height:100%;
                                            align-items:center;
                                            justify-content:center;
                                            background:linear-gradient(135deg,#c52c76,#76256f);
                                            color:#fff;
                                            font-size:38px;
                                            font-weight:800;
                                        "
                                    >
                                        {{ $contestantInitial }}
                                    </div>

                                @endif

                            </div>

                            <!-- <span
                                class="cp-photo-caption"
                                style="
                                    display:block;
                                    width:110px;
                                    margin-top:9px;
                                    font-size:9px;
                                    font-weight:800;
                                    letter-spacing:.06em;
                                    color:#a82f76;
                                    text-align:center;
                                "
                            >
                                OFFICIAL CONTESTANT
                            </span> -->

                        </div>

                        {{-- RIGHT: CONTESTANT INFORMATION --}}
                        <div
                            class="cp-profile-details-column"
                            style="
                                min-width:0;
                                width:100%;
                                overflow-wrap:anywhere;
                            "
                        >

                            <div class="cp-profile-row">
                                <span>Full Name</span>
                                <strong>{{ $fullName }}</strong>
                            </div>

                            <div class="cp-profile-row">
                                <span>Contestant Number</span>
                                <strong>{{ $participantNumber }}</strong>
                            </div>

                            <div class="cp-profile-row">
                                <span>Skill Category</span>
                                <strong>{{ $skillCategory }}</strong>
                            </div>

                            @if($participant->business_name)
                                <div class="cp-profile-row">
                                    <span>Business Name</span>
                                    <strong>{{ $participant->business_name }}</strong>
                                </div>
                            @endif

                            @if($participant->profession)
                                <div class="cp-profile-row">
                                    <span>Profession</span>
                                    <strong>{{ $participant->profession }}</strong>
                                </div>
                            @endif

                            <div class="cp-profile-row">
                                <span>Account Status</span>

                                <strong
                                    class="cp-active-status"
                                    style="
                                        display:inline-flex;
                                        align-items:center;
                                        justify-content:flex-end;
                                        gap:6px;
                                    "
                                >
                                    <span
                                        style="
                                            display:inline-block;
                                            width:7px;
                                            height:7px;
                                            flex-shrink:0;
                                            border-radius:50%;
                                            background:{{ $participant->status === 'active' ? '#28a56a' : '#d18b32' }};
                                        "
                                    ></span>

                                    {{ ucfirst($participant->status ?? 'inactive') }}
                                </strong>
                            </div>

                        </div>

                    </div>

                </section>



            </div>

            <section class="cp-panel cp-supply-panel" aria-labelledby="cp-supply-title">
                <div class="cp-supply-heading"><div><p class="cp-overline">THE KREATIV MARKETPLACE</p><h3 id="cp-supply-title">Featured Supplies</h3><p>Browse the essentials available to contestants. Purchasing will be available when contestant checkout opens.</p></div><span class="cp-supply-count">{{ number_format($availableProducts) }} available</span></div>
                <div class="cp-products">
                    @forelse($products as $product)
                        <article class="cp-product"><div class="cp-product-photo">
                            @if($product->image_path)
                                <img src="{{ asset('storage/' . ltrim($product->image_path, '/')) }}" alt="{{ $product->name }}" loading="lazy">
                            @else
                                <div class="cp-product-fallback"><span>✦</span><small>MAKEME LIVE</small></div>
                            @endif
                            <span class="cp-stock-pill">In stock</span>
                        </div><div class="cp-product-info"><span class="cp-product-category">{{ ucwords(str_replace(['_', '-'], ' ', (string) ($product->category ?? 'General'))) }}</span><h4>{{ $product->name }}</h4><div class="cp-product-price"><strong>{{ number_format((int) $product->credit_price) }}</strong><span>credits</span></div></div></article>
                    @empty
                        <div class="cp-products-empty"><span>✦</span><strong>The Marketplace is getting ready</strong><p>Available products will appear here once they are added.</p></div>
                    @endforelse
                </div>
            </section>
            <div class="cp-bottom-note"><span class="cp-note-icon">i</span><p><strong>Heads up:</strong> Your Kolo Money balance is live. Product previews are read-only until participant checkout is activated.</p></div>
@endsection
