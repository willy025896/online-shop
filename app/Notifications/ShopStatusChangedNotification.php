<?php

namespace App\Notifications;

use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ShopStatusChangedNotification extends Notification
{
    use Queueable;

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

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
