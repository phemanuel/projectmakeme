@extends('admin.makeme.layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<section class="page-intro">
    <div>
        <div class="intro-kicker">SHOW INSIGHTS <span class="live-dot"></span></div>
        <h1>Reports & Analytics<span class="gold-period">.</span></h1>
        <p>Reporting for shopping credits, product stock and collection status.</p>
    </div>
</section>

<section class="panel module-panel">
    <div class="module-glow" aria-hidden="true"></div>
    <span class="module-kicker">PROJECT MAKEME LIVE · ADMIN OPERATIONS</span>
    <h2>Module page ready</h2>
    <p>Reports will use actual transactions after the wallet and checkout systems are implemented.</p>
    <div class="module-features">
            <div class="module-feature"><span aria-hidden="true">✦</span> Credit allocations and balances</div>
            <div class="module-feature"><span aria-hidden="true">✦</span> Product inventory and purchases</div>
            <div class="module-feature"><span aria-hidden="true">✦</span> Exportable participant and collection reports</div>
    </div>
    <div class="module-actions">
        <a href="{{ route('admin.makeme.dashboard') }}" class="btn btn-outline">Return to overview</a>
        <a href="{{ route('admin.makeme.participants.index') }}" class="btn btn-gold">Manage participants</a>
    </div>
</section>
@endsection
