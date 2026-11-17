@extends('admin.makeme.layouts.app')

@section('title', 'Orders Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/makeme-admin-orders.css') }}">
<style>
    
/* ==========================================
   PROJECT MAKEME - ORDERS PAGINATION
========================================== */

.om-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    margin: 28px 0 12px;
}

.om-pagination nav {
    width: auto !important;
    max-width: 100%;
}

.om-pagination ul.pagination {
    display: flex !important;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 6px;
    margin: 0 !important;
    padding: 0 !important;
    list-style: none !important;
}

.om-pagination .page-item {
    display: inline-flex !important;
    padding: 0 !important;
    margin: 0 !important;
    list-style: none !important;
}

.om-pagination .page-link {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;

    min-width: 36px !important;
    width: auto !important;
    height: 36px !important;
    min-height: 36px !important;
    padding: 0 11px !important;

    background: #ffffff !important;
    color: #242424 !important;
    border: 1px solid #e2e5ea !important;
    border-radius: 8px !important;

    font-size: 12px !important;
    font-weight: 700 !important;
    line-height: 1 !important;
    text-decoration: none !important;
    box-shadow: none !important;

    transition: background .2s ease,
                border-color .2s ease,
                color .2s ease;
}

.om-pagination .page-link:hover {
    background: #fff4de !important;
    border-color: #f4a116 !important;
    color: #8a5900 !important;
}

.om-pagination .page-item.active .page-link {
    background: #f4a116 !important;
    border-color: #f4a116 !important;
    color: #151515 !important;
}

.om-pagination .page-item.disabled .page-link {
    background: #f5f5f5 !important;
    border-color: #ebebeb !important;
    color: #a0a0a0 !important;
    opacity: 0.6;
    cursor: not-allowed;
}

.om-pagination .page-link:focus-visible {
    outline: 2px solid #f4a116;
    outline-offset: 2px;
}

@media (max-width: 600px) {
    .om-pagination {
        margin-top: 20px;
    }

    .om-pagination ul.pagination {
        gap: 4px;
    }

    .om-pagination .page-link {
        min-width: 32px !important;
        height: 32px !important;
        min-height: 32px !important;
        padding: 0 8px !important;
        font-size: 11px !important;
    }
}


/* Received/delivery date filters */
.om-date-filters{display:grid;grid-template-columns:repeat(3,minmax(170px,1fr));gap:10px;width:100%;flex-basis:100%}
.om-date-filters label{display:flex;flex-direction:column;gap:6px;font-size:11px;font-weight:700;color:inherit;min-width:0}
.om-date-filters input{box-sizing:border-box;width:100%;min-width:0;min-height:39px;padding:9px 11px;border:1px solid #d4d7dd;border-radius:9px;background:#fff;color:#242424;font:inherit;font-size:13px}
.om-date-filters input:focus{outline:2px solid rgba(244,161,22,.4);border-color:#f4a116}
@media(max-width:760px){.om-date-filters{grid-template-columns:1fr}}
</style>
<style>
    .om-export-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    margin: 18px 0;
    padding: 14px 18px;
    background: #f8f9fb;
    border: 1px solid #e8eaee;
    border-radius: 12px;
}

.om-export-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.om-export-info strong {
    font-size: 13px;
    color: #202127;
}

.om-export-info span {
    font-size: 12px;
    color: #777c87;
}

.om-export-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}

.om-report-btn {
    padding: 10px 15px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s, transform .2s;
}

.om-report-btn:hover {
    transform: translateY(-1px);
}

.om-export-btn {
    background: #f4a116;
    color: #171717;
    border: 1px solid #f4a116;
}

.om-print-btn {
    background: #fff;
    color: #282828;
    border: 1px solid #d9dce2;
}

@media (max-width: 600px) {
    .om-export-actions {
        width: 100%;
    }

    .om-report-btn {
        flex: 1;
    }
}
</style>
@endpush
@section('content')
@php
    $admin = auth('web')->user();
    $canCreate = $admin && ($admin->hasRole('Super Admin', 'web') || $admin->can('makeme.orders.create'));
    $canUpdate = $admin && ($admin->hasRole('Super Admin', 'web') || $admin->can('makeme.orders.update-status'));

    $participantLabel = function ($participant) {
        $name = trim(implode(' ', array_filter([
            (string) ($participant->first_name ?? ''),
            (string) ($participant->other_name ?? ''),
            (string) ($participant->surname ?? ''),
        ], fn ($value) => trim($value) !== '')));
        return $name ?: ($participant->full_name ?? $participant->name ?? 'Participant #' . $participant->getKey());
    };

    $orderPayloads = [];
    $participantOptions = $participants->map(fn ($participant) => [
        'id' => $participant->getKey(),
        'name' => $participantLabel($participant),
        'email' => $participant->email ?? '',
        'balance' => (int) ($participant->wallet?->balance ?? 0),
    ])->values()->all();
    $productOptions = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'price' => (int) $product->credit_price,
        'stock' => (int) $product->stock_quantity,
        'limit' => (int) ($product->max_per_participant ?? 0),
    ])->values()->all();
