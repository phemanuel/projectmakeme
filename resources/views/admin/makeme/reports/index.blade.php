@extends('admin.makeme.layouts.app')

@section('title', 'Reports & Analytics')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/makeme-admin-reports.css') }}">
@endpush

@section('content')
@php
    $s = $report['summary'];
    $dateParams = ['from' => $report['from']->toDateString(), 'to' => $report['to']->toDateString()];
    $cards = [
        ['label' => 'Active Participants', 'value' => number_format($s['participants_active']), 'meta' => 'Current snapshot'],
        ['label' => 'Wallet Credits Available', 'value' => number_format($s['available_credits']), 'meta' => 'Current snapshot'],
        ['label' => 'Orders in Period', 'value' => number_format($s['orders_total']), 'meta' => 'Includes cancellations'],
        ['label' => 'Net Order Credits', 'value' => number_format($s['order_credits']), 'meta' => 'Excludes cancelled orders'],
        ['label' => 'Units Ordered', 'value' => number_format($s['units_sold']), 'meta' => 'Excludes cancelled orders'],
        ['label' => 'Active Products', 'value' => number_format($s['products_active']), 'meta' => 'Current snapshot'],
        ['label' => 'Credits In', 'value' => number_format($s['credits_in']), 'meta' => 'Allocations and refunds in period'],
        ['label' => 'Credits Out', 'value' => number_format($s['credits_out']), 'meta' => 'Deductions and purchases in period'],
    ];
    $maxCredits = max(1, (int) $report['daily']->max('credits'));
@endphp

<div class="rm-page">
    <div class="rm-header">
        <div>
            <h1>Reports &amp; Analytics</h1>
            <p>Monitor participant activity, credits, orders and inventory.</p>
        </div>
        @if($canExport)
            <button type="button" class="rm-btn rm-primary" id="rmOpenExport">Export CSV</button>
        @endif
    </div>

    @if($errors->any())
        <div class="rm-alert">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="rm-card rm-filter-card">
        <form method="GET" action="{{ route('admin.makeme.reports.index') }}" class="rm-filters">
            <label>From <input type="date" name="from" value="{{ $dateParams['from'] }}" required></label>
            <label>To <input type="date" name="to" value="{{ $dateParams['to'] }}" required></label>
            <button type="submit" class="rm-btn rm-primary">Apply Filter</button>
            <a class="rm-btn rm-secondary" href="{{ route('admin.makeme.reports.index') }}">This Month</a>
        </form>
        <p class="rm-note">Order, credit movement and product sales metrics use the selected period. Participant counts, wallet balances and available stock are current snapshots.</p>
    </div>

    @unless($report['orders_ready'])
        <div class="rm-alert">Orders tables are not installed yet. Order metrics will display zero until the Orders module migrations are completed.</div>
    @endunless

    <div class="rm-stats">
        @foreach($cards as $card)
            <div class="rm-stat">
                <span>{{ $card['label'] }}</span>
                <strong>{{ $card['value'] }}</strong>
                <small>{{ $card['meta'] }}</small>
            </div>
        @endforeach
    </div>

    <div class="rm-columns">
        <section class="rm-card">
            <div class="rm-card-header"><h2>Order Status</h2><p>Orders created within the selected period</p></div>
            <div class="rm-breakdown">
                <div><span>Placed</span><strong>{{ number_format($s['orders_placed']) }}</strong></div>
                <div><span>Fulfilled</span><strong>{{ number_format($s['orders_fulfilled']) }}</strong></div>
                <div><span>Cancelled</span><strong>{{ number_format($s['orders_cancelled']) }}</strong></div>
            </div>
        </section>
        <section class="rm-card">
            <div class="rm-card-header"><h2>Credit Movements</h2><p>Movements recorded in the selected period</p></div>
            <div class="rm-breakdown">
                <div><span>Manual allocations</span><strong>{{ number_format($s['manual_credits']) }}</strong></div>
                <div><span>Manual deductions</span><strong>{{ number_format($s['manual_debits']) }}</strong></div>
                <div><span>Purchase deductions</span><strong>{{ number_format($s['purchase_credits']) }}</strong></div>
                <div><span>Refunds</span><strong>{{ number_format($s['refund_credits']) }}</strong></div>
            </div>
        </section>
    </div>

    <section class="rm-card">
        <div class="rm-card-header"><h2>Daily Order Activity</h2><p>Non-cancelled orders, grouped by creation date</p></div>
        @if($report['daily']->isEmpty())
            <div class="rm-empty">No order activity in this period.</div>
        @else
            <div class="rm-bars">
                @foreach($report['daily'] as $day)
                    <div class="rm-bar-row">
                        <span>{{ $day->day }}</span>
                        <div class="rm-bar-track"><div class="rm-bar-fill" style="width: {{ round(((int)$day->credits / $maxCredits) * 100, 2) }}%"></div></div>
                        <strong>{{ number_format($day->credits) }} credits</strong>
                        <small>{{ number_format($day->orders) }} orders</small>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <div class="rm-columns">
        <section class="rm-card">
            <div class="rm-card-header"><h2>Top Products</h2><p>Ranked by non-cancelled units ordered in the period</p></div>
            <div class="rm-table-wrap"><table class="rm-table"><thead><tr><th>Product</th><th>Units</th><th>Credits</th></tr></thead><tbody>
                @forelse($report['topProducts'] as $item)
                    <tr><td><strong>{{ $item->product_name }}</strong><small>{{ $item->sku }}</small></td><td>{{ number_format($item->units) }}</td><td>{{ number_format($item->credits) }}</td></tr>
                @empty<tr><td colspan="3" class="rm-empty">No product sales in this period.</td></tr>@endforelse
            </tbody></table></div>
        </section>
        <section class="rm-card">
            <div class="rm-card-header"><h2>Low Stock Alerts</h2><p>Active products with 5 units or fewer — current snapshot</p></div>
            <div class="rm-table-wrap"><table class="rm-table"><thead><tr><th>Product</th><th>SKU</th><th>Available</th></tr></thead><tbody>
                @forelse($report['lowStock'] as $item)
                    <tr><td>{{ $item->name }}</td><td>{{ $item->sku }}</td><td><span class="rm-alert-pill">{{ number_format($item->stock_quantity) }}</span></td></tr>
                @empty<tr><td colspan="3" class="rm-empty">No low-stock products.</td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>
