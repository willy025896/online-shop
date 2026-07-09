<?php

namespace App\Notifications;

use App\Models\OrderCancellation;
use App\Notifications\Concerns\BroadcastsAsArray;
use App\Notifications\Concerns\MailsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderCancellationRespondedNotification extends Notification implements ShouldQueue
{
    use BroadcastsAsArray, MailsAsArray, Queueable;

    public function __construct(public OrderCancellation $cancellation) {}

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
}
