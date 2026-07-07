<?php

namespace App\Notifications;

use App\Models\OrderReturn;
use App\Notifications\Concerns\BroadcastsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderReturnRespondedNotification extends Notification
{
    use BroadcastsAsArray, Queueable;

    public function __construct(public OrderReturn $orderReturn) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $order = $this->orderReturn->order;
        $approved = $this->orderReturn->status === OrderReturn::STATUS_APPROVED;
        $key = $approved ? 'approved' : 'rejected';

        return [
            'type' => 'order.return_'.$key,
            'title' => __('notifications.order.return_'.$key.'.title'),
            'body' => __('notifications.order.return_'.$key.'.body', ['number' => $order->order_number]),
            'url' => route('orders.show', $order),
            'meta' => [
                'order_id' => $order->id,
                'return_id' => $this->orderReturn->id,
                'status' => $this->orderReturn->status,
                'refund_amount' => $approved ? (float) $this->orderReturn->refund_amount : null,
            ],
        ];
    }
}
