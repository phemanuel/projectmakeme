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
use Carbon\Carbon;

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
    
        /*
        |--------------------------------------------------------------------------
        | Validate Filters
        |--------------------------------------------------------------------------
        */
    
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
    
            'status' => [
                'nullable',
                Rule::in(['all', 'placed', 'fulfilled', 'cancelled']),
            ],
    
            'received_from' => [
                'nullable',
                'date_format:Y-m-d\TH:i',
            ],
    
            'received_to' => [
                'nullable',
                'date_format:Y-m-d\TH:i',
            ],
    
            'delivery_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            
            'output' => [
                'nullable',
                Rule::in(['csv', 'print']),
            ],
        ]);
    
        /*
        |--------------------------------------------------------------------------
        | Timezone Configuration
        |--------------------------------------------------------------------------
        |
        | Admins enter dates and times in Nigerian time.
        | Convert the selected times to the timezone used
        | to store order created_at values.
        |
        | This assumes timestamps are stored in the Laravel
        | application timezone (commonly UTC).
        |
        */
    
        $displayTimezone = 'Africa/Lagos';
        $storageTimezone = config('app.timezone', 'UTC');
    
        /*
        |--------------------------------------------------------------------------
        | Retrieve Orders
        |--------------------------------------------------------------------------
        */
    
        $ordersQuery = MakeMeOrder::query()
            ->with([
                'participant',
                'items',
                'creator',
            ]);
    
        /*
        |--------------------------------------------------------------------------
        | Search Orders
        |--------------------------------------------------------------------------
        |
        | Supports:
        | - Order reference
        | - Kreativ ID
        | - First name
        | - Other name
        | - Surname
        | - Full name
        | - Email address
        |
        */
    
        if (!empty($filters['q'])) {
    
            $term = trim($filters['q']);
    
            $nameParts = preg_split(
                '/\s+/',
                $term,
                -1,
                PREG_SPLIT_NO_EMPTY
            );
    
            $ordersQuery->where(function ($query) use ($term, $nameParts) {
    
                $query->where(
                    'reference',
                    'like',
                    "%{$term}%"
                );
    
                if (ctype_digit($term)) {
                    $query->orWhere(
                        'participant_id',
                        (int) $term
                    );
                }
    
                $query->orWhereHas(
                    'participant',
                    function ($participantQuery) use ($term, $nameParts) {
    
                        $participantQuery->where(
                            function ($nameQuery) use ($term, $nameParts) {
    
                                $nameQuery
                                    ->where('first_name', 'like', "%{$term}%")
                                    ->orWhere('surname', 'like', "%{$term}%")
                                    ->orWhere('other_name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%");
    
                                if (count($nameParts) > 1) {
    
                                    $nameQuery->orWhere(
                                        function ($fullNameQuery) use ($nameParts) {
    
                                            foreach ($nameParts as $part) {
    
                                                $fullNameQuery->where(
                                                    function ($partQuery) use ($part) {
    
                                                        $partQuery
                                                            ->where('first_name', 'like', "%{$part}%")
                                                            ->orWhere('surname', 'like', "%{$part}%")
                                                            ->orWhere('other_name', 'like', "%{$part}%");
    
                                                    }
                                                );
                                            }
                                        }
                                    );
                                }
                            }
                        );
                    }
                );
            });
        }
    
        /*
        |--------------------------------------------------------------------------
        | Filter by Order Status
        |--------------------------------------------------------------------------
        */
    
        if (
            !empty($filters['status']) &&
            $filters['status'] !== 'all'
        ) {
            $ordersQuery->where(
                'status',
                $filters['status']
            );
        }
    
        /*
        |--------------------------------------------------------------------------
        | Filter by Received Date and Time
        |--------------------------------------------------------------------------
        */
    
        $receivedFrom = null;
        $receivedTo = null;
    
        if (!empty($filters['received_from'])) {
    
            $receivedFrom = Carbon::createFromFormat(
                '!Y-m-d\TH:i',
                $filters['received_from'],
                $displayTimezone
            )->startOfMinute();
    
            $ordersQuery->where(
                'created_at',
                '>=',
                $receivedFrom
                    ->copy()
                    ->timezone($storageTimezone)
                    ->format('Y-m-d H:i:s')
            );
        }
    
        if (!empty($filters['received_to'])) {
    
            $receivedTo = Carbon::createFromFormat(
                '!Y-m-d\TH:i',
                $filters['received_to'],
                $displayTimezone
            )->endOfMinute();
    
            $ordersQuery->where(
                'created_at',
                '<=',
                $receivedTo
                    ->copy()
                    ->timezone($storageTimezone)
                    ->format('Y-m-d H:i:s')
            );
        }
    
        if (
            $receivedFrom &&
            $receivedTo &&
            $receivedFrom->greaterThan($receivedTo)
        ) {
            throw ValidationException::withMessages([
                'received_to' =>
                    'The end date and time must be after the start date and time.',
            ]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | Filter by Scheduled Delivery Date
        |--------------------------------------------------------------------------
        |
        | Delivery rule:
        |
        | Orders received on or before 10:00 AM:
        | Delivered that evening.
        |
        | Orders received after 10:00 AM:
        | Delivered the following evening.
        |
        | Example:
        |
        | Delivery Date: November 18
        |
        | Includes orders received:
        | After November 17, 10:00:00 AM
        | Through November 18, 10:00:00 AM
        |
        */
    
        if (!empty($filters['delivery_date'])) {
    
            $deliveryDate = Carbon::createFromFormat(
                '!Y-m-d',
                $filters['delivery_date'],
                $displayTimezone
            );
    
            $deliveryEnd = $deliveryDate
                ->copy()
                ->setTime(10, 0, 0);
    
            $deliveryStart = $deliveryEnd
                ->copy()
                ->subDay();
    
            $ordersQuery
                ->where(
                    'created_at',
                    '>',
                    $deliveryStart
                        ->timezone($storageTimezone)
                        ->format('Y-m-d H:i:s')
                )
                ->where(
                    'created_at',
                    '<=',
                    $deliveryEnd
                        ->timezone($storageTimezone)
                        ->format('Y-m-d H:i:s')
                );
        }
    
        /*
        |--------------------------------------------------------------------------
        | Sort Orders by Priority
        |--------------------------------------------------------------------------
        |
        | Placed first, followed by Fulfilled and Cancelled.
        | Newest first within each status.
        |
        */
    
       
        /*
        |--------------------------------------------------------------------------
        | Sort Orders by Priority
        |--------------------------------------------------------------------------
        */
        
        $ordersQuery
            ->orderByRaw("
                CASE
                    WHEN status = 'placed' THEN 0
                    WHEN status = 'fulfilled' THEN 1
                    WHEN status = 'cancelled' THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('created_at')
            ->orderByDesc('id');
        
        /*
        |--------------------------------------------------------------------------
        | Export and Print Filtered Orders
        |--------------------------------------------------------------------------
        |
        | Use the same filtered query as the normal Orders page.
        | This happens BEFORE pagination.
        |
        */
        
        if (($filters['output'] ?? null) === 'csv') {
        
            $filename = 'ProjectMakeMe-Orders-'
                . now('Africa/Lagos')->format('Y-m-d-His')
                . '.csv';
        
            return response()->streamDownload(
                function () use ($ordersQuery) {
        
                    $handle = fopen('php://output', 'w');
        
                    // UTF-8 BOM for Excel compatibility.
                    fwrite($handle, "\xEF\xBB\xBF");
        
                    fputcsv($handle, [
                        'Order Reference',
                        'Kreativ ID',
                        'Kreativ Name',
                        'Email',
                        'Items',
                        'Total Units',
                        'Total Credits',
                        'Status',
                        'Received At (WAT)',
                        'Scheduled Delivery Date',
                    ]);
        
                    // Prevent spreadsheet formula injection.
                    $safeCell = static function ($value): string {
                        $text = (string) ($value ?? '');
        
                        if (preg_match('/^[\s]*[=+\-@]/u', $text)) {
                            return "'" . $text;
                        }
        
                        return $text;
                    };
        
                    foreach ($ordersQuery->lazy(200) as $order) {
        
                        $participant = $order->participant;
        
                        $name = $participant
                            ? trim(implode(' ', array_filter([
                                $participant->first_name,
                                $participant->other_name,
                                $participant->surname,
                            ])))
                            : 'Unavailable Kreativ';
        
                        $itemSummary = $order->items
                            ->map(fn ($item) =>
                                $item->product_name
                                . ' x ' . (int) $item->quantity
                            )
                            ->implode('; ');
        
                        $receivedAt = $order->created_at
                            ? $order->created_at
                                ->copy()
                                ->timezone('Africa/Lagos')
                            : null;
        
                        $deliveryDate = $order->created_at
                            ? \App\Support\MakeMeDeliverySchedule::deliveryDate(
                                $order->created_at
                            )
                            : '';
        
                        fputcsv($handle, [
                            $safeCell($order->reference),
                            $order->participant_id,
                            $safeCell($name),
                            $safeCell($participant?->email),
                            $safeCell($itemSummary),
                            (int) $order->items->sum('quantity'),
                            (int) $order->total_credits,
                            ucfirst($order->status),
                            $receivedAt?->format('Y-m-d h:i A'),
                            $deliveryDate,
                        ]);
                    }
        
                    fclose($handle);
                },
                $filename,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        }
        
        /*
        |--------------------------------------------------------------------------
        | Print Filtered Orders
        |--------------------------------------------------------------------------
        */
        
        if (($filters['output'] ?? null) === 'print') {
        
            return view('admin.makeme.orders.print', [
                'orders' => (clone $ordersQuery)->get(),
                'filters' => $filters,
            ]);
        }
        
        /*
        |--------------------------------------------------------------------------
        | Normal Paginated Orders
        |--------------------------------------------------------------------------
        */
        
        $orders = $ordersQuery
            ->paginate(15)
            ->withQueryString();

    
        /*
        |--------------------------------------------------------------------------
        | Active Participants
        |--------------------------------------------------------------------------
        */
    
        $participants = MakeMeParticipant::query()
            ->where('status', 'active')
            ->with('wallet')
            ->orderBy('id')
            ->get();
    
        /*
        |--------------------------------------------------------------------------
        | Available Products
        |--------------------------------------------------------------------------
        */
    
        $products = MakeMeProduct::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'sku',
                'credit_price',
                'stock_quantity',
                'max_per_participant',
            ]);
    
        /*
        |--------------------------------------------------------------------------
        | Order Statistics
        |--------------------------------------------------------------------------
        */
    
        $stats = [
            'all' => MakeMeOrder::count(),
    
            'placed' => MakeMeOrder::where(
                'status',
                'placed'
            )->count(),
    
            'fulfilled' => MakeMeOrder::where(
                'status',
                'fulfilled'
            )->count(),
    
            'cancelled' => MakeMeOrder::where(
                'status',
                'cancelled'
            )->count(),
        ];
    
        return view(
            'admin.makeme.orders.index',
            compact(
                'orders',
                'participants',
                'products',
                'stats'
            )
        );
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
