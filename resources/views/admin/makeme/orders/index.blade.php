@extends('admin.makeme.layouts.app')

@section('title', 'Orders & Collections')

@section('content')
<section class="page-intro">
    <div>
        <div class="intro-kicker">FULFILMENT DESK <span class="live-dot"></span></div>
        <h1>Orders & Collections<span class="gold-period">.</span></h1>
        <p>Track participant purchases and equipment handovers.</p>
    </div>
</section>

<section class="panel module-panel">
    <div class="module-glow" aria-hidden="true"></div>
    <span class="module-kicker">PROJECT MAKEME LIVE · ADMIN OPERATIONS</span>
    <h2>Module page ready</h2>
    <p>Checkout and order fulfilment are not active yet. There are no live order records on this page.</p>
    <div class="module-features">
            <div class="module-feature"><span aria-hidden="true">✦</span> Participant order listing</div>
            <div class="module-feature"><span aria-hidden="true">✦</span> Order approval and cancellation</div>
            <div class="module-feature"><span aria-hidden="true">✦</span> QR-based collection verification</div>
    </div>
    <div class="module-actions">
        <a href="{{ route('admin.makeme.dashboard') }}" class="btn btn-outline">Return to overview</a>
        <a href="{{ route('admin.makeme.participants.index') }}" class="btn btn-gold">Manage participants</a>
    </div>
</section>
@endsection