@endphp

<div class="om-page">
    <div class="om-header">
        <div>
            <h1>Orders Management</h1>
            <p>Place and manage kreativ orders, credits and stock.</p>
        </div>
        @if($canCreate)
            <button type="button" class="om-btn om-primary" id="omOpenCreate">+ Create Order</button>
        @endif
    </div>

    @if(session('success'))
        <div class="om-alert om-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="om-alert om-error">
            <strong>Unable to complete the request:</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="om-stats">
        <div class="om-stat"><span>All Orders</span><strong>{{ number_format($stats['all']) }}</strong></div>
        <div class="om-stat"><span>Placed</span><strong>{{ number_format($stats['placed']) }}</strong></div>
        <div class="om-stat"><span>Fulfilled</span><strong>{{ number_format($stats['fulfilled']) }}</strong></div>
        <div class="om-stat"><span>Cancelled</span><strong>{{ number_format($stats['cancelled']) }}</strong></div>
    </div>

    <div class="om-card">
        <div class="om-card-header">
            <h2>Order Records</h2>
            <p>Placed orders appear first, followed by fulfilled and cancelled orders.</p>
        </div>
        <form id="omLiveFilters" class="om-filters" method="GET" action="{{ route('admin.makeme.orders.index') }}">
            <input id="omLiveSearch" type="search" name="q" maxlength="100" autocomplete="off" placeholder="Search reference, Kreativ name, email or ID" aria-label="Search orders" value="{{ request('q', '') }}">
            <select id="omLiveStatus" name="status">
                <option value="all">All statuses</option>
                @foreach(['placed' => 'Placed', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="om-date-filters">
                <label>Received from (WAT)
                    <input type="datetime-local" name="received_from" id="omReceivedFrom" value="{{ request('received_from') }}">
                </label>
                <label>Received through (WAT)
                    <input type="datetime-local" name="received_to" id="omReceivedTo" value="{{ request('received_to') }}">
                </label>
                <label>Scheduled delivery date (WAT)
                    <input type="date" name="delivery_date" id="omDeliveryDate" value="{{ request('delivery_date') }}">
                </label>
            </div>
            <button type="submit" class="om-btn om-primary">Apply filters</button>
            <a id="omLiveReset" class="om-btn om-secondary" href="{{ route('admin.makeme.orders.index') }}">Reset</a>
        </form>
        
        <div class="om-export-toolbar">
        
            <div class="om-export-info">
                <strong>Order Reports</strong>
                <span>
                    Export or print all orders matching your filters.
                </span>
            </div>
        
            <div class="om-export-actions">
        
                <button
                    type="submit"
                    form="omLiveFilters"
                    name="output"
                    value="csv"
                    class="om-report-btn om-export-btn"
                >
                    ↓ Export CSV
                </button>
        
                <button
                    type="submit"
                    form="omLiveFilters"
                    name="output"
                    value="print"
                    formtarget="_blank"
                    class="om-report-btn om-print-btn"
                >
                    Print Orders
                </button>
        
            </div>
        
        </div>

        <div class="om-table-wrap">
            <table class="om-table">
                <thead><tr><th>Reference</th><th>kreativ</th><th>Items</th><th>Total Credits</th><th>Status</th><th>Received (WAT)</th><th>Scheduled Delivery</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($orders as $order)
                    @php
                        $name = $order->participant ? $participantLabel($order->participant) : 'Unavailable participant';
                        $orderPayloads[] = [
                            'id' => $order->id,
                            'reference' => $order->reference,
                            'participant' => $name,
                            'participant_id' => $order->participant_id,
                            'status' => $order->status,
                            'total' => (int) $order->total_credits,
                            'notes' => $order->notes,
                            'cancellation_reason' => $order->cancellation_reason,
                            'date' => $order->created_at?->format('d M Y, h:i A'),
                            'items' => $order->items->map(fn ($item) => [
                                'name' => $item->product_name,
                                'sku' => $item->sku,
                                'quantity' => (int) $item->quantity,
                                'price' => (int) $item->unit_price,
                                'total' => (int) $item->line_total,
                            ])->values()->all(),
                            'fulfill_url' => route('admin.makeme.orders.fulfill', ['order' => $order->id]),
                            'cancel_url' => route('admin.makeme.orders.cancel', ['order' => $order->id]),
                        ];
                    @endphp
                    <tr>
                        <td><strong>{{ $order->reference }}</strong></td>
                        <td>{{ $name }}<small>#{{ $order->participant_id }}</small></td>
                        <td>{{ number_format($order->items->sum('quantity')) }} units</td>
                        <td><strong>{{ number_format($order->total_credits) }}</strong></td>
                        <td><span class="om-badge om-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                        <td>{{ $order->created_at?->copy()->timezone('Africa/Lagos')->format('d M Y, h:i A') }}</td>
                        <td>
                            @if($order->created_at)
                                <strong>{{ \App\Support\MakeMeDeliverySchedule::deliveryDate($order->created_at) }}</strong>
                                <small>Evening · WAT</small>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <div class="om-actions">
                                <button type="button" class="om-link" data-om-details="{{ $order->id }}">Details</button>
                                @if($canUpdate && $order->status === 'placed')
                                    <button type="button" class="om-link" data-om-fulfill="{{ $order->id }}">Fulfill</button>
                                    <button type="button" class="om-link om-danger-text" data-om-cancel="{{ $order->id }}">Cancel</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="om-empty">No orders found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
       {{-- ORDERS PAGINATION --}}
@if($orders->hasPages())
    <div class="om-pagination">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
@endif
    </div>
</div>

<script type="application/json" id="omOrderData">@json($orderPayloads)</script>
<script type="application/json" id="omParticipantData">@json($participantOptions)</script>
<script type="application/json" id="omProductData">@json($productOptions)</script>

@if($canCreate)
<div class="om-overlay" id="omCreateModal" hidden>
    <section class="om-modal om-wide" role="dialog" aria-modal="true" aria-labelledby="omCreateTitle">
        <div class="om-modal-head"><div><h2 id="omCreateTitle">Create kreativ Order</h2><p>Credits and product stock are deducted once the order is saved.</p></div><button type="button" class="om-close" data-om-close aria-label="Close">&times;</button></div>
        <form method="POST" action="{{ route('admin.makeme.orders.store') }}" id="omCreateForm">
            @csrf
            <input type="hidden" name="_modal" value="create">
            <input type="hidden" name="request_key" id="omRequestKey" value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <div class="om-modal-body">
                <div class="om-field"><label for="omParticipant">Kreativ</label><select id="omParticipant" name="participant_id" required><option value="">Select a kreativ</option></select></div>
                <div class="om-balance">Available kolomoni credits: <strong id="omWalletBalance">—</strong></div>
                <div class="om-section-head"><h3>Products</h3><button type="button" class="om-btn om-secondary" id="omAddItem">+ Add Product</button></div>
                <div id="omItems"></div>
                <div class="om-total">Order total <strong id="omOrderTotal">0 credits</strong></div>
                <p class="om-inline-warning" id="omBalanceWarning" hidden>Insufficient kolomoni credits for this order.</p>
                <div class="om-field"><label for="omNotes">Notes (optional)</label><textarea rows="3" maxlength="2000" id="omNotes" name="notes" placeholder="Optional instructions">{{ old('notes') }}</textarea></div>
            </div>
            <div class="om-modal-foot"><button type="button" class="om-btn om-secondary" data-om-close>Close</button><button type="submit" class="om-btn om-primary" id="omSaveOrder">Place Order</button></div>
        </form>
    </section>
</div>
@endif

<div class="om-overlay" id="omDetailsModal" hidden>
    <section class="om-modal om-wide" role="dialog" aria-modal="true" aria-labelledby="omDetailsTitle">
        <div class="om-modal-head"><div><h2 id="omDetailsTitle">Order Details</h2><p id="omDetailsSubtitle"></p></div><button type="button" class="om-close" data-om-close aria-label="Close">&times;</button></div>
        <div class="om-modal-body">
            <div class="om-detail-grid" id="omDetailMeta"></div>
            <div class="om-table-wrap"><table class="om-table"><thead><tr><th>Product</th><th>Qty</th><th>Unit price</th><th>Total</th></tr></thead><tbody id="omDetailItems"></tbody></table></div>
            <div class="om-total">Total <strong id="omDetailTotal"></strong></div>
            <div id="omDetailNotes" class="om-detail-notes"></div>
        </div>
        <div class="om-modal-foot"><button type="button" class="om-btn om-secondary" data-om-close>Close</button></div>
    </section>
</div>

@if($canUpdate)
<div class="om-overlay" id="omFulfillModal" hidden>
    <section class="om-modal om-small" role="dialog" aria-modal="true" aria-labelledby="omFulfillTitle">
        <div class="om-modal-head"><div><h2 id="omFulfillTitle">Fulfill Order</h2><p id="omFulfillSubtitle"></p></div><button type="button" class="om-close" data-om-close aria-label="Close">&times;</button></div>
        <form method="POST" id="omFulfillForm">@csrf
            <div class="om-modal-body"><p>Mark this order as fulfilled? Credits and stock were already deducted when the order was placed.</p></div>
            <div class="om-modal-foot"><button type="button" class="om-btn om-secondary" data-om-close>Back</button><button type="submit" class="om-btn om-primary">Confirm Fulfillment</button></div>
        </form>
    </section>
</div>
<div class="om-overlay" id="omCancelModal" hidden>
    <section class="om-modal om-small" role="dialog" aria-modal="true" aria-labelledby="omCancelTitle">
        <div class="om-modal-head"><div><h2 id="omCancelTitle">Cancel Order</h2><p id="omCancelSubtitle"></p></div><button type="button" class="om-close" data-om-close aria-label="Close">&times;</button></div>
        <form method="POST" id="omCancelForm">@csrf
            <input type="hidden" name="_modal" value="cancel">
            <input type="hidden" name="_order_id" id="omCancelOrderId">
            <div class="om-modal-body"><p>Cancellation will refund the kreativs's credits and restore all ordered stock.</p>
                <div class="om-field"><label for="omCancelReason">Reason for cancellation</label><textarea id="omCancelReason" name="reason" minlength="5" maxlength="2000" rows="4" required>{{ old('reason') }}</textarea></div>
            </div>
            <div class="om-modal-foot"><button type="button" class="om-btn om-secondary" data-om-close>Back</button><button type="submit" class="om-btn om-danger">Cancel and Refund</button></div>
        </form>
    </section>
</div>
@endif


@if($errors->any())
    @php
        $restoreData = [
            'modal' => old('_modal'),
            'participant_id' => old('participant_id'),
            'items' => old('items', []),
            'reason' => old('reason', ''),
            'order_id' => old('_order_id'),
        ];
    @endphp

    <script type="application/json" id="omRestoreData">@json($restoreData)</script>
@endif


@endsection

@push('scripts')
<script src="{{ asset('js/makeme-admin-orders.js') }}"></script>

<script>
/* Live form filtering without interfering with existing order-modal handlers.
   Uses server-side GET search and preserves focus across navigation. */
(function () {
    const form = document.getElementById('omLiveFilters');
    if (!form) return;
    const search = document.getElementById('omLiveSearch');
    const status = document.getElementById('omLiveStatus');
    const reset = document.getElementById('omLiveReset');
    const receivedFrom = document.getElementById('omReceivedFrom');
    const receivedTo = document.getElementById('omReceivedTo');
    const deliveryDate = document.getElementById('omDeliveryDate');
    const storageKey = 'makeme_orders_search_focus';
    let timer;

    try {
        const saved = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
        if (saved && saved.path === location.pathname && saved.value === search.value) {
            search.focus({preventScroll: true});
            search.setSelectionRange(Math.min(saved.cursor, search.value.length), Math.min(saved.cursor, search.value.length));
        }
        sessionStorage.removeItem(storageKey);
    } catch (_) {}

    function navigate() {
        const url = new URL(form.action, location.origin);
        const q = search.value.trim();
        if (q) url.searchParams.set('q', q);
        if (status.value && status.value !== 'all') url.searchParams.set('status', status.value);
        if (receivedFrom.value) url.searchParams.set('received_from', receivedFrom.value);
        if (receivedTo.value) url.searchParams.set('received_to', receivedTo.value);
        if (deliveryDate.value) url.searchParams.set('delivery_date', deliveryDate.value);
        // Always return to the first page after a new search/filter.
        const next = url.pathname + url.search;
        if (next === location.pathname + location.search) return;
        try { sessionStorage.setItem(storageKey, JSON.stringify({path: url.pathname, value: search.value, cursor: search.selectionStart || 0})); } catch (_) {}
        location.assign(next);
    }

    search.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(navigate, 450);
    });
    [status, receivedFrom, receivedTo, deliveryDate].forEach(field => {
        field.addEventListener('change', function () {
            clearTimeout(timer);
            navigate();
        });
    });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        clearTimeout(timer);
        navigate();
    });
    reset.addEventListener('click', function () {
        try { sessionStorage.removeItem(storageKey); } catch (_) {}
    });
})();
</script>
@endpush
