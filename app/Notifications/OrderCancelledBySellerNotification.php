<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BroadcastsAsArray;
use App\Notifications\Concerns\MailsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderCancelledBySellerNotification extends Notification implements ShouldQueue
{
    use BroadcastsAsArray, MailsAsArray, Queueable;

    public function __construct(public Order $order) {}

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order.cancelled_by_seller',
            'title' => __('notifications.order.cancelled_by_seller.title'),
            'body' => __('notifications.order.cancelled_by_seller.body', ['number' => $this->order->order_number]),
            'url' => route('orders.show', $this->order),
            'meta' => ['order_id' => $this->order->id],
        ];
    }
}
