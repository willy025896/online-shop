<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReview extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'product_id',
        'shop_id',
        'user_id',
        'order_item_id',
        'rating',
        'comment',
        'seller_reply',
        'seller_replied_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'seller_replied_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function isPublished(): bool
    {
        return $this->orderItem->order->review_released_at !== null
            && $this->status === self::STATUS_PUBLISHED;
    }

    public function scopeReleased($query)
    {
        return $query->whereHas('orderItem.order', fn ($q) => $q->whereNotNull('review_released_at'));
    }

    public function scopePublished($query)
    {
        return $query->released()->where('status', self::STATUS_PUBLISHED);
    }
}
