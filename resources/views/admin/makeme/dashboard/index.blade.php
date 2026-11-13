@extends('admin.makeme.layouts.app')
@section('title', 'Show Operations')
@section('content')
<div class="page-intro"><div><div class="intro-kicker">NYFEW / SHOW OPERATIONS</div>
<h1>Admin Overview<span class="gold-period">.</span></h1>
<p>Manage the people and equipment behind Project MakeMe Live.</p></div><div class="intro-actions"><a class="btn btn-gold" href="{{ route('admin.makeme.products.create') }}">+ Add product</a><a class="btn btn-outline" href="{{ route('admin.makeme.participants.index') }}">View participants</a></div></div>
@if(session('success'))<div class="notice success">{{ session('success') }}</div>@endif
<div class="stat-grid">
<div class="stat-card"><div class="stat-label">ENROLLED PARTICIPANTS</div><div class="stat-value">{{ number_format($stats['participants']) }}</div><div class="stat-detail">{{ number_format($stats['active_participants']) }} active</div><span class="stat-icon">◎</span></div>
<div class="stat-card"><div class="stat-label">CATALOGUE PRODUCTS</div><div class="stat-value">{{ number_format($stats['products']) }}</div><div class="stat-detail">{{ number_format($stats['active_products']) }} published</div><span class="stat-icon">▦</span></div>
<div class="stat-card"><div class="stat-label">STOCK UNITS</div><div class="stat-value">{{ number_format($stats['stock_units']) }}</div><div class="stat-detail">Total units entered</div><span class="stat-icon">▣</span></div>
</div>
<section class="panel"><div class="panel-heading"><div><h2>Recently added products</h2><p>Latest additions to the equipment catalogue</p></div><a href="{{ route('admin.makeme.products.index') }}" class="btn btn-outline">Manage products</a></div><div class="table-scroll"><table class="participant-table"><thead><tr><th>PRODUCT</th><th>SKU</th><th>CREDITS</th><th>STOCK</th><th>VISIBILITY</th></tr></thead><tbody>@forelse($recentProducts as $product)<tr><td><strong>{{ $product->name }}</strong></td><td>{{ $product->sku }}</td><td>{{ number_format($product->credit_price) }}</td><td>{{ number_format($product->stock_quantity) }}</td><td><span class="status {{ $product->is_active ? 'status-active' : 'status-inactive' }}">{{ $product->is_active ? 'Published' : 'Draft' }}</span></td></tr>@empty<tr><td colspan="5"><div class="empty-state"><strong>No products yet</strong><p>Add the equipment participants will be able to purchase.</p></div></td></tr>@endforelse</tbody></table></div></section>
@endsection
