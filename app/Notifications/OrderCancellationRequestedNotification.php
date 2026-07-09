<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BroadcastsAsArray;
use App\Notifications\Concerns\MailsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderCancellationRequestedNotification extends Notification implements ShouldQueue
{
    use BroadcastsAsArray, MailsAsArray, Queueable;

    public function __construct(public Order $order) {}

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order.cancellation_requested',
            'title' => __('notifications.order.cancellation_requested.title'),
            'body' => __('notifications.order.cancellation_requested.body', ['number' => $this->order->order_number]),
            'url' => route('seller.orders.show', $this->order),
            'meta' => ['order_id' => $this->order->id],
        ];
    }
}
