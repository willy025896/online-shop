<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
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

        // Rating filter: only show products with enough reviews and avg >= min_rating
        if ($minRating = $request->integer('min_rating')) {
            $query->where('reviews_count', '>', 0)
                ->whereRaw('(rating_sum / reviews_count) >= ?', [$minRating]);
        }

        $query->priceRange($request->input('min_price'), $request->input('max_price'));

        $sort = $request->input('sort', 'latest');
        if ($sort === 'price_asc') {
            $query->orderBy('price');
        } elseif ($sort === 'price_desc') {
            $query->orderByDesc('price');
        } elseif ($sort === 'rating_desc') {
            $query->orderByRating();
        } else {
            $query->latest();
        }

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
        } else {
            $relatedProducts = [];
            $reviews = new LengthAwarePaginator([], 0, 10);
            $ratingDistribution = [];
        }

        return Inertia::render('Products/Show', [
            'product' => $product,
            'isAvailable' => $isAvailable,
            'relatedProducts' => $relatedProducts,
            'reviews' => $reviews,
            'ratingDistribution' => $ratingDistribution,
            'seo' => $isAvailable ? [
                'title' => $product->name,
                'description' => Str::limit(strip_tags($product->description ?? ''), 155),
                'image' => $product->images->first() ? asset('storage/'.$product->images->first()->path) : null,
                'url' => route('products.show', $product->slug),
            ] : null,
        ]);
    }
}
