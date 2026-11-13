<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MakeMeOrder;
use App\Models\MakeMeOrderItem;
use App\Models\MakeMeParticipant;
use App\Models\MakeMeProduct;
use App\Models\MakeMeWallet;
use App\Services\MakeMeActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MakeMeOrderController extends Controller
{
    private const MAX_CREDITS = 999999999;

    private function authorizeAction(string $permission): void
    {
        $admin = auth('web')->user();
        abort_unless(
            $admin && (int) $admin->user_type === 1 &&
            (int) $admin->user_status === 1 &&
            $admin->applicant_type !== 'WAITLIST' &&
            ($admin->hasRole('Super Admin', 'web') || $admin->can($permission)),
            403
        );
    }

    public function index(Request $request)
    {
        $this->authorizeAction('makeme.orders.view');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'placed', 'fulfilled', 'cancelled'])],
        ]);

        $ordersQuery = MakeMeOrder::query()->with(['participant', 'items', 'creator']);
        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $ordersQuery->where(function ($query) use ($term) {
                $query->where('reference', 'like', "%{$term}%")
                    ->orWhere('participant_id', $term);
            });
        }
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $ordersQuery->where('status', $filters['status']);
        }

        $orders = $ordersQuery->latest('id')->paginate(15)->withQueryString();

        // Only active participants can have new orders placed on their behalf.
        $participants = MakeMeParticipant::query()
            ->where('status', 'active')
            ->with('wallet')
            ->orderBy('id')
            ->get();

        $products = MakeMeProduct::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'credit_price', 'stock_quantity', 'max_per_participant']);

        $stats = [
            'all' => MakeMeOrder::count(),
            'placed' => MakeMeOrder::where('status', 'placed')->count(),
            'fulfilled' => MakeMeOrder::where('status', 'fulfilled')->count(),
            'cancelled' => MakeMeOrder::where('status', 'cancelled')->count(),
        ];

        return view('admin.makeme.orders.index', compact('orders', 'participants', 'products', 'stats'));
    }

    public function store(Request $request)
    {
        $this->authorizeAction('makeme.orders.create');
        $validated = $request->validate([
            'request_key' => ['required', 'uuid'],
            'participant_id' => ['required', 'integer', 'exists:makeme_participants,id'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:makeme_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $existing = MakeMeOrder::where('request_key', $validated['request_key'])->first();
        if ($existing) {
            return redirect()->route('admin.makeme.orders.index')
                ->with('success', 'This order was already submitted: ' . $existing->reference);
        }

        $order = DB::transaction(function () use ($validated) {
            // Serialize orders for the same participant, including first purchases.
            $participant = MakeMeParticipant::query()->lockForUpdate()
                ->findOrFail($validated['participant_id']);

            if ($participant->status !== 'active') {
                throw ValidationException::withMessages([
                    'participant_id' => 'Only active participants can place orders.',
                ]);
            }

            // Repeat idempotency check under the participant lock.
            $existing = MakeMeOrder::where('request_key', $validated['request_key'])->first();
            if ($existing) {
                return $existing;
            }

            $wallet = MakeMeWallet::query()->where('participant_id', $participant->getKey())
                ->lockForUpdate()->first();
            if (!$wallet) {
                throw ValidationException::withMessages([
                    'participant_id' => 'This participant has no funded wallet.',
                ]);
            }

            $requested = collect($validated['items'])->keyBy('product_id');
            $productIds = $requested->keys()->map(fn ($id) => (int) $id)->sort()->values();
            $products = MakeMeProduct::query()->whereIn('id', $productIds)
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $total = 0;
            $lines = [];
            foreach ($productIds as $productId) {
                $product = $products->get($productId);
                $quantity = (int) $requested->get($productId)['quantity'];
                if (!$product || !$product->is_active) {
                    throw ValidationException::withMessages(['items' => 'An item is no longer available.']);
                }
                if ((int) $product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}.",
                    ]);
                }

                if (!is_numeric($product->credit_price) ||
                    floor((float) $product->credit_price) !== (float) $product->credit_price ||
                    (float) $product->credit_price < 0) {
                    throw ValidationException::withMessages([
                        'items' => "Invalid credit price for {$product->name}.",
                    ]);
                }

                $limit = (int) ($product->max_per_participant ?? 0);
                if ($limit > 0) {
                    $previousQuantity = (int) MakeMeOrderItem::query()
                        ->join('makeme_orders', 'makeme_orders.id', '=', 'makeme_order_items.order_id')
                        ->where('makeme_orders.participant_id', $participant->getKey())
                        ->where('makeme_orders.status', '!=', 'cancelled')
                        ->where('makeme_order_items.product_id', $productId)
                        ->sum('makeme_order_items.quantity');
                    if ($previousQuantity + $quantity > $limit) {
                        throw ValidationException::withMessages([
                            'items' => "Purchase limit for {$product->name}: {$limit} per participant (already ordered {$previousQuantity}).",
                        ]);
                    }
                }

                $unitPrice = (int) $product->credit_price;
                $lineTotal = $unitPrice * $quantity;
                if ($lineTotal > self::MAX_CREDITS || $total + $lineTotal > self::MAX_CREDITS) {
                    throw ValidationException::withMessages(['items' => 'Order total exceeds permitted credits.']);
                }
                $total += $lineTotal;
                $lines[] = compact('product', 'quantity', 'unitPrice', 'lineTotal');
            }

            $before = (int) $wallet->balance;
            if ($before < $total) {
                throw ValidationException::withMessages([
                    'participant_id' => "Insufficient credits. Available: {$before}; required: {$total}.",
                ]);
            }

            $order = MakeMeOrder::create([
                'request_key' => $validated['request_key'],
                'reference' => 'MM-' . strtoupper(bin2hex(random_bytes(6))),
                'participant_id' => $participant->getKey(),
                'created_by' => auth('web')->id(),
                'status' => 'placed',
                'total_credits' => $total,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];
                $quantity = $line['quantity'];
                $stockBefore = (int) $product->stock_quantity;
                $stockAfter = $stockBefore - $quantity;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $line['unitPrice'],
                    'line_total' => $line['lineTotal'],
                ]);
                $product->stock_quantity = $stockAfter;
                $product->save();
                $product->stockMovements()->create([
                    'performed_by' => auth('web')->id(),
                    'type' => 'remove',
                    'quantity_before' => $stockBefore,
                    'quantity_change' => $quantity,
                    'quantity_after' => $stockAfter,
                    'reason' => "Order {$order->reference}",
                ]);
            }

            $wallet->balance = $before - $total;
            $wallet->save();
            $wallet->transactions()->create([
                'performed_by' => auth('web')->id(),
                'type' => 'purchase',
                'amount' => $total,
                'balance_before' => $before,
                'balance_after' => $wallet->balance,
                'reason' => "Purchase {$order->reference}",
                'reference' => "order:{$order->id}:purchase",
            ]);

            MakeMeActivityLogger::record('orders', 'created', 'Order placed', $order, [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'participant_id' => $participant->getKey(),
                'total_credits' => $total,
                'wallet_before' => $before,
                'wallet_after' => $wallet->balance,
                'items' => array_map(fn ($line) => [
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unitPrice'],
                ], $lines),
            ]);

            return $order;
        }, 3);

        return redirect()->route('admin.makeme.orders.index')
            ->with('success', "Order {$order->reference} placed successfully.");
    }

    public function fulfill(MakeMeOrder $order)
    {
        $this->authorizeAction('makeme.orders.update-status');

        DB::transaction(function () use ($order) {
            $locked = MakeMeOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== 'placed') {
                throw ValidationException::withMessages([
                    'status' => 'Only placed orders can be fulfilled.',
                ]);
            }
            $locked->status = 'fulfilled';
            $locked->fulfilled_at = now();
            $locked->fulfilled_by = auth('web')->id();
            $locked->save();

            MakeMeActivityLogger::record('orders', 'fulfilled', 'Order fulfilled', $locked, [
                'order_id' => $locked->id,
                'reference' => $locked->reference,
                'old_status' => 'placed',
                'new_status' => 'fulfilled',
            ]);
        }, 3);

        return redirect()->route('admin.makeme.orders.index')
            ->with('success', 'Order marked as fulfilled.');
    }

    public function cancel(Request $request, MakeMeOrder $order)
    {
        $this->authorizeAction('makeme.orders.update-status');
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        DB::transaction(function () use ($order, $data) {
            $locked = MakeMeOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== 'placed') {
                throw ValidationException::withMessages([
                    'status' => 'Only placed orders can be cancelled. Fulfilled orders require a separate return process.',
                ]);
            }

            $participant = MakeMeParticipant::query()->lockForUpdate()
                ->findOrFail($locked->participant_id);
            $wallet = MakeMeWallet::query()->where('participant_id', $participant->getKey())
                ->lockForUpdate()->firstOrFail();

            $before = (int) $wallet->balance;
            $total = (int) $locked->total_credits;
            if ($before + $total > self::MAX_CREDITS) {
                throw ValidationException::withMessages([
                    'status' => 'Refund exceeds maximum wallet balance. Review the wallet before cancellation.',
                ]);
            }

            $items = $locked->items()->orderBy('product_id')->get();
            $productIds = $items->pluck('product_id');
            $products = MakeMeProduct::whereIn('id', $productIds)
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                if (!$product) {
                    throw ValidationException::withMessages(['status' => 'A product record is missing; cancellation needs manual review.']);
                }
                $stockBefore = (int) $product->stock_quantity;
                $stockAfter = $stockBefore + (int) $item->quantity;
                if ($stockAfter > 4294967295) {
                    throw ValidationException::withMessages(['status' => 'Stock quantity limit exceeded.']);
                }
                $product->stock_quantity = $stockAfter;
                $product->save();
                $product->stockMovements()->create([
                    'performed_by' => auth('web')->id(),
                    'type' => 'add',
                    'quantity_before' => $stockBefore,
                    'quantity_change' => (int) $item->quantity,
                    'quantity_after' => $stockAfter,
                    'reason' => "Cancelled order {$locked->reference}: " . $data['reason'],
                ]);
            }

            $wallet->balance = $before + $total;
            $wallet->save();
            $wallet->transactions()->create([
                'performed_by' => auth('web')->id(),
                'type' => 'refund',
                'amount' => $total,
                'balance_before' => $before,
                'balance_after' => $wallet->balance,
                'reason' => "Refund cancelled order {$locked->reference}",
                'reference' => "order:{$locked->id}:refund",
            ]);

            $locked->status = 'cancelled';
            $locked->cancelled_by = auth('web')->id();
            $locked->cancelled_at = now();
            $locked->cancellation_reason = trim($data['reason']);
            $locked->save();

            MakeMeActivityLogger::record('orders', 'cancelled', 'Order cancelled and refunded', $locked, [
                'order_id' => $locked->id,
                'reference' => $locked->reference,
                'participant_id' => $locked->participant_id,
                'refund_credits' => $total,
                'wallet_before' => $before,
                'wallet_after' => $wallet->balance,
                'reason' => trim($data['reason']),
                'items_restored' => $items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ])->all(),
            ]);
        }, 3);

        return redirect()->route('admin.makeme.orders.index')
            ->with('success', 'Order cancelled. Credits refunded and stock restored.');
    }
}
