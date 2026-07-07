<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'product_image',
        'variant_label',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function review(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProductReview::class);
    }

    public function returnItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    /**
     * Total quantity of this line item already covered by approved returns —
     * used to cap how much of it can still be requested for return.
     */
    public function returnedQuantity(): int
    {
        return (int) $this->returnItems()
            ->whereHas('orderReturn', fn ($query) => $query->where('status', OrderReturn::STATUS_APPROVED))
            ->sum('quantity');
    }

    /**
     * How many units of this line item are still eligible to be requested for
     * return — the single definition shared by the buyer-facing display and
     * the server-side quantity cap in OrderController::requestReturn().
     */
    public function remainingReturnableQuantity(): int
    {
        return max(0, $this->quantity - $this->returnedQuantity());
    }
}
