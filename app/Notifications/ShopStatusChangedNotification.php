<?php

namespace App\Notifications;

use App\Models\Shop;
use App\Notifications\Concerns\BroadcastsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShopStatusChangedNotification extends Notification
{
    use BroadcastsAsArray, Queueable;

    public function __construct(public Shop $shop) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $key = $this->shop->status === Shop::STATUS_APPROVED ? 'approved' : 'suspended';

        return [
            'type' => 'shop.'.$key,
            'title' => __('notifications.shop.'.$key.'.title'),
            'body' => __('notifications.shop.'.$key.'.body', ['name' => $this->shop->name]),
            'url' => route('seller.dashboard'),
            'meta' => [
                'shop_id' => $this->shop->id,
                'status' => $this->shop->status,
            ],
        ];
    }
}
