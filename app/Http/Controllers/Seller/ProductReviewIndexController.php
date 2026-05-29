<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductReviewIndexController extends Controller
{
    public function index(Request $request)
    {
        $shop = auth()->user()->shop;

        $query = ProductReview::with('user', 'product', 'orderItem.order')
            ->where('shop_id', $shop->id)
            ->released();

        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        if ($request->input('replied') === 'no') {
            $query->whereNull('seller_replied_at');
        } elseif ($request->input('replied') === 'yes') {
            $query->whereNotNull('seller_replied_at');
        }

        $reviews = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Seller/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only(['rating', 'replied']),
            'shopRating' => [
                'average' => $shop->averageRating(),
                'count' => $shop->reviews_count,
            ],
        ]);
    }
}
