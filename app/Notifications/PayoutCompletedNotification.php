<?php

namespace App\Notifications;

use App\Models\Payout;
use App\Notifications\Concerns\BroadcastsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PayoutCompletedNotification extends Notification
{
    use BroadcastsAsArray, Queueable;

    public function __construct(public Payout $payout) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order.payout_completed',
            'title' => __('notifications.order.payout_completed.title'),
            'body' => __('notifications.order.payout_completed.body', ['amount' => number_format((float) $this->payout->net_amount, 2)]),
            'url' => route('seller.payouts.index'),
            'meta' => [
                'payout_id' => $this->payout->id,
                'net_amount' => (float) $this->payout->net_amount,
            ],
        ];
    }
}
