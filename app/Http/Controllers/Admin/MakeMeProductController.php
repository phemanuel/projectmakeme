<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MakeMeProduct;
use App\Models\MakeMeStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\MakeMeActivityLogger;

class MakeMeProductController extends Controller
{
    private const CATEGORIES = [
        'general',
        'fashion_designing',
        'shoe_making',
        'hair_styling',
        'photography',
        'makeup_artistry',
        'modelling',
    ];

    private function authorizeAction(string $permission): void
    {
        $user = auth('web')->user();

        abort_unless(
            $user &&
            (int) $user->user_type === 1 &&
            (int) $user->user_status === 1 &&
            $user->applicant_type !== 'WAITLIST' &&
            (
                $user->hasRole('Super Admin', 'web') ||
                $user->can($permission)
            ),
            403
        );
    }

    public function index(Request $request)
    {
        $this->authorizeAction('makeme.products.view');

        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => [
                'nullable',
                Rule::in(self::CATEGORIES),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                    'low_stock',
                    'out_of_stock',
                ]),
            ],
        ]);

        $products = MakeMeProduct::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = $request->input('q');

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category', $request->category);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                match ($request->status) {
                    'active' => $query->where('is_active', true),
                    'inactive' => $query->where('is_active', false),
                    'low_stock' => $query
                        ->where('stock_quantity', '>', 0)
                        ->where('stock_quantity', '<=', 5),
                    'out_of_stock' => $query
                        ->where('stock_quantity', 0),
                    default => null,
                };
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => MakeMeProduct::count(),
            'active' => MakeMeProduct::where('is_active', true)->count(),
            'available_units' => MakeMeProduct::sum('stock_quantity'),
            'low_stock' => MakeMeProduct::whereBetween(
                'stock_quantity',
                [1, 5]
            )->count(),
            'out_of_stock' => MakeMeProduct::where(
                'stock_quantity',
                0
            )->count(),
        ];

        $categories = self::CATEGORIES;

        $movements = MakeMeStockMovement::query()
            ->with(['product', 'administrator'])
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.makeme.products.index', compact(
            'products',
            'stats',
            'categories',
            'movements'
        ));
    }

    // Preserve old resource URLs; all forms are now modals.
    public function create()
    {
        $this->authorizeAction('makeme.products.create');

        return redirect()->route('admin.makeme.products.index');
    }

    public function edit(MakeMeProduct $product)
    {
        $this->authorizeAction('makeme.products.edit');

        return redirect()->route('admin.makeme.products.index', [
            'q' => $product->sku,
        ]);
    }

    public function show(MakeMeProduct $product)
    {
        $this->authorizeAction('makeme.products.view');

        return redirect()->route('admin.makeme.products.index', [
            'q' => $product->sku,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAction('makeme.products.create');

        $data = $this->validatedProduct($request, true);

        $initialStock = (int) $data['stock_quantity'];

        $data['is_active'] = $request->boolean('is_active');

        unset($data['image']);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store('makeme/products', 'public');

            $data['image_path'] = $imagePath;
        }

        try {
            DB::transaction(function () use ($data, $initialStock) {
                $product = MakeMeProduct::create($data);

                if ($initialStock > 0) {
                    $product->stockMovements()->create([
                        'performed_by' => auth('web')->id(),
                        'type' => 'initial',
                        'quantity_before' => 0,
                        'quantity_change' => $initialStock,
                        'quantity_after' => $initialStock,
                        'reason' => 'Initial product stock',
                    ]);
                }

                MakeMeActivityLogger::record(
                    'products',
                    'created',
                    'Product created',
                    $product,
                    [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'category' => $product->category,
                        'credit_price' => $product->credit_price,
                        'initial_stock' => $product->stock_quantity,
                    ]
                );
            });
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return redirect()
            ->route('admin.makeme.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function update(Request $request, MakeMeProduct $product)
    {
        $this->authorizeAction('makeme.products.edit');

        $data = $this->validatedProduct($request, false);

        $data['is_active'] = $request->boolean('is_active');

        unset($data['image']);

        $oldImage = $product->image_path;
        $newImage = null;

        $before = $product->only([
            'name',
            'sku',
            'description',
            'category',
            'credit_price',
            'max_per_participant',
            'is_active',
            'image_path',
        ]);

        if ($request->hasFile('image')) {
            $newImage = $request->file('image')
                ->store('makeme/products', 'public');

            $data['image_path'] = $newImage;
        }

        try {
            $product->update($data);

            $after = $product->fresh()->only(array_keys($before));

            $changes = [];

            foreach ($before as $field => $oldValue) {
                $newValue = $after[$field] ?? null;

                if ($oldValue != $newValue) {
                    $changes[$field] = [
                        'before' => $oldValue,
                        'after' => $newValue,
                    ];
                }
            }

            if (!empty($changes)) {
                MakeMeActivityLogger::record(
                    'products',
                    'updated',
                    'Product updated',
                    $product,
                    [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'changes' => $changes,
                    ]
                );
            }

        } catch (\Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $exception;
        }

        if ($newImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()
            ->route('admin.makeme.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function adjustStock(Request $request, MakeMeProduct $product)
    {
        $this->authorizeAction('makeme.products.stock.adjust');

        $validated = $request->validate([
            'type' => [
                'required',
                Rule::in(['add', 'remove', 'set']),
            ],
            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:999999999',
            ],
            'reason' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        DB::transaction(function () use ($product, $validated) {
            $locked = MakeMeProduct::query()
                ->lockForUpdate()
                ->findOrFail($product->id);

            $before = (int) $locked->stock_quantity;
            $quantity = (int) $validated['quantity'];

            $after = match ($validated['type']) {
                'add' => $before + $quantity,
                'remove' => $before - $quantity,
                'set' => $quantity,
            };

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Cannot remove more stock than is available.',
                ]);
            }

            if ($after > 999999999) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock quantity exceeds the allowed maximum.',
                ]);
            }

            if ($after === $before) {
                throw ValidationException::withMessages([
                    'quantity' => 'The stock quantity must change.',
                ]);
            }

            $locked->stock_quantity = $after;
            $locked->save();

            $movement = $locked->stockMovements()->create([
                'performed_by' => auth('web')->id(),
                'type' => $validated['type'],
                'quantity_before' => $before,
                'quantity_change' => abs($after - $before),
                'quantity_after' => $after,
                'reason' => $validated['reason'],
            ]);
            
            MakeMeActivityLogger::record(
                'stock',
                'adjusted',
                'Product stock adjusted',
                $product,
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'movement_id' => $movement->id,
                    'adjustment_type' => $movement->type,
                    'quantity_before' => $movement->quantity_before,
                    'quantity_after' => $movement->quantity_after,
                    'quantity_change' => $movement->quantity_change,
                    'reason' => $movement->reason,
                ]
            );

        });

        return redirect()
            ->route('admin.makeme.products.index')
            ->with('success', 'Stock adjusted successfully.');
    }

    public function destroy(MakeMeProduct $product)
    {
        $this->authorizeAction('makeme.products.delete');

        // Archive rather than permanently delete product/order history.
        $product->update([
            'is_active' => false,
        ]);

        MakeMeActivityLogger::record(
            'products',
            'archived',
            'Product archived',
            $product,
            [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'is_active' => false,
            ]
        );

        return redirect()
            ->route('admin.makeme.products.index')
            ->with('success', 'Product archived successfully.');
    }

    private function validatedProduct(
        Request $request,
        bool $creating
    ): array {
        $rules = [
            'name' => [
                'required', 'string', 'max:180',
            ],
            'sku' => [
                'required', 'string', 'max:80',
                Rule::unique('makeme_products', 'sku')
                    ->ignore(
                        $creating ? null : $request->route('product')?->id
                    ),
            ],
            'description' => [
                'nullable', 'string', 'max:4000',
            ],
            'category' => [
                'required',
                Rule::in(self::CATEGORIES),
            ],
            'credit_price' => [
                'required', 'integer', 'min:0', 'max:999999999',
            ],
            'max_per_participant' => [
                'nullable', 'integer', 'min:1', 'max:1000',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:3072',
            ],
            'is_active' => [
                'nullable', 'boolean',
            ],
        ];

        if ($creating) {
            $rules['stock_quantity'] = [
                'required',
                'integer',
                'min:0',
                'max:999999999',
            ];
        }

        return $request->validate($rules);
    }
}
