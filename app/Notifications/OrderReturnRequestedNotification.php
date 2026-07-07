<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BroadcastsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderReturnRequestedNotification extends Notification
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
            'type' => 'order.return_requested',
            'title' => __('notifications.order.return_requested.title'),
            'body' => __('notifications.order.return_requested.body', ['number' => $this->order->order_number]),
            'url' => route('seller.orders.show', $this->order),
            'meta' => ['order_id' => $this->order->id],
        ];
    }
}
