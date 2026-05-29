<?php

namespace App\Notifications;

use App\Models\BuyerReview;
use App\Models\Order;
use App\Notifications\Concerns\BroadcastsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReviewReleasedNotification extends Notification
{
    use BroadcastsAsArray, Queueable;

    /**
     * @param  BuyerReview|null  $buyerReview  The review of the buyer (for buyer-facing notifications)
     * @param  array{count:int, avg:float|null, ratings:array<int,int>}  $productReviewStats  Stats over all product reviews on this order (for seller-facing notifications)
     */
    public function __construct(
        public Order $order,
        public ?BuyerReview $buyerReview,
        public array $productReviewStats = ['count' => 0, 'avg' => null, 'ratings' => []],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $isBuyer = $notifiable->id === $this->order->user_id;

        if ($isBuyer) {
            return [
                'type' => 'review.released',
                'title' => __('notifications.review.released.title'),
                'body' => __('notifications.review.released.body', [
                    'number' => $this->order->order_number,
                ]),
                'url' => route('orders.show', $this->order),
                'meta' => [
                    'order_id' => $this->order->id,
                    // The seller's rating of THIS buyer (what's newly visible to them
                    // at release). buyerReview = the row on buyer_reviews table = the
                    // shop's evaluation of the buyer.
                    'seller_rating_of_buyer' => $this->buyerReview?->rating,
                ],
            ];
        }

        return [
            'type' => 'review.released',
            'title' => __('notifications.review.released.title'),
            'body' => __('notifications.review.released.body', [
                'number' => $this->order->order_number,
            ]),
            'url' => route('seller.reviews.index'),
            'meta' => [
                'order_id' => $this->order->id,
                'product_reviews_count' => $this->productReviewStats['count'],
                'product_reviews_avg' => $this->productReviewStats['avg'],
                'product_reviews_ratings' => $this->productReviewStats['ratings'],
            ],
        ];
    }
}
