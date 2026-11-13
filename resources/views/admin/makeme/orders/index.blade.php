@extends('admin.makeme.layouts.app')

@section('title', 'Orders Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/makeme-admin-orders.css') }}">
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
            <p>Place and manage participant orders, credits and stock.</p>
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
            <p>View each order and its fulfillment status.</p>
        </div>
        <form class="om-filters" method="GET" action="{{ route('admin.makeme.orders.index') }}">
            <input type="search" name="q" maxlength="100" placeholder="Search reference or participant ID" value="{{ request('q', '') }}">
            <select name="status">
                <option value="all">All statuses</option>
                @foreach(['placed' => 'Placed', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="om-btn om-primary">Filter</button>
            <a class="om-btn om-secondary" href="{{ route('admin.makeme.orders.index') }}">Reset</a>
        </form>
        <div class="om-table-wrap">
            <table class="om-table">
                <thead><tr><th>Reference</th><th>Participant</th><th>Items</th><th>Total Credits</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
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
                        <td>{{ $order->created_at?->format('d M Y') }}</td>
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
                    <tr><td colspan="7" class="om-empty">No orders found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())<div class="om-pagination">{{ $orders->links() }}</div>@endif
    </div>
</div>

<script type="application/json" id="omOrderData">@json($orderPayloads)</script>
<script type="application/json" id="omParticipantData">@json($participantOptions)</script>
<script type="application/json" id="omProductData">@json($productOptions)</script>

@if($canCreate)
<div class="om-overlay" id="omCreateModal" hidden>
    <section class="om-modal om-wide" role="dialog" aria-modal="true" aria-labelledby="omCreateTitle">
        <div class="om-modal-head"><div><h2 id="omCreateTitle">Create Participant Order</h2><p>Credits and product stock are deducted once the order is saved.</p></div><button type="button" class="om-close" data-om-close aria-label="Close">&times;</button></div>
        <form method="POST" action="{{ route('admin.makeme.orders.store') }}" id="omCreateForm">
            @csrf
            <input type="hidden" name="_modal" value="create">
            <input type="hidden" name="request_key" id="omRequestKey" value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <div class="om-modal-body">
                <div class="om-field"><label for="omParticipant">Participant</label><select id="omParticipant" name="participant_id" required><option value="">Select a participant</option></select></div>
                <div class="om-balance">Available wallet credits: <strong id="omWalletBalance">—</strong></div>
                <div class="om-section-head"><h3>Products</h3><button type="button" class="om-btn om-secondary" id="omAddItem">+ Add Product</button></div>
                <div id="omItems"></div>
                <div class="om-total">Order total <strong id="omOrderTotal">0 credits</strong></div>
                <p class="om-inline-warning" id="omBalanceWarning" hidden>Insufficient wallet credits for this order.</p>
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
            <div class="om-modal-body"><p>Cancellation will refund the participant's credits and restore all ordered stock.</p>
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
@endpush
