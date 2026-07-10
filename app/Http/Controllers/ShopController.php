<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersProductListings;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Support\JsonLd;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ShopController extends Controller
{
    use FiltersProductListings;

    public function index()
    {
        $shops = Shop::where('status', Shop::STATUS_APPROVED)
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
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $this->applyProductSortAndFilters($query, $request);

        $products = $query->paginate(12)->withQueryString();

        return Inertia::render('Shop/Show', [
            'shop' => $shop,
            'products' => $products,
            'categories' => fn () => Category::whereIn(
                'id',
                Product::where('shop_id', $shop->id)->where('status', Product::STATUS_ACTIVE)->select('category_id')
            )->active()->orderBy('sort_order')->get(['id', 'name']),
            'filters' => $request->only(['search', 'category', 'sort', 'min_rating', 'min_price', 'max_price']),
            // Lazy: sort/filter clicks partial-reload with only:['products','filters']
            // (useListingFilters.js) and never touch seo, so skip the route()/JsonLd
            // work below unless this is a full page load.
            'seo' => function () use ($shop) {
                $description = Str::limit(strip_tags($shop->description ?? ''), 155);

                return [
                    'title' => $shop->name,
                    'description' => $description,
                    'image' => $shop->logo_path ? asset('storage/'.$shop->logo_path) : null,
                    'url' => route('shops.show', $shop->slug),
                    'jsonLd' => [
                        JsonLd::organization($shop, $description),
                        JsonLd::breadcrumbList([
                            ['name' => __('navigation.home'), 'url' => url('/')],
                            ['name' => __('navigation.shops'), 'url' => route('shops.index')],
                            ['name' => $shop->name, 'url' => route('shops.show', $shop->slug)],
                        ]),
                    ],
                ];
            },
        ]);
    }
}
