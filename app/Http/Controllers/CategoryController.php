<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersProductListings;
use App\Models\Category;
use App\Models\Product;
use App\Support\JsonLd;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    use FiltersProductListings;

    public function show(Category $category, Request $request)
    {
        abort_unless($category->is_active, 404);

        $category->load('children', 'parent');

        $categoryIds = collect([$category->id])
            ->merge($category->children->pluck('id'));

        $query = Product::active()
            ->whereIn('category_id', $categoryIds)
            ->with(['shop', 'primaryImage']);

        $this->applyProductSortAndFilters($query, $request);

        $products = $query->paginate(12)->withQueryString();

        return Inertia::render('Categories/Show', [
            'category' => $category,
            'products' => $products,
            'filters' => $request->only(['sort', 'min_rating', 'min_price', 'max_price']),
            // Lazy: sort/filter clicks partial-reload with only:['products','filters']
            // (useListingFilters.js) and never touch seo, so skip the route()/JsonLd
            // work below unless this is a full page load.
            'seo' => fn () => [
                'title' => $category->name,
                'description' => "探索「{$category->name}」分類下的所有商品。",
                'url' => route('categories.show', $category->slug),
                'jsonLd' => [JsonLd::breadcrumbList(array_merge(
                    [['name' => __('navigation.home'), 'url' => url('/')]],
                    $category->breadcrumbTrail()
                ))],
            ],
        ]);
    }
}
