<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsCanonicalListingUrl;
use App\Http\Controllers\Concerns\FiltersProductListings;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\RecommendationService;
use App\Support\JsonLd;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    use BuildsCanonicalListingUrl, FiltersProductListings;

    public function index(Request $request)
    {
        $query = Product::active()
            ->with(['shop', 'primaryImage', 'category']);

        if ($search = $request->input('search')) {
            SearchQuery::record($search);
            $query->whereFullText(['name', 'description'], $search);
        }

        if ($categoryId = $request->input('category')) {
            $query->where('category_id', $categoryId);
        }

        $this->applyProductSortAndFilters($query, $request);

        return Inertia::render('Products/Index', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => fn () => Category::active()->root()->with('children')->orderBy('sort_order')->get(),
            'filters' => $request->only(['search', 'category', 'sort', 'min_rating', 'min_price', 'max_price']),
            // Lazy: sort/filter clicks partial-reload with only:['products','filters']
            // (useListingFilters.js) and never touch seo, so skip the route() work
            // below unless this is a full page load.
            'seo' => fn () => [
                'title' => __('navigation.products'),
                'description' => '瀏覽平台上所有商家上架的商品。',
                'url' => $this->canonicalListingUrl('products.index', [], $request),
            ],
        ]);
    }

    public function show(Product $product, RecommendationService $recommendations)
    {
        // variants.optionValues.option is loaded unconditionally (cheap — Eloquent
        // skips the nested queries entirely when a product has no variant rows);
        // options.values is only needed once we already know variants exist, so
        // that check is done in-memory instead of an extra hasVariants() query.
        $product->load(['shop', 'images', 'category', 'variants.optionValues.option']);

        if ($product->variants->isNotEmpty()) {
            $product->load('options.values');
        }

        $isAvailable = $product->status === Product::STATUS_ACTIVE;

        if ($isAvailable) {
            $relatedProducts = $recommendations->relatedTo($product, 4);

            $reviews = $product->reviews()
                ->with(['user:id,name,profile_photo_path'])
                ->published()
                ->latest()
                ->paginate(10);

            $ratingDistribution = $product->reviews()
                ->published()
                ->selectRaw('rating, count(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->toArray();

            $description = Str::limit(strip_tags($product->description ?? ''), 155);

            $seo = [
                'title' => $product->name,
                'description' => $description,
                'image' => $product->images->first() ? asset('storage/'.$product->images->first()->path) : null,
                'url' => route('products.show', $product->slug),
                'jsonLd' => [
                    JsonLd::product($product, $description),
                    JsonLd::breadcrumbList($this->buildProductBreadcrumbItems($product)),
                ],
            ];
        } else {
            $relatedProducts = [];
            $reviews = new LengthAwarePaginator([], 0, 10);
            $ratingDistribution = [];
            $seo = null;
        }

        return Inertia::render('Products/Show', [
            'product' => $product,
            'isAvailable' => $isAvailable,
            'relatedProducts' => $relatedProducts,
            'reviews' => $reviews,
            'ratingDistribution' => $ratingDistribution,
            'seo' => $seo,
        ]);
    }

    private function buildProductBreadcrumbItems(Product $product): array
    {
        $items = [['name' => __('navigation.home'), 'url' => url('/')]];

        if ($product->category) {
            $items = array_merge($items, $product->category->breadcrumbTrail());
        }

        $items[] = ['name' => $product->name, 'url' => route('products.show', $product->slug)];

        return $items;
    }
}
