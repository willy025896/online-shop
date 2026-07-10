<?php

namespace App\Http\Controllers;

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
    use FiltersProductListings;

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
        ]);
    }

    public function show(Product $product, RecommendationService $recommendations)
    {
        // variants.optionValues.option is loaded unconditionally (cheap — Eloquent
        // skips the nested queries entirely when a product has no variant rows);
        // options.values is only needed once we already know variants exist, so
        // that check is done in-memory instead of an extra hasVariants() query.
        $product->load(['shop', 'images', 'category.parent', 'variants.optionValues.option']);

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
                    $this->buildProductJsonLd($product, $description),
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

    private function buildProductJsonLd(Product $product, string $description): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $description,
            'url' => route('products.show', $product->slug),
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->shop->name,
            ],
        ];

        if ($product->category) {
            $node['category'] = $product->category->name;
        }

        if ($product->images->isNotEmpty()) {
            $node['image'] = $product->images->map(fn ($image) => asset('storage/'.$image->path))->all();
        }

        if ($product->variants->isNotEmpty()) {
            $node['offers'] = [
                '@type' => 'AggregateOffer',
                'priceCurrency' => Product::CURRENCY,
                'lowPrice' => (float) $product->variants->min('price'),
                'highPrice' => (float) $product->variants->max('price'),
                'offerCount' => $product->variants->count(),
                'availability' => $product->variants->contains(fn ($variant) => $variant->inStock())
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ];
        } else {
            $node['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => Product::CURRENCY,
                'price' => (float) $product->price,
                'availability' => $product->inStock()
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ];
        }

        if ($product->reviews_count > 0) {
            $node['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $product->averageRating(),
                'reviewCount' => $product->reviews_count,
            ];
        }

        return $node;
    }

    private function buildProductBreadcrumbItems(Product $product): array
    {
        $items = [['name' => __('navigation.home'), 'url' => url('/')]];

        if ($product->category && $product->category->is_active) {
            foreach ($product->category->activeAncestors() as $ancestor) {
                $items[] = ['name' => $ancestor->name, 'url' => route('categories.show', $ancestor->slug)];
            }

            $items[] = ['name' => $product->category->name, 'url' => route('categories.show', $product->category->slug)];
        }

        $items[] = ['name' => $product->name, 'url' => route('products.show', $product->slug)];

        return $items;
    }
}
