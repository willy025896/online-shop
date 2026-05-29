<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BroadcastsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPaidNotification extends Notification
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
            'type' => 'order.paid',
            'title' => __('notifications.order.paid.title'),
            'body' => __('notifications.order.paid.body', ['number' => $this->order->order_number]),
            'url' => route('seller.orders.show', $this->order),
            'meta' => ['order_id' => $this->order->id],
        ];
    }
}
