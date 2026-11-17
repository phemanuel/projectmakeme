
@extends('makeme.participant.layouts.app')

@section('title', 'Marketplace | Project MakeMe Live')
@section('breadcrumb', 'Marketplace')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/makeme-participant-marketplace.css') }}">

<style>
/* ============================================
   PROJECT MAKEME - MARKETPLACE PRODUCT CARDS
   Isolated styles to prevent older card/image
   rules from affecting the new layout.
============================================ */

.pmk-shop-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
    align-items: stretch;
    margin-top: 24px;
}

.pmk-shop-card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    height: 100%;
    overflow: hidden;
    position: relative;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, .12);
    background: #ffffff;
    box-shadow: 0 8px 28px rgba(0, 0, 0, .12);
    isolation: isolate;
}

/* PRODUCT IMAGE: ONLY THE TOP SECTION */

.pmk-shop-media {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    width: 100%;
    height: 210px;
    min-height: 210px;
    max-height: 210px;
    flex: 0 0 210px;
    overflow: hidden;
    background: #f3f4f6;
    text-decoration: none;
}

.pmk-shop-media img {
    display: block !important;
    position: static !important;
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    max-height: 100% !important;
    object-fit: contain !important;
    object-position: center !important;
    margin: 0 !important;
    padding: 12px !important;
    box-sizing: border-box !important;
    border-radius: 0 !important;
    transform: none !important;
}

.pmk-shop-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    font-size: 48px;
    color: #777;
}

/* DETAILS: COMPLETELY SEPARATE FROM IMAGE */

.pmk-shop-details {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-width: 0;
    position: relative;
    background: #ffffff;
    color: #161616;
    padding: 18px;
    gap: 10px;
    z-index: 1;
}

.pmk-shop-category {
    display: inline-flex;
    align-self: flex-start;
    max-width: 100%;
    background: #fff3d7;
    border: 1px solid #f0dfb9;
    color: #805a13;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .055em;
    line-height: 1.3;
    text-transform: uppercase;
}

.pmk-shop-title {
    margin: 0;
    color: #141414;
    font-size: 18px;
    line-height: 1.4;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.pmk-shop-unit {
    display: inline-flex;
    align-self: flex-start;
    background: #f2f3f5;
    color: #535965;
    padding: 6px 9px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    margin: 0;
}

.pmk-shop-price {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    column-gap: 6px;
    row-gap: 3px;
    margin-top: 4px;
    color: #131313;
}

.pmk-shop-price strong {
    font-size: 25px;
    font-weight: 900;
    line-height: 1.1;
}

.pmk-shop-price span {
    font-size: 12px;
    color: #69707b;
    font-weight: 600;
}

.pmk-shop-stock {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #286145;
    font-size: 12px;
    font-weight: 700;
    margin: 0;
}

.pmk-shop-stock::before {
    content: "";
    display: inline-block;
    flex: 0 0 7px;
    width: 7px;
    height: 7px;
    background: #28a66b;
    border-radius: 50%;
}

.pmk-shop-stock.is-empty {
    color: #a12b34;
}

.pmk-shop-stock.is-empty::before {
    background: #dc3545;
}

.pmk-shop-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    box-sizing: border-box;
    min-height: 44px;
    margin-top: auto;
    padding: 12px 14px;
    border-radius: 10px;
    background: #171717;
    color: #ffffff !important;
    text-decoration: none !important;
    text-align: center;
    font-size: 13px;
    font-weight: 800;
    transition: background .2s ease, transform .2s ease;
}

.pmk-shop-button:hover {
    background: #393939;
    transform: translateY(-1px);
}

.pmk-shop-button:focus-visible,
.pmk-shop-media:focus-visible {
    outline: 3px solid #efad39;
    outline-offset: 3px;
}

/* RESPONSIVE GRID */

@media (max-width: 1100px) {
    .pmk-shop-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .pmk-shop-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .pmk-shop-media {
        height: 155px;
        min-height: 155px;
        max-height: 155px;
        flex-basis: 155px;
    }

    .pmk-shop-media img {
        padding: 8px !important;
    }

    .pmk-shop-details {
        padding: 13px 11px;
        gap: 9px;
    }

    .pmk-shop-category {
        font-size: 9px;
        padding: 5px 7px;
    }

    .pmk-shop-title {
        font-size: 15px;
    }

    .pmk-shop-unit {
        font-size: 11px;
    }

    .pmk-shop-price strong {
        font-size: 20px;
    }

    .pmk-shop-button {
        font-size: 12px;
        padding: 10px 8px;
    }
}

@media (max-width: 360px) {
    .pmk-shop-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .pmk-shop-media {
        height: 200px;
        min-height: 200px;
        max-height: 200px;
        flex-basis: 200px;
    }
}
</style>
<style>
    
/* ============================================
   PROJECT MAKEME - COMPACT PAGINATION
============================================ */

.pmk-pagination-wrapper {
    display: flex !important;
    justify-content: center !important;
    width: 100%;
    margin: 28px 0 12px;
}

.pmk-pagination-wrapper > nav {
    width: auto !important;
    max-width: 100%;
}

.pmk-pagination-wrapper ul.pagination {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
}

.pmk-pagination-wrapper ul.pagination > li.page-item {
    display: inline-flex !important;
    margin: 0 !important;
    padding: 0 !important;
    list-style: none !important;
}

