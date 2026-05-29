<?php

namespace App\Services;

use App\Models\BuyerReview;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\ReviewCoolingResetNotification;
use App\Notifications\ReviewCoolingStartedNotification;
use App\Notifications\ReviewReleasedNotification;
use App\Notifications\SellerReplyNotification;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function submitProductReview(OrderItem $orderItem, array $data): ProductReview
    {
        return DB::transaction(function () use ($orderItem, $data) {
            $order = Order::lockForUpdate()->find($orderItem->order_id);

            abort_if($order->status !== Order::STATUS_COMPLETED, 422, '只有已完成訂單可以評論');
            abort_if(! $order->isReviewWindowOpen(), 422, '評價窗口已關閉');
            abort_if($orderItem->review()->exists(), 422, '此商品已評論過');

            $review = ProductReview::create([
                'product_id' => $orderItem->product_id,
                'shop_id' => $order->shop_id,
                'user_id' => $order->user_id,
                'order_item_id' => $orderItem->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            $this->checkAndStartCooling($order);

            return $review;
        });
    }

    public function updateProductReview(ProductReview $review, array $data): ProductReview
    {
        return DB::transaction(function () use ($review, $data) {
            $order = Order::lockForUpdate()->find($review->orderItem->order_id);

            abort_if(! $order->isReviewWindowOpen(), 422, '評價窗口已關閉');

            $review->update([
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            $this->resetCoolingIfActive($order, notifyParty: 'seller');

            return $review;
        });
    }

    public function deleteProductReview(ProductReview $review): void
    {
        DB::transaction(function () use ($review) {
            $order = Order::lockForUpdate()->find($review->orderItem->order_id);

            abort_if(! $order->isReviewWindowOpen(), 422, '評價窗口已關閉');

            $review->delete();

            $this->resetCoolingIfActive($order, notifyParty: 'seller');
        });
    }

    public function submitBuyerReview(Order $order, Shop $shop, array $data): BuyerReview
    {
        return DB::transaction(function () use ($order, $shop, $data) {
            $order = Order::lockForUpdate()->find($order->id);

            abort_if($order->status !== Order::STATUS_COMPLETED, 422, '只有已完成訂單可以評論');
            abort_if(! $order->isReviewWindowOpen(), 422, '評價窗口已關閉');
            abort_if($order->buyerReview()->exists(), 422, '此訂單已評論過');

            $review = BuyerReview::create([
                'user_id' => $order->user_id,
                'shop_id' => $shop->id,
                'order_id' => $order->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            $this->checkAndStartCooling($order);

            return $review;
        });
    }

    public function updateBuyerReview(BuyerReview $review, array $data): BuyerReview
    {
        return DB::transaction(function () use ($review, $data) {
            $order = Order::lockForUpdate()->find($review->order_id);

            abort_if(! $order->isReviewWindowOpen(), 422, '評價窗口已關閉');

            $review->update([
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            $this->resetCoolingIfActive($order, notifyParty: 'buyer');

            return $review;
        });
    }

    public function deleteBuyerReview(BuyerReview $review): void
    {
        DB::transaction(function () use ($review) {
            $order = Order::lockForUpdate()->find($review->order_id);

            abort_if(! $order->isReviewWindowOpen(), 422, '評價窗口已關閉');

            $review->delete();

            $this->resetCoolingIfActive($order, notifyParty: 'buyer');
        });
    }

    /**
     * Notify the counterparty when one side edits or deletes their review during the
     * still-open window. If cooling was active, also reset it so both sides retain a
     * fair opportunity to revise. Notification ALWAYS fires (even when cooling is
     * already null due to an earlier party's edit) so the second editor isn't silently
     * invisible to the first.
     * Must be called inside a transaction with the order already locked.
     *
     * @param  'buyer'|'seller'  $notifyParty  Which side to notify (i.e. the party who did NOT just edit)
     */
    private function resetCoolingIfActive(Order $order, string $notifyParty): void
    {
        if ($order->review_cooling_until !== null) {
            $order->update(['review_cooling_until' => null]);
        }

        $order->loadMissing('user', 'shop.user');

        $recipient = $notifyParty === 'buyer'
            ? $order->user
            : $order->shop->user;

        $recipient?->notify(new ReviewCoolingResetNotification($order));
    }

    public function addSellerReply(ProductReview $review, string $reply): ProductReview
    {
        return DB::transaction(function () use ($review, $reply) {
            $locked = ProductReview::lockForUpdate()->find($review->id);

            abort_if($locked->seller_replied_at !== null, 422, '已回覆過，無法再次回覆');

            $locked->update([
                'seller_reply' => $reply,
                'seller_replied_at' => now(),
            ]);

            $locked->loadMissing('user', 'product');
            $locked->user?->notify(new SellerReplyNotification($locked));

            return $locked;
        });
    }

    /**
     * Check if both sides have submitted reviews; if so, start the 24h cooling period.
     * Must be called inside a transaction with the order already locked.
     */
    private function checkAndStartCooling(Order $order): void
    {
        if ($order->review_cooling_until !== null) {
            return;
        }

        $hasBuyerReview = $order->items()
            ->whereHas('review')
            ->exists();

        $hasSellerReview = $order->buyerReview()->exists();

        if ($hasBuyerReview && $hasSellerReview) {
            $coolingUntil = now()->addHours(24);
            $order->update(['review_cooling_until' => $coolingUntil]);

            $order->loadMissing('user', 'shop.user');

            $order->user?->notify(new ReviewCoolingStartedNotification($order, $coolingUntil));
            $order->shop->user?->notify(new ReviewCoolingStartedNotification($order, $coolingUntil));
        }
    }

    /**
     * Release reviews for a single order: update aggregates and notify both parties.
     * Must be called inside a transaction with the order already locked.
     */
    public function releaseOrder(Order $order): void
    {
        if ($order->review_released_at !== null) {
            return;
        }

        $order->update(['review_released_at' => now()]);

        // productReviews relation is already scoped to STATUS_PUBLISHED.
        $productReviews = $order->productReviews()->get();

        // Force a fresh read of buyerReview after the row is locked, in case the
        // relation was eager-loaded before this call and a concurrent submission
        // committed between the load and the lock.
        $buyerReview = $order->buyerReview()->first();

        $this->updateAggregates($order, $productReviews, $buyerReview);

        if ($productReviews->isEmpty() && ! $buyerReview) {
            return;
        }

        $order->loadMissing('user', 'shop.user');

        $stats = [
            'count' => $productReviews->count(),
            'avg' => $productReviews->isNotEmpty()
                ? round($productReviews->avg('rating'), 1)
                : null,
            'ratings' => $productReviews->pluck('rating')->all(),
        ];

        $order->user?->notify(new ReviewReleasedNotification($order, $buyerReview, []));
        $order->shop->user?->notify(new ReviewReleasedNotification($order, null, $stats));
    }

    private function updateAggregates(Order $order, $productReviews, ?BuyerReview $buyerReview): void
    {
        if ($productReviews->isNotEmpty()) {
            // One UPDATE per product (all order items in an order share the same shop)
            foreach ($productReviews->groupBy('product_id') as $productId => $group) {
                Product::where('id', $productId)->update([
                    'reviews_count' => DB::raw('reviews_count + '.$group->count()),
                    'rating_sum' => DB::raw('rating_sum + '.$group->sum('rating')),
                ]);
            }

            // One UPDATE for the shop covering all reviews on this order
            Shop::where('id', $order->shop_id)->update([
                'reviews_count' => DB::raw('reviews_count + '.$productReviews->count()),
                'rating_sum' => DB::raw('rating_sum + '.$productReviews->sum('rating')),
            ]);
        }

        if ($buyerReview && $buyerReview->status === BuyerReview::STATUS_PUBLISHED) {
            User::where('id', $buyerReview->user_id)->update([
                'buyer_reviews_count' => DB::raw('buyer_reviews_count + 1'),
                'buyer_rating_sum' => DB::raw('buyer_rating_sum + '.(int) $buyerReview->rating),
            ]);
        }
    }
}
