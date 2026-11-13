<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MakeMeProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MakeMeProductController extends Controller
{
    private const CATEGORIES = [
        'general', 'fashion_designing', 'shoe_making', 'hair_styling',
        'photography', 'makeup_artistry',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::in(self::CATEGORIES)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $products = MakeMeProduct::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = $request->input('q');
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%"));
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'total' => MakeMeProduct::count(),
            'active' => MakeMeProduct::where('is_active', true)->count(),
            'low_stock' => MakeMeProduct::where('stock_quantity', '<=', 5)->count(),
        ];

        return view('admin.makeme.products.index', compact('products', 'stats'));
    }

    public function create()
    {
        $product = new MakeMeProduct();
        return view('admin.makeme.products.form', compact('product'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('makeme/products', 'public');
        }
        unset($data['image']);
        MakeMeProduct::create($data);
        return redirect()->route('admin.makeme.products.index')->with('success', 'Product created.');
    }

    public function edit(MakeMeProduct $product)
    {
        return view('admin.makeme.products.form', compact('product'));
    }

    public function update(Request $request, MakeMeProduct $product)
    {
        $data = $this->validated($request, $product);
        $data['is_active'] = $request->boolean('is_active');
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('makeme/products', 'public');
            $data['image_path'] = $path;
            if ($product->image_path) Storage::disk('public')->delete($product->image_path);
        }
        unset($data['image']);
        $product->update($data);
        return redirect()->route('admin.makeme.products.index')->with('success', 'Product updated.');
    }

    private function validated(Request $request, ?MakeMeProduct $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('makeme_products', 'sku')->ignore($product?->id)],
            'description' => ['nullable', 'string', 'max:4000'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'credit_price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:999999999'],
            'max_per_participant' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:3072'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
