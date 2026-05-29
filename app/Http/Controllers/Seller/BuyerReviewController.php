<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\BuyerReview;
use App\Models\Order;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BuyerReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    public function create(Order $order)
    {
        $shop = auth()->user()->shop;

        abort_if($order->shop_id !== $shop->id, 403);
        abort_if($order->status !== Order::STATUS_COMPLETED, 403, '只有已完成訂單可以評論');
        abort_if(! $order->isReviewWindowOpen(), 403, '評價窗口已關閉');
        abort_if($order->buyerReview()->exists(), 422, '此訂單已評論過');

        $order->load('user', 'items.product');

        return Inertia::render('Seller/Reviews/BuyerReviewCreate', [
            'order' => $order,
            'coolingUntil' => $order->review_cooling_until?->toIso8601String(),
        ]);
    }

    public function store(Request $request, Order $order)
    {
        $shop = auth()->user()->shop;

        abort_if($order->shop_id !== $shop->id, 403);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $this->reviewService->submitBuyerReview($order, $shop, $validated);

        return redirect()->route('seller.orders.show', $order)
            ->with('success', '對買家的評價已送出。');
    }

    public function update(Request $request, BuyerReview $buyerReview)
    {
        $shop = auth()->user()->shop;
        abort_if($buyerReview->shop_id !== $shop->id, 403);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $this->reviewService->updateBuyerReview($buyerReview, $validated);

        return back()->with('success', '評價已更新。');
    }

    public function destroy(BuyerReview $buyerReview)
    {
        $shop = auth()->user()->shop;
        abort_if($buyerReview->shop_id !== $shop->id, 403);

        $this->reviewService->deleteBuyerReview($buyerReview);

        return back()->with('success', '評價已刪除。');
    }
}
