<?php

namespace App\Notifications;

use App\Models\ProductReview;
use App\Notifications\Concerns\BroadcastsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SellerReplyNotification extends Notification
{
    use BroadcastsAsArray, Queueable;

    public function __construct(public ProductReview $review) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review.seller_replied',
            'title' => __('notifications.review.seller_replied.title'),
            'body' => __('notifications.review.seller_replied.body', [
                'product' => $this->review->product->name,
            ]),
            'url' => route('products.show', $this->review->product),
            'meta' => [
                'product_review_id' => $this->review->id,
                'product_id' => $this->review->product_id,
            ],
        ];
    }
}
