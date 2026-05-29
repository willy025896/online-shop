<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BroadcastsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReviewCoolingResetNotification extends Notification
{
    use BroadcastsAsArray, Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review.cooling_reset',
            'title' => __('notifications.review.cooling_reset.title'),
            'body' => __('notifications.review.cooling_reset.body', [
                'number' => $this->order->order_number,
            ]),
            'url' => $notifiable->id === $this->order->user_id
                ? route('orders.show', $this->order)
                : route('seller.reviews.index'),
            'meta' => [
                'order_id' => $this->order->id,
            ],
        ];
    }
}
