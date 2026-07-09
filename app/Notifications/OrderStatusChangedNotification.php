<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BroadcastsAsArray;
use App\Notifications\Concerns\MailsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use BroadcastsAsArray, MailsAsArray, Queueable;

    public function __construct(public Order $order, public string $newStatus) {}

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order.status_changed',
            'title' => __('notifications.order.status_changed.title'),
            'body' => __('notifications.order.status_changed.body', [
                'number' => $this->order->order_number,
                'status' => __('notifications.order.status.'.$this->newStatus),
            ]),
            'url' => route('orders.show', $this->order),
            'meta' => [
                'order_id' => $this->order->id,
                'status' => $this->newStatus,
            ],
        ];
    }
}
