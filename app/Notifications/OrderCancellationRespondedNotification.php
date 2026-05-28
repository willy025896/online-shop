<?php

namespace App\Notifications;

use App\Models\OrderCancellation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OrderCancellationRespondedNotification extends Notification
{
    use Queueable;

    public function __construct(public OrderCancellation $cancellation) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $order = $this->cancellation->order;
        $approved = $this->cancellation->status === OrderCancellation::STATUS_APPROVED;
        $key = $approved ? 'approved' : 'rejected';

        return [
            'type' => 'order.cancellation_'.$key,
            'title' => __('notifications.order.cancellation_'.$key.'.title'),
            'body' => __('notifications.order.cancellation_'.$key.'.body', ['number' => $order->order_number]),
            'url' => route('orders.show', $order),
            'meta' => [
                'order_id' => $order->id,
                'cancellation_id' => $this->cancellation->id,
                'status' => $this->cancellation->status,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
