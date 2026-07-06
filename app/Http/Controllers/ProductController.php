<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
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
        abort_unless($product->status === Product::STATUS_ACTIVE, 404);

        $product->load(['shop', 'images', 'category']);

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

        return Inertia::render('Products/Show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'reviews' => $reviews,
            'ratingDistribution' => $ratingDistribution,
        ]);
    }
}
