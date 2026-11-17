
@extends('admin.makeme.layouts.app')

@section('title', 'Products & Stock')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/makeme-admin-products.css') }}">
@endpush

@section('content')
@php
    $admin = auth('web')->user();
    $super = $admin->hasRole('Super Admin', 'web');

    $canCreate = $super || $admin->can('makeme.products.create');
    $canEdit = $super || $admin->can('makeme.products.edit');
    $canStock = $super || $admin->can('makeme.products.stock.adjust');
    $canArchive = $super || $admin->can('makeme.products.delete');

    $categoryLabels = [
        'general' => 'General',
        'groceries' => 'Groceries',
        'fashion_designing' => 'Fashion Designing',
        'shoe_making' => 'Shoe Making',
        'hair_styling' => 'Hair Styling',
        'photography' => 'Photography',
        'makeup_artistry' => 'Makeup Artistry',
        'modelling' => 'Modelling',
    ];
@endphp

<div class="pm-page">
    <div class="pm-heading">
        <div>
            <h1>Products & Stock</h1>
            <p>Manage products, credit pricing and inventory.</p>
        </div>

        @if($canCreate)
            <button type="button" class="pm-btn pm-primary"
                    data-pm-open="pmCreateModal">
                + Add Product
            </button>
        @endif
    </div>

    @if(session('success'))
        <div class="pm-alert pm-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="pm-alert pm-error">
            <strong>Please check the following:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="pm-stats">
        @foreach([
            ['Total Products', $stats['total']],
            ['Active Products', $stats['active']],
            ['Available Units', $stats['available_units']],
            ['Low Stock', $stats['low_stock']],
            ['Out of Stock', $stats['out_of_stock']],
        ] as $stat)
            <div class="pm-stat">
                <span>{{ $stat[0] }}</span>
                <strong>{{ number_format($stat[1]) }}</strong>
            </div>
        @endforeach
    </div>

    <div class="pm-panel">
        <div class="pm-panel-heading">
            <div>
                <h2>Product Inventory</h2>
                <p>View and manage available products.</p>
            </div>
        </div>

        <form method="GET"
              action="{{ route('admin.makeme.products.index') }}"
              class="pm-filters">

            <input name="q" type="search"
                   placeholder="Search name or SKU"
                   value="{{ request('q') }}">

            <select name="category">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}"
                        @selected(request('category') === $category)>
                        {{ $categoryLabels[$category] ?? ucwords(str_replace('_', ' ', $category)) }}
                    </option>
                @endforeach
            </select>

            <select name="status">
                <option value="">All Statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                <option value="low_stock" @selected(request('status') === 'low_stock')>Low Stock</option>
                <option value="out_of_stock" @selected(request('status') === 'out_of_stock')>Out of Stock</option>
            </select>

            <button class="pm-btn pm-primary" type="submit">
                Filter
            </button>

            <a class="pm-btn pm-secondary"
               href="{{ route('admin.makeme.products.index') }}">
                Reset
            </a>
        </form>

        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Credit Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        @if($canEdit || $canStock || $canArchive)
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @forelse($products as $product)
                    @php
                        $payload = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'unit_label' => $product->unit_label,
                            'sku' => $product->sku,
                            'description' => $product->description,
                            'category' => $product->category,
                            'credit_price' => $product->credit_price,
                            'stock_quantity' => $product->stock_quantity,
                            'max_per_participant' => $product->max_per_participant,
                            'is_active' => (bool) $product->is_active,
                            'image_url' => $product->image_path
                                ? \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($product->image_path, '/'))
                                : null,
                            'update_url' => route('admin.makeme.products.update', $product),
                            'stock_url' => route('admin.makeme.products.stock.adjust', $product),
                            'archive_url' => route('admin.makeme.products.destroy', $product),
                        ];
                    @endphp

                    <tr>
                        <td>
                            <div class="pm-product">
                                @if($product->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($product->image_path, '/')) }}"
                                         alt="{{ $product->name }}">
                                @else
                                    <div class="pm-placeholder">▦</div>
                                @endif

                                <div>
                                    <strong>{{ $product->name }}</strong>
                                    @if($product->unit_label)
                                        <small>{{ $product->unit_label }}</small>
                                    @endif
                                    <small>{{ $product->sku }}</small>
                                </div>
                            </div>
                        </td>

                        <td>
                            {{ $categoryLabels[$product->category] ?? $product->category }}
                        </td>

                        <td>{{ number_format($product->credit_price) }} credits</td>

                        <td>
                            <strong>{{ number_format($product->stock_quantity) }}</strong>

                            @if($product->stock_quantity === 0)
                                <span class="pm-tag pm-red">Out of Stock</span>
                            @elseif($product->stock_quantity <= 5)
                                <span class="pm-tag pm-amber">Low Stock</span>
                            @else
                                <span class="pm-tag pm-green">In Stock</span>
                            @endif
                        </td>

                        <td>
                            <span class="pm-tag {{ $product->is_active ? 'pm-green' : 'pm-gray' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>

                        @if($canEdit || $canStock || $canArchive)
                            <td>
                                <div class="pm-actions">
                                    @if($canEdit)
                                        <button type="button"
                                                class="pm-link"
                                                data-pm-edit="{{ $product->id }}">
                                            Edit
                                        </button>
                                    @endif

                                    @if($canStock)
                                        <button type="button"
                                                class="pm-link"
                                                data-pm-stock="{{ $product->id }}">
                                            Adjust Stock
                                        </button>
                                    @endif

                                    @if($canArchive && $product->is_active)
                                        <button type="button"
                                                class="pm-link pm-danger-text"
                                                data-pm-archive="{{ $product->id }}">
                                            Archive
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>

                    <script type="application/json"
                            id="pmProduct{{ $product->id }}">
                        @json($payload)
                    </script>
                @empty
                    <tr>
                        <td colspan="{{ ($canEdit || $canStock || $canArchive) ? 6 : 5 }}"
                            class="pm-empty">
                            No products found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pm-pagination">
            {{ $products->links() }}
        </div>
    </div>

    <div class="pm-panel pm-history">
        <div class="pm-panel-heading">
            <div>
                <h2>Recent Stock Movements</h2>
                <p>Latest 20 recorded inventory changes.</p>
            </div>
        </div>

        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Movement</th>
                        <th>Before / After</th>
                        <th>Reason</th>
                        <th>Administrator</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at->format('d M Y, H:i') }}</td>
                        <td>{{ $movement->product?->name ?? 'Unknown Product' }}</td>
                        <td>
                            <span class="pm-tag {{ in_array($movement->type, ['add', 'initial']) ? 'pm-green' : 'pm-amber' }}">
                                {{ ucfirst($movement->type) }}
                                ({{ number_format($movement->quantity_change) }})
                            </span>
                        </td>
                        <td>
                            {{ $movement->quantity_before }}
                            →
                            {{ $movement->quantity_after }}
                        </td>
                        <td>{{ $movement->reason ?? '—' }}</td>
                        <td>
                            {{ trim(($movement->administrator?->first_name ?? '') . ' ' . ($movement->administrator?->surname ?? '')) ?: 'System' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="pm-empty">
                            No stock movements recorded yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- CREATE / EDIT PRODUCT MODAL --}}
