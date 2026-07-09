<?php

namespace App\Notifications;

use App\Models\Payout;
use App\Notifications\Concerns\BroadcastsAsArray;
use App\Notifications\Concerns\MailsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PayoutCompletedNotification extends Notification implements ShouldQueue
{
    use BroadcastsAsArray, MailsAsArray, Queueable;

    public function __construct(public Payout $payout) {}

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
