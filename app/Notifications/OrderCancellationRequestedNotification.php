<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OrderCancellationRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

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

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