@if($canCreate || $canEdit)
<div class="pm-overlay" id="pmProductModal" hidden>
    <section class="pm-modal" role="dialog" aria-modal="true"
             aria-labelledby="pmProductModalTitle">
        <div class="pm-modal-head">
            <div>
                <h2 id="pmProductModalTitle">Add Product</h2>
                <p>Enter product information and pricing.</p>
            </div>
            <button type="button" class="pm-close" data-pm-close
                    aria-label="Close">&times;</button>
        </div>

        <form id="pmProductForm"
              method="POST"
              enctype="multipart/form-data"
              action="{{ route('admin.makeme.products.store') }}">
            @csrf
            <input type="hidden" name="_method" id="pmProductMethod" value="POST">
            <input type="hidden" name="_modal" value="product">
            <input type="hidden" name="_product_id" id="pmProductId">

            <div class="pm-modal-body">
                <div class="pm-form-grid">
                    <div class="pm-field">
                        <label>Product Name *</label>
                        <input name="name" id="pmName" maxlength="180" required>
                    </div>

                    <div class="pm-field">
                        <label for="pmUnitLabel">Unit / Package Size</label>
                        <input name="unit_label" id="pmUnitLabel" maxlength="100"
                               placeholder="e.g. 5kg Bag, 500g Pack, 1 Tuber">
                        <small>What does one purchased unit contain?</small>
                    </div>

                   <div class="pm-field">
                        <label for="pmSku">SKU / Stock Code</label>
                    
                        <input
                            type="text"
                            id="pmSku"
                            value="Automatically generated on save"
                            readonly
                        >
                    
                        <small>
                            A unique SKU is generated automatically when creating a product.
                        </small>
                    </div>

                    <div class="pm-field">
                        <label>Category *</label>
                        <select name="category" id="pmCategory" required>
                            @foreach($categories as $category)
                                <option value="{{ $category }}">
                                    {{ $categoryLabels[$category] ?? $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pm-field">
                        <label>Credit Price *</label>
                        <input type="number" name="credit_price"
                               id="pmPrice" min="0" max="999999999" required>
                    </div>

                    <div class="pm-field" id="pmInitialStockField">
                        <label>Initial Stock *</label>
                        <input type="number" name="stock_quantity"
                               id="pmInitialStock" min="0" max="999999999"
                               required>
                    </div>

                    <div class="pm-field">
                        <label>Maximum Per Participant</label>
                        <input type="number" name="max_per_participant"
                               id="pmMax" min="1" max="1000">
                    </div>

                    <div class="pm-field pm-full">
                        <label>Description</label>
                        <textarea name="description" id="pmDescription"
                                  rows="3" maxlength="4000"></textarea>
                    </div>

                    <div class="pm-field pm-full">
                        <label>Product Image</label>
                        <input type="file" name="image" id="pmImage"
                               accept=".jpg,.jpeg,.png,.webp">
                        <small>JPEG, PNG or WEBP, maximum 3 MB.</small>
                        <img id="pmImagePreview" class="pm-image-preview"
                             alt="Current product image" hidden>
                    </div>

                    <div class="pm-field pm-full">
                        <label class="pm-check">
                            <input type="checkbox" name="is_active"
                                   value="1" id="pmActive" checked>
                            Product is active
                        </label>
                    </div>
                </div>
            </div>

            <div class="pm-modal-foot">
                <button type="button" class="pm-btn pm-secondary"
                        data-pm-close>Cancel</button>
                <button type="submit" class="pm-btn pm-primary"
                        id="pmProductSave">Save Product</button>
            </div>
        </form>
    </section>
</div>
@endif

{{-- STOCK ADJUSTMENT MODAL --}}
@if($canStock)
<div class="pm-overlay" id="pmStockModal" hidden>
    <section class="pm-modal pm-small" role="dialog" aria-modal="true"
             aria-labelledby="pmStockTitle">
        <div class="pm-modal-head">
            <div>
                <h2 id="pmStockTitle">Adjust Stock</h2>
                <p id="pmStockSubtitle"></p>
            </div>
            <button type="button" class="pm-close" data-pm-close
                    aria-label="Close">&times;</button>
        </div>

        <form id="pmStockForm" method="POST">
            @csrf
            <input type="hidden" name="_modal" value="stock">
            <input type="hidden" name="_product_id" id="pmStockProductId">

            <div class="pm-modal-body">
                <div class="pm-field">
                    <label>Adjustment Type</label>
                    <select name="type" id="pmStockType" required>
                        <option value="add">Add Stock</option>
                        <option value="remove">Remove Stock</option>
                        <option value="set">Set Exact Quantity</option>
                    </select>
                </div>

                <div class="pm-field">
                    <label>Quantity</label>
                    <input type="number" name="quantity"
                           id="pmStockQuantity" min="0"
                           max="999999999" required>
                </div>

                <div class="pm-field">
                    <label>Reason for Adjustment</label>
                    <textarea name="reason" id="pmStockReason"
                              maxlength="255" rows="3"
                              placeholder="e.g. New stock received"
                              required></textarea>
                </div>
            </div>

            <div class="pm-modal-foot">
                <button type="button" class="pm-btn pm-secondary"
                        data-pm-close>Cancel</button>
                <button type="submit" class="pm-btn pm-primary">
                    Save Adjustment
                </button>
            </div>
        </form>
    </section>
</div>
@endif

{{-- ARCHIVE CONFIRMATION --}}
@if($canArchive)
<div class="pm-overlay" id="pmArchiveModal" hidden>
    <section class="pm-modal pm-small" role="dialog" aria-modal="true"
             aria-labelledby="pmArchiveTitle">
        <div class="pm-modal-head">
            <div>
                <h2 id="pmArchiveTitle">Archive Product</h2>
                <p>This will make the product inactive.</p>
            </div>
            <button type="button" class="pm-close" data-pm-close
                    aria-label="Close">&times;</button>
        </div>

        <form id="pmArchiveForm" method="POST">
            @csrf
            @method('DELETE')

            <div class="pm-modal-body">
                <p id="pmArchiveMessage"></p>
            </div>

            <div class="pm-modal-foot">
                <button type="button" class="pm-btn pm-secondary"
                        data-pm-close>Cancel</button>
                <button type="submit" class="pm-btn pm-danger">
                    Archive Product
                </button>
            </div>
        </form>
    </section>
</div>
@endif

@if($errors->any())
    <div hidden id="pmValidation"
         data-modal="{{ old('_modal') }}"
         data-product-id="{{ old('_product_id') }}"
         data-values='@json(old())'></div>
@endif

@endsection

@push('scripts')
<script src="{{ asset('js/makeme-admin-products.js') }}"></script>
<script>
// Supplements the existing product-modal script without replacing its handlers.
document.addEventListener('DOMContentLoaded', function () {
    const unitInput = document.getElementById('pmUnitLabel');
    if (!unitInput) return;

    document.addEventListener('click', function (event) {
        const edit = event.target.closest('[data-pm-edit]');
        if (edit) {
            const productId = edit.getAttribute('data-pm-edit');
            const payloadNode = document.getElementById('pmProduct' + productId);
            if (!payloadNode) return;
            try {
                const payload = JSON.parse(payloadNode.textContent);
                queueMicrotask(function () {
                    unitInput.value = payload.unit_label ?? '';
                });
            } catch (error) {
                console.error('Could not read product details', error);
            }
            return;
        }

        if (event.target.closest('[data-pm-open="pmCreateModal"]')) {
            queueMicrotask(function () { unitInput.value = ''; });
        }
    });

    const validation = document.getElementById('pmValidation');
    if (validation && validation.dataset.modal === 'product') {
        try {
            const values = JSON.parse(validation.dataset.values || '{}');
            if (Object.prototype.hasOwnProperty.call(values, 'unit_label')) {
                unitInput.value = values.unit_label ?? '';
            }
        } catch (error) {
            console.error('Could not restore product input', error);
        }
    }
});
</script>
@endpush