</div>

@if($canExport)
<div class="rm-overlay" id="rmExportModal" hidden>
    <section class="rm-modal" role="dialog" aria-modal="true" aria-labelledby="rmExportTitle">
        <div class="rm-modal-head"><h2 id="rmExportTitle">Export Reports</h2><button type="button" class="rm-close" data-rm-close aria-label="Close">&times;</button></div>
        <form method="GET" action="{{ route('admin.makeme.reports.export') }}">
            <input type="hidden" name="from" value="{{ $dateParams['from'] }}">
            <input type="hidden" name="to" value="{{ $dateParams['to'] }}">
            <div class="rm-modal-body">
                <p>Download report data as CSV. The selected date range will be applied.</p>
                <label for="rmSection">Report section</label>
                <select id="rmSection" name="section" required>
                    <option value="summary">Summary metrics</option>
                    <option value="products">Top products</option>
                    <option value="daily">Daily orders</option>
                    <option value="low-stock">Low stock snapshot</option>
                </select>
            </div>
            <div class="rm-modal-footer"><button type="button" class="rm-btn rm-secondary" data-rm-close>Cancel</button><button type="submit" class="rm-btn rm-primary">Download CSV</button></div>
        </form>
    </section>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('rmExportModal');
    const openButton = document.getElementById('rmOpenExport');
    if (!modal || !openButton) return;
    let previousFocus;
    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('rm-modal-open');
        previousFocus?.focus();
    }
    openButton.addEventListener('click', () => {
        previousFocus = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('rm-modal-open');
        modal.querySelector('.rm-close')?.focus();
    });
    modal.querySelectorAll('[data-rm-close]').forEach(button => button.addEventListener('click', closeModal));
    modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', event => { if (!modal.hidden && event.key === 'Escape') closeModal(); });
});
</script>
@endpush
