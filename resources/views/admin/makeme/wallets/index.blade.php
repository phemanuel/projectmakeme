@extends('admin.makeme.layouts.app')

@section('title', 'Wallets & Credit Allocation')

@section('content')
<section class="page-intro">
    <div>
        <div class="intro-kicker">FINANCIAL CONTROL <span class="live-dot"></span></div>
        <h1>Wallets & Credit Allocation<span class="gold-period">.</span></h1>
        <p>Credit allocations, balances and transaction history will appear here.</p>
    </div>
</section>

<section class="panel module-panel">
    <div class="module-glow" aria-hidden="true"></div>
    <span class="module-kicker">PROJECT MAKEME LIVE · ADMIN OPERATIONS</span>
    <h2>Module page ready</h2>
    <p>Wallet operations are not active yet. No credits can be issued from this page.</p>
    <div class="module-features">
            <div class="module-feature"><span aria-hidden="true">✦</span> Credit individual participants</div>
            <div class="module-feature"><span aria-hidden="true">✦</span> Bulk credit allocation</div>
            <div class="module-feature"><span aria-hidden="true">✦</span> Transaction ledger and adjustments</div>
    </div>
    <div class="module-actions">
        <a href="{{ route('admin.makeme.dashboard') }}" class="btn btn-outline">Return to overview</a>
        <a href="{{ route('admin.makeme.participants.index') }}" class="btn btn-gold">Manage participants</a>
    </div>
</section>
@endsection
