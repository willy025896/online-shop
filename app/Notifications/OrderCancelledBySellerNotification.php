<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OrderCancelledBySellerNotification extends Notification
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
            'type' => 'order.cancelled_by_seller',
            'title' => __('notifications.order.cancelled_by_seller.title'),
            'body' => __('notifications.order.cancelled_by_seller.body', ['number' => $this->order->order_number]),
            'url' => route('orders.show', $this->order),
            'meta' => ['order_id' => $this->order->id],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