.pmk-pagination-wrapper .page-link {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-sizing: border-box !important;

    width: auto !important;
    min-width: 34px !important;
    height: 34px !important;
    min-height: 34px !important;
    padding: 0 10px !important;

    border: 1px solid #e5e7eb !important;
    border-radius: 8px !important;

    background: #ffffff !important;
    color: #222222 !important;

    font-size: 12px !important;
    font-weight: 700 !important;
    line-height: 1 !important;
    text-decoration: none !important;

    box-shadow: none !important;
}

.pmk-pagination-wrapper .page-link:hover {
    background: #f3f4f6 !important;
    color: #111111 !important;
}

.pmk-pagination-wrapper .page-item.active .page-link {
    background: #171717 !important;
    border-color: #171717 !important;
    color: #ffffff !important;
}

.pmk-pagination-wrapper .page-item.disabled .page-link {
    opacity: 0.45 !important;
    cursor: not-allowed !important;
}

.pmk-pagination-wrapper .page-link svg {
    width: 14px !important;
    height: 14px !important;
}

@media (max-width: 480px) {
    .pmk-pagination-wrapper ul.pagination {
        gap: 4px !important;
    }

    .pmk-pagination-wrapper .page-link {
        min-width: 30px !important;
        height: 30px !important;
        min-height: 30px !important;
        padding: 0 7px !important;
        font-size: 11px !important;
    }
}

</style>

@endpush

@section('content')

<div class="mm-page">

    {{-- PAGE HEADER --}}
    <div class="mm-head">
        <div>
            <p class="cp-overline">PROJECT MAKEME LIVE</p>
            <h1>Kreativ Marketplace</h1>
            <p>
                Explore supplies and purchase with your
                KoloMoni credits.
            </p>
        </div>

        <div class="mm-balance">
            KOLOMONI BALANCE
            <strong>{{ number_format($walletBalance) }}</strong>
            <small>credits</small>
        </div>
    </div>
    <x-makeme-delivery-notice />

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="mm-notice">
            {{ session('success') }}
        </div>
    @endif

    {{-- SEARCH --}}
    <form
        method="GET"
        class="mm-search"
        action="{{ route('makeme.participant.marketplace.index') }}"
    >
        <input
            type="search"
            name="q"
            placeholder="Search products or categories"
            value="{{ request('q') }}"
            maxlength="120"
        >

        <button type="submit">
            Search
        </button>

        <a href="{{ route('makeme.participant.marketplace.index') }}">
            Clear
        </a>
    </form>

    {{-- PRODUCT GRID --}}
    <div class="pmk-shop-grid">

        @forelse($products as $product)

            @php
                $productUrl = route(
                    'makeme.participant.marketplace.show',
                    $product
                );

                $imageUrl = $product->image_path
                    ? \Illuminate\Support\Facades\Storage::disk('public')
                        ->url(ltrim($product->image_path, '/'))
                    : null;

                $categoryLabel = ucwords(
                    str_replace(
                        ['_', '-'],
                        ' ',
                        (string) ($product->category ?: 'General')
                    )
                );

                $isOutOfStock = (int) $product->stock_quantity <= 0;
            @endphp

            <article class="pmk-shop-card">

                {{-- TOP: IMAGE ONLY --}}
                <a
                    href="{{ $productUrl }}"
                    class="pmk-shop-media"
                    aria-label="View {{ $product->name }}"
                >
                    @if($imageUrl)
                        <img
                            loading="lazy"
                            src="{{ $imageUrl }}"
                            alt="{{ $product->name }}"
                        >
                    @else
                        <span class="pmk-shop-placeholder">
                            📦
                        </span>
                    @endif
                </a>

                {{-- BOTTOM: PRODUCT DETAILS --}}
                <div class="pmk-shop-details">

                    <span class="pmk-shop-category">
                        {{ $categoryLabel }}
                    </span>

                    <h3 class="pmk-shop-title">
                        {{ $product->name }}
                    </h3>

                    @if($product->unit_label)
                        <p class="pmk-shop-unit">
                            {{ $product->unit_label }}
                        </p>
                    @endif

                    <div class="pmk-shop-price">
                        <strong>
                            {{ number_format((int) $product->credit_price) }}
                        </strong>

                        <span>
                            credits
                            {{ $product->unit_label
                                ? 'per ' . $product->unit_label
                                : 'each' }}
                        </span>
                    </div>

                    <p class="pmk-shop-stock {{ $isOutOfStock ? 'is-empty' : '' }}">
                        @if($isOutOfStock)
                            Out of stock
                        @else
                            {{ number_format($product->stock_quantity) }}
                            remaining
                        @endif
                    </p>

                    <a
                        href="{{ $productUrl }}"
                        class="pmk-shop-button"
                    >
                        View &amp; Purchase
                        <span aria-hidden="true">→</span>
                    </a>

                </div>

            </article>

        @empty

            <div class="mm-empty">
                No products available.

                @if(request('q'))
                    Try another search.
                @else
                    Check again after supplies are added.
                @endif
            </div>

        @endforelse

    </div>

    {{-- PAGINATION --}}
   @if($products->hasPages())
    <div class="pmk-pagination-wrapper">
        {{ $products->links('pagination::bootstrap-5') }}
    </div>
@endif

</div>

@endsection
