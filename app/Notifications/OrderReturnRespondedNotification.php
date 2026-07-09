<?php

namespace App\Notifications;

use App\Models\OrderReturn;
use App\Notifications\Concerns\BroadcastsAsArray;
use App\Notifications\Concerns\MailsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderReturnRespondedNotification extends Notification implements ShouldQueue
{
    use BroadcastsAsArray, MailsAsArray, Queueable;

    public function __construct(public OrderReturn $orderReturn) {}

    public function toArray(object $notifiable): array
    {
        $order = $this->orderReturn->order;
        $approved = $this->orderReturn->status === OrderReturn::STATUS_APPROVED;
        $key = $approved ? 'approved' : 'rejected';

        $bodyParams = ['number' => $order->order_number];
        if ($approved) {
            $bodyParams['amount'] = number_format((float) $this->orderReturn->refund_amount, 2);
        }

        return [
            'type' => 'order.return_'.$key,
            'title' => __('notifications.order.return_'.$key.'.title'),
            'body' => __('notifications.order.return_'.$key.'.body', $bodyParams),
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
