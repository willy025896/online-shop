<?php

namespace App\Notifications;

use App\Models\Product;
use App\Notifications\Concerns\BroadcastsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WishlistPriceDropNotification extends Notification implements ShouldQueue
{
    use BroadcastsAsArray, Queueable;

    public function __construct(public Product $product, public string $oldPrice) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'wishlist.price_drop',
            'title' => __('notifications.wishlist.price_drop.title'),
            'body' => __('notifications.wishlist.price_drop.body', [
                'product' => $this->product->name,
                'old' => number_format((float) $this->oldPrice, 2),
                'new' => number_format((float) $this->product->price, 2),
            ]),
            'url' => route('products.show', $this->product),
            'meta' => [
                'product_id' => $this->product->id,
            ],
        ];
    }
}
