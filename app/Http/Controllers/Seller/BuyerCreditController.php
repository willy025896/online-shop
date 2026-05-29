<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\BuyerReview;
use App\Models\User;
use Inertia\Inertia;

class BuyerCreditController extends Controller
{
    public function show(User $user)
    {
        $shop = auth()->user()->shop;

        // Seller may only view buyers who have ordered from their shop
        $hasBought = $shop->orders()->where('user_id', $user->id)->exists();
        abort_if(! $hasBought, 403);

        $reviews = BuyerReview::with(['shop:id,name'])
            ->where('user_id', $user->id)
            ->published()
            ->select(['id', 'user_id', 'shop_id', 'order_id', 'rating', 'comment', 'created_at'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Seller/Buyers/Show', [
            'buyer' => $user->only('id', 'name', 'buyer_reviews_count', 'buyer_rating_sum'),
            'buyerRating' => [
                'average' => $user->averageBuyerRating(),
                'count' => $user->buyer_reviews_count,
            ],
            'reviews' => $reviews,
        ]);
    }
}
