<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index()
    {
        $shops = Shop::where('status', 'approved')
            ->withCount('products')
            ->latest()
            ->paginate(12);

        return Inertia::render('Shop/Index', [
            'shops' => $shops,
        ]);
    }

    public function show(Shop $shop, Request $request)
    {
        abort_unless($shop->isApproved(), 404);

        $query = $shop->products()
            ->active()
            ->with('primaryImage');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        match ($request->get('sort', 'latest')) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name'       => $query->orderBy('name', 'asc'),
            default      => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::whereHas('products', fn ($q) =>
            $q->where('shop_id', $shop->id)->where('status', 'active')
        )->active()->orderBy('sort_order')->get(['id', 'name']);

        return Inertia::render('Shop/Show', [
            'shop'       => $shop,
            'products'   => $products,
            'categories' => $categories,
            'filters'    => [
                'search'   => $request->get('search', ''),
                'category' => $request->get('category', ''),
                'sort'     => $request->get('sort', 'latest'),
            ],
        ]);
    }
}
