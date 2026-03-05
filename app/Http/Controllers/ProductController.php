<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::active()
            ->with(['shop', 'primaryImage', 'category']);

        if ($search = $request->input('search')) {
            $query->whereFullText(['name', 'description'], $search);
        }

        if ($categoryId = $request->input('category')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->input('sort') === 'price_asc') {
            $query->orderBy('price');
        } elseif ($request->input('sort') === 'price_desc') {
            $query->orderByDesc('price');
        } else {
            $query->latest();
        }

        return Inertia::render('Products/Index', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => Category::active()->root()->with('children')->orderBy('sort_order')->get(),
            'filters' => $request->only(['search', 'category', 'sort']),
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'active', 404);

        $product->load(['shop', 'images', 'category']);

        $relatedProducts = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('primaryImage')
            ->limit(4)
            ->get();

        return Inertia::render('Products/Show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
