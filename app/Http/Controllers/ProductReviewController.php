<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductReview;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    public function create(Order $order)
    {
        $this->authorize('view', $order);

        abort_if(! $order->isReviewWindowOpen(), 403, '評價窗口已關閉');
        abort_if($order->status !== \App\Models\Order::STATUS_COMPLETED, 403, '只有已完成訂單可以評論');

        $order->load('items.product.primaryImage', 'items.review', 'shop');

        $reviewableItems = $order->items->filter(
            fn ($item) => $item->review === null
        )->values();

        return Inertia::render('Reviews/Create', [
            'order' => $order,
            'reviewableItems' => $reviewableItems,
            'coolingUntil' => $order->review_cooling_until?->toIso8601String(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_item_id' => 'required|exists:order_items,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $orderItem = OrderItem::with('order')->findOrFail($validated['order_item_id']);

        $this->authorize('createReview', $orderItem->order);

        $this->reviewService->submitProductReview($orderItem, $validated);

        return back()->with('success', '評論已送出。');
    }

    public function update(Request $request, ProductReview $productReview)
    {
        $this->authorize('update', $productReview);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $this->reviewService->updateProductReview($productReview, $validated);

        return back()->with('success', '評論已更新。');
    }

    public function destroy(ProductReview $productReview)
    {
        $this->authorize('delete', $productReview);

        $this->reviewService->deleteProductReview($productReview);

        return back()->with('success', '評論已刪除。');
    }
}
