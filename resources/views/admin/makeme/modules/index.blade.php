@extends('admin.makeme.layouts.app')

@section('title', $heading)

@section('content')
<section class="page-intro">
    <div>
        <div class="intro-kicker">{{ $eyebrow }} <span class="live-dot"></span></div>
        <h1>{{ $heading }}<span class="gold-period">.</span></h1>
        <p>{{ $description }}</p>
    </div>
</section>

<section class="panel module-panel">
    <div class="module-glow" aria-hidden="true"></div>
    <span class="module-kicker">PROJECT MAKEME LIVE · ADMIN OPERATIONS</span>
    <h2>Module foundation is ready</h2>
    <p>This page is now connected to the admin navigation. The operational features below are planned and are <strong>not yet enabled</strong>.</p>
    <div class="module-features">
        @foreach ($featureList as $feature)
            <div class="module-feature"><span aria-hidden="true">✦</span> {{ $feature }}</div>
        @endforeach
    </div>
    <div class="module-actions">
        <a href="{{ route('admin.makeme.dashboard') }}" class="btn btn-outline">Return to overview</a>
        <a href="{{ route('admin.makeme.participants.index') }}" class="btn btn-gold">Manage participants</a>
    </div>
</section>
@endsection
