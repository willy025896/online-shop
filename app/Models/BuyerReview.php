<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuyerReview extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'user_id',
        'shop_id',
        'order_id',
        'rating',
        'comment',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeReleased($query)
    {
        return $query->whereHas('order', fn ($q) => $q->whereNotNull('review_released_at'));
    }

    public function scopePublished($query)
    {
        return $query->released()->where('status', self::STATUS_PUBLISHED);
    }
}
