@extends('makeme.participant.layouts.app')
@section('title', $product->name . ' | Marketplace')
@section('breadcrumb', 'Marketplace / Product')
@section('content')
<div class="mm-page"><a class="mm-back" href="{{ route('makeme.participant.marketplace.index') }}">← Back to Marketplace</a>
@if($errors->any())<div class="mm-error"><strong>Cannot place this order:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="mm-detail">
  <div class="mm-detail-photo">@if($product->image_path)<img src="{{ asset('storage/' . ltrim($product->image_path, '/')) }}" alt="{{ $product->name }}">@else<span>✦</span>@endif</div>
  <div class="mm-detail-info"><p class="cp-overline">{{ ucwords(str_replace(['_', '-'], ' ', (string) ($product->category ?: 'General'))) }}</p><h1>{{ $product->name }}</h1><p>{{ $product->description ?: 'No description provided.' }}</p><div class="mm-big-price">{{ number_format((int)$product->credit_price) }} <small>Kolo credits each</small></div>
  <div class="mm-facts"><div>Current stock <strong>{{ number_format($product->stock_quantity) }}</strong></div><div>Your credits <strong>{{ number_format($walletBalance) }}</strong></div>@if((int)$product->max_per_participant > 0)<div>Limit per contestant <strong>{{ number_format($product->max_per_participant) }}</strong></div><div>Already ordered <strong>{{ number_format($alreadyOrdered) }}</strong></div>@endif</div>
  @php $remainingLimit = (int)$product->max_per_participant > 0 ? max(0, (int)$product->max_per_participant - $alreadyOrdered) : (int)$product->stock_quantity; $maxQty = min((int)$product->stock_quantity, $remainingLimit, 100000); @endphp
  @if($maxQty > 0)
  <form action="{{ route('makeme.participant.marketplace.purchase', $product) }}" method="POST" class="mm-buy-form" onsubmit="const b=this.querySelector('button[type=submit]'); b.disabled=true; b.textContent='Placing order…';">@csrf
    <input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
    <label for="mmQuantity">Quantity</label><input id="mmQuantity" type="number" name="quantity" min="1" max="{{ $maxQty }}" value="{{ old('quantity', 1) }}" required>
    <p>Credits will be deducted immediately. Your order will await fulfilment.</p><button type="submit" class="mm-btn">Confirm purchase</button>
  </form>
  @else <div class="mm-error">This product cannot be purchased at the moment (out of stock or you have reached your limit).</div>@endif
  </div>
</div></div>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('css/makeme-participant-marketplace.css') }}">@endpush
