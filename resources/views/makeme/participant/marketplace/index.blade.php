@extends('makeme.participant.layouts.app')
@section('title', 'Marketplace | Project MakeMe Live')
@section('breadcrumb', 'Marketplace')
@section('content')
<div class="mm-page">
  <div class="mm-head"><div><p class="cp-overline">PROJECT MAKEME LIVE</p><h1>Creative Marketplace</h1><p>Explore supplies and purchase with your Kolo Money credits.</p></div><div class="mm-balance">KOLO BALANCE <strong>{{ number_format($walletBalance) }}</strong><small>credits</small></div></div>
  @if(session('success'))<div class="mm-notice">{{ session('success') }}</div>@endif
  <form method="GET" class="mm-search" action="{{ route('makeme.participant.marketplace.index') }}"><input type="search" name="q" placeholder="Search products or categories" value="{{ request('q') }}" maxlength="120"><button type="submit">Search</button><a href="{{ route('makeme.participant.marketplace.index') }}">Clear</a></form>
  <div class="mm-grid">
  @forelse($products as $product)
    <article class="mm-product">
      <a href="{{ route('makeme.participant.marketplace.show', $product) }}" class="mm-image">
        @if($product->image_path)<img loading="lazy" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($product->image_path, '/')) }}" alt="{{ $product->name }}">@else<span class="mm-image-fallback">✦</span>@endif
      </a>
      <div class="mm-info"><span class="mm-category">{{ ucwords(str_replace(['_', '-'], ' ', (string) ($product->category ?: 'General'))) }}</span><h3>{{ $product->name }}</h3>@if($product->unit_label)<p class="mm-product-unit" style="margin:4px 0 8px;font-size:12px;font-weight:600;opacity:.85;">{{ $product->unit_label }}</p>@endif<div class="mm-price">{{ number_format((int)$product->credit_price) }} <small>credits {{ $product->unit_label ? 'per ' . $product->unit_label : 'each' }}</small></div><p class="mm-stock">{{ number_format($product->stock_quantity) }} remaining</p><a class="mm-btn" href="{{ route('makeme.participant.marketplace.show', $product) }}">View &amp; purchase</a></div>
    </article>
  @empty
    <div class="mm-empty">No products available. @if(request('q'))Try another search.@else Check again after supplies are added.@endif</div>
  @endforelse
  </div>
  <div class="mm-paginate">{{ $products->links() }}</div>
</div>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('css/makeme-participant-marketplace.css') }}">@endpush
