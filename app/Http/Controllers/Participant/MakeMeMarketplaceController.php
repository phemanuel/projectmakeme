<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\MakeMeOrder;
use App\Models\MakeMeOrderItem;
use App\Models\MakeMeParticipant;
use App\Models\MakeMeProduct;
use App\Models\MakeMeWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MakeMeMarketplaceController extends Controller
{
    private const MAX_CREDITS = 999999999;

    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $participant = auth('makeme')->user();
        abort_unless($participant && $participant->status === 'active', 403);

        
        $products = MakeMeProduct::query()
            ->visibleToParticipant($participant)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->input('q'));

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('category', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();


        $walletBalance = (int) (MakeMeWallet::where('participant_id', $participant->getKey())->value('balance') ?? 0);
        return view('makeme.participant.marketplace.index', compact('products', 'walletBalance'));
    }

    
    public function show(MakeMeProduct $product)
    {
        $participant = auth('makeme')->user();

        abort_unless(
            $participant && $participant->status === 'active',
            403
        );

        // Ensure this participant is allowed to view the product.
        $product = MakeMeProduct::query()
            ->visibleToParticipant($participant)
            ->where('is_active', true)
            ->findOrFail($product->getKey());

        $walletBalance = (int) (
            MakeMeWallet::where(
                'participant_id',
                $participant->getKey()
            )->value('balance') ?? 0
        );

        $alreadyOrdered = (int) MakeMeOrderItem::query()
            ->join(
                'makeme_orders',
                'makeme_orders.id',
                '=',
                'makeme_order_items.order_id'
            )
            ->where(
                'makeme_orders.participant_id',
                $participant->getKey()
            )
            ->where('makeme_orders.status', '!=', 'cancelled')
            ->where(
                'makeme_order_items.product_id',
                $product->getKey()
            )
            ->sum('makeme_order_items.quantity');

        return view(
            'makeme.participant.marketplace.show',
            compact('product', 'walletBalance', 'alreadyOrdered')
        );
    }


    public function purchase(Request $request, MakeMeProduct $product)
    {
        $validated = $request->validate([
            'request_key' => ['required', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);
        $participantId = auth('makeme')->id();
        abort_unless($participantId, 403);

        // Unique request_key prevents accidental duplicate purchases after refresh or double click.
        $existing = MakeMeOrder::where('request_key', $validated['request_key'])
            ->where('participant_id', $participantId)->first();
        if ($existing) return redirect()->route('makeme.participant.orders.show', $existing);

        $order = DB::transaction(function () use ($validated, $participantId, $product) {
            // Lock participant first: serialises all purchases against participant-specific limits.
            $participant = MakeMeParticipant::query()->lockForUpdate()->findOrFail($participantId);
            if ($participant->status !== 'active') {
                throw ValidationException::withMessages(['quantity' => 'Your account is not active.']);
            }

            $existing = MakeMeOrder::where('request_key', $validated['request_key'])->first();
            if ($existing) {
                if ((int) $existing->participant_id !== (int) $participantId) abort(403);
                return $existing;
            }

            $wallet = MakeMeWallet::where('participant_id', $participantId)->lockForUpdate()->first();
            if (!$wallet) throw ValidationException::withMessages(['quantity' => 'Your Kolo Money wallet is not available.']);

            
            $lockedProduct = MakeMeProduct::query()
                ->visibleToParticipant($participant)
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail($product->getKey());

            $quantity = (int) $validated['quantity'];
            if (!$lockedProduct->is_active || (int) $lockedProduct->stock_quantity < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'This quantity is no longer available.']);
            }

            $unitPrice = $lockedProduct->credit_price;
            if (!is_numeric($unitPrice) || (float) $unitPrice < 0 || floor((float) $unitPrice) != (float) $unitPrice) {
                throw ValidationException::withMessages(['quantity' => 'This item has an invalid credit price.']);
            }
            $unitPrice = (int) $unitPrice;
            if ($unitPrice > self::MAX_CREDITS || ($unitPrice > 0 && $quantity > intdiv(self::MAX_CREDITS, $unitPrice))) {
                throw ValidationException::withMessages(['quantity' => 'Order total exceeds the permitted limit.']);
            }
            $total = $unitPrice * $quantity;

            $limit = (int) ($lockedProduct->max_per_participant ?? 0);
            if ($limit > 0) {
                $purchased = (int) MakeMeOrderItem::query()
                    ->join('makeme_orders', 'makeme_orders.id', '=', 'makeme_order_items.order_id')
                    ->where('makeme_orders.participant_id', $participantId)
                    ->where('makeme_orders.status', '!=', 'cancelled')
                    ->where('makeme_order_items.product_id', $lockedProduct->getKey())
                    ->sum('makeme_order_items.quantity');
                if ($purchased + $quantity > $limit) {
                    throw ValidationException::withMessages(['quantity' => "Limit is {$limit} total per participant. Already ordered: {$purchased}."]);
                }
            }

            $before = (int) $wallet->balance;
            if ($before < $total) throw ValidationException::withMessages(['quantity' => "Insufficient credits. You have {$before}; you need {$total}."]);

            $order = MakeMeOrder::create([
                'request_key' => $validated['request_key'],
                'reference' => 'MM-' . strtoupper(bin2hex(random_bytes(6))),
                'participant_id' => $participantId,
                'created_by' => null, // Participant is not an admin user.
                'status' => 'placed',
                'total_credits' => $total,
            ]);
            $order->items()->create([
                'product_id' => $lockedProduct->getKey(), 'product_name' => $lockedProduct->name,
                'sku' => $lockedProduct->sku, 'quantity' => $quantity,
                'unit_price' => $unitPrice, 'line_total' => $total,
            ]);

            $stockBefore = (int) $lockedProduct->stock_quantity;
            $lockedProduct->stock_quantity = $stockBefore - $quantity;
            $lockedProduct->save();
            $lockedProduct->stockMovements()->create([
                'performed_by' => null, // Must be nullable: participant has no users.id.
                'type' => 'remove', 'quantity_before' => $stockBefore,
                'quantity_change' => $quantity, 'quantity_after' => $stockBefore - $quantity,
                'reason' => "Participant order {$order->reference}",
            ]);

            $wallet->balance = $before - $total;
            $wallet->save();
            $wallet->transactions()->create([
                'performed_by' => null,
                'type' => 'purchase', 'amount' => $total,
                'balance_before' => $before, 'balance_after' => $wallet->balance,
                'reason' => "Participant purchase {$order->reference}",
                'reference' => "order:{$order->id}:purchase",
            ]);

            // Attribute to the logged-in contestant, not the admin web guard.
            activity('makeme-orders')->event('participant_purchase')
                ->causedBy($participant)->performedOn($order)
                ->withProperties([
                    'module' => 'orders', 'action' => 'participant_purchase',
                    'order_id' => $order->id, 'reference' => $order->reference,
                    'participant_id' => $participantId, 'product_id' => $lockedProduct->getKey(),
                    'quantity' => $quantity, 'total_credits' => $total,
                    'wallet_before' => $before, 'wallet_after' => $wallet->balance,
                    'ip_address' => request()->ip(),
                ])->log('Participant placed marketplace order');

            return $order;
        }, 3);

        return redirect()->route('makeme.participant.orders.show', $order)
            ->with('success', 'Purchase successful. Your order has been placed.');
    }
}
