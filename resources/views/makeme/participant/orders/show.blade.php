@extends('makeme.participant.layouts.app')
@section('title', 'Order ' . $order->reference . ' | Project MakeMe Live')
@section('breadcrumb', 'Order Details')
@section('content')
<div class="mm-page"><a class="mm-back" href="{{ route('makeme.participant.orders.index') }}">← My Orders</a>
@if(session('success'))<div class="mm-notice">{{ session('success') }}</div>@endif
<div class="mm-order-detail"><div class="mm-head"><div><p class="cp-overline">ORDER CONFIRMATION</p><h1>{{ $order->reference }}</h1><p>Placed {{ $order->created_at?->format('d M Y, g:i A') }}</p></div><span class="mm-status mm-status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></div>
<div class="mm-order-list">@foreach($order->items as $item)<div class="mm-order-row"><div><strong>{{ $item->product_name }}</strong><small>{{ $item->quantity }} × {{ number_format($item->unit_price) }} credits</small></div><strong>{{ number_format($item->line_total) }} credits</strong></div>@endforeach</div>
<div class="mm-total">Total <strong>{{ number_format($order->total_credits) }} credits</strong></div>
@if($order->status === 'placed')<p class="mm-hint">Order received and awaiting fulfilment.</p>@elseif($order->status === 'fulfilled')<p class="mm-hint">This order has been fulfilled.</p>@elseif($order->status === 'cancelled')<p class="mm-hint">This order was cancelled. Credits should have been returned to your Kolo Money wallet.</p>@endif
</div></div>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('css/makeme-participant-marketplace.css') }}">@endpush
