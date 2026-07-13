<?php

namespace App\Notifications;

use App\Models\Product;
use App\Notifications\Concerns\BroadcastsAsArray;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WishlistBackInStockNotification extends Notification implements ShouldQueue
{
    use BroadcastsAsArray, Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'wishlist.back_in_stock',
            'title' => __('notifications.wishlist.back_in_stock.title'),
            'body' => __('notifications.wishlist.back_in_stock.body', [
                'product' => $this->product->name,
            ]),
            'url' => route('products.show', $this->product),
            'meta' => [
                'product_id' => $this->product->id,
            ],
        ];
    }
}
