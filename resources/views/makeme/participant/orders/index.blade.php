@extends('makeme.participant.layouts.app')
@section('title', 'My Orders | Project MakeMe Live')
@section('breadcrumb', 'My Orders')
@section('content')
<div class="mm-page"><div class="mm-head"><div><p class="cp-overline">YOUR PURCHASES</p><h1>My Orders</h1><p>Track your supplies from order placement to fulfilment.</p></div><a class="mm-btn" href="{{ route('makeme.participant.marketplace.index') }}">Shop Marketplace</a></div>
@if(session('success'))<div class="mm-notice">{{ session('success') }}</div>@endif
<div class="mm-order-list">@forelse($orders as $order)
<a class="mm-order-row" href="{{ route('makeme.participant.orders.show', $order) }}"><div><strong>{{ $order->reference }}</strong><small>{{ $order->created_at?->format('d M Y, g:i A') }}</small><span>{{ $order->items->sum('quantity') }} item(s)</span></div><div><strong>{{ number_format($order->total_credits) }} credits</strong><span class="mm-status mm-status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></div></a>
@empty<div class="mm-empty">You have no orders yet.</div>@endforelse</div><div class="mm-paginate">{{ $orders->links() }}</div></div>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('css/makeme-participant-marketplace.css') }}">@endpush
