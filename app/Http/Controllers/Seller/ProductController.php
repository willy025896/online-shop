<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        $products = auth()->user()->shop->products()
            ->with('primaryImage', 'category')
            ->latest()
            ->paginate(10);

        return Inertia::render('Seller/Products/Index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        return Inertia::render('Seller/Products/Create', [
            'categories' => Category::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0|max:9999999.99',
            'compare_price' => 'nullable|numeric|min:0|max:9999999.99',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:draft,active,inactive',
            'is_featured' => 'boolean',
        ]);

        $validated['shop_id'] = auth()->user()->shop->id;
        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(6);

        $product = Product::create($validated);

        return redirect()->route('seller.products.edit', $product)
            ->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $product->load('images');

        return Inertia::render('Seller/Products/Edit', [
            'product' => $product,
            'categories' => Category::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0|max:9999999.99',
            'compare_price' => 'nullable|numeric|min:0|max:9999999.99',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:draft,active,inactive',
            'is_featured' => 'boolean',
        ]);

        $product->update($validated);

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('seller.products.index')
            ->with('success', 'Product deleted.');
    }
}
