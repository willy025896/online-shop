<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BroadcastsAsArray;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReviewCoolingStartedNotification extends Notification
{
    use BroadcastsAsArray, Queueable;

    public function __construct(
        public Order $order,
        public Carbon $coolingUntil,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review.cooling_started',
            'title' => __('notifications.review.cooling_started.title'),
            'body' => __('notifications.review.cooling_started.body', [
                'number' => $this->order->order_number,
                'time' => $this->coolingUntil->format('Y-m-d H:i'),
            ]),
            'url' => route('orders.show', $this->order),
            'meta' => [
                'order_id' => $this->order->id,
                'cooling_until' => $this->coolingUntil->toIso8601String(),
            ],
        ];
    }
}
