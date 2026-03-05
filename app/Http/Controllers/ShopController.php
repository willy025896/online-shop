<?php

namespace App\Http\Controllers;

use App\Models\Shop;
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

    public function show(Shop $shop)
    {
        abort_unless($shop->isApproved(), 404);

        $products = $shop->products()
            ->active()
            ->with('primaryImage')
            ->latest()
            ->paginate(12);

        return Inertia::render('Shop/Show', [
            'shop' => $shop,
            'products' => $products,
        ]);
    }
}
