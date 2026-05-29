<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class ReviewReplyController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    public function store(Request $request, ProductReview $productReview)
    {
        $shop = auth()->user()->shop;
        abort_if($productReview->shop_id !== $shop->id, 403);

        $validated = $request->validate([
            'reply' => 'required|string|max:1000',
        ]);

        $this->reviewService->addSellerReply($productReview, $validated['reply']);

        return back()->with('success', '回覆已送出。');
    }
}
