<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order, public string $newStatus) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

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

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
