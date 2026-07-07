<?php

namespace App\Models;

use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Forward progress ranking of the normal fulfillment flow. Higher = later.
     * Used to forbid backward transitions while still allowing legitimate skips
     * (e.g. pending→shipped for cash-on-delivery, paid→completed for virtual goods).
     * Terminal states (completed, cancelled) are intentionally excluded as sources.
     */
    private const STATUS_RANK = [
        self::STATUS_PENDING => 0,
        self::STATUS_PAID => 1,
        self::STATUS_PROCESSING => 2,
        self::STATUS_SHIPPED => 3,
        self::STATUS_COMPLETED => 4,
    ];

    /**
     * Buyer-facing milestone statuses worth a generic notification.
     * `cancelled` is intentionally excluded — every cancellation path
     * (buyer direct, seller direct, request approved/rejected) already
     * fires its own purpose-specific notification, so adding the generic
     * one here would double-notify the buyer or self-notify them.
     */
    private const BUYER_NOTIFY_STATUSES = [
        self::STATUS_PAID,
        self::STATUS_SHIPPED,
        self::STATUS_COMPLETED,
    ];

    protected static function booted(): void
    {
        static::updating(function (Order $order) {
            if ($order->isDirty('status')
                && $order->status === self::STATUS_COMPLETED
                && $order->completed_at === null) {
                $order->completed_at = now();
            }
        });

        static::updated(function (Order $order) {
            if ($order->wasChanged('status')) {
                $order->statusLogs()->create([
                    'from_status' => $order->getOriginal('status'),
                    'to_status' => $order->status,
                    'changed_by' => auth()->id(),
                ]);

                if (in_array($order->status, self::BUYER_NOTIFY_STATUSES, true)) {
                    $order->loadMissing('user');
                    $order->user?->notify(new OrderStatusChangedNotification($order, $order->status));
                }
            }
        });
    }

    protected $fillable = [
        'order_number',
        'user_id',
        'shop_id',
        'coupon_id',
        'coupon_code',
        'status',
        'subtotal',
        'discount',
        'shipping_fee',
        'total',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'payment_method',
        'paid_at',
        'completed_at',
        'notes',
        'review_cooling_until',
        'review_released_at',
        'refunded_amount',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'review_cooling_until' => 'datetime',
            'review_released_at' => 'datetime',
            'refunded_amount' => 'decimal:2',
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

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(OrderCancellation::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function productReviews(): HasManyThrough
    {
        return $this->hasManyThrough(ProductReview::class, OrderItem::class)
            ->where('product_reviews.status', ProductReview::STATUS_PUBLISHED);
    }

    public function buyerReview(): HasOne
    {
        return $this->hasOne(BuyerReview::class);
    }

    public function latestCancellation(): HasOne
    {
        return $this->hasOne(OrderCancellation::class)->latestOfMany();
    }

    public function latestReturn(): HasOne
    {
        return $this->hasOne(OrderReturn::class)->latestOfMany();
    }

    public function payoutItem(): HasOne
    {
        return $this->hasOne(PayoutItem::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function pendingCancellation(): ?OrderCancellation
    {
        if ($this->relationLoaded('cancellations')) {
            return $this->cancellations->firstWhere('status', OrderCancellation::STATUS_REQUESTED);
        }

        return $this->cancellations()->where('status', OrderCancellation::STATUS_REQUESTED)->first();
    }

    public function wasCancellationRejected(): bool
    {
        if ($this->relationLoaded('cancellations')) {
            return $this->cancellations->contains('status', OrderCancellation::STATUS_REJECTED);
        }

        return $this->cancellations()->where('status', OrderCancellation::STATUS_REJECTED)->exists();
    }

    public function canBeCancelledDirectly(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PAID]);
    }

    public function canRequestCancellation(): bool
    {
        return $this->status === self::STATUS_PROCESSING
            && $this->pendingCancellation() === null
            && ! $this->wasCancellationRejected();
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function canBeCancelledBySeller(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PAID, self::STATUS_PROCESSING])
            && $this->pendingCancellation() === null;
    }

    public function pendingReturn(): ?OrderReturn
    {
        if ($this->relationLoaded('returns')) {
            return $this->returns->firstWhere('status', OrderReturn::STATUS_REQUESTED);
        }

        // Only one return can be pending at a time (canRequestReturn() blocks a
        // new request while one exists), so the most recent return — when
        // already eager-loaded as latestReturn — is the pending one iff its
        // status is still "requested". Avoids a redundant query in the common
        // case where callers already loaded latestReturn (e.g. order show pages).
        if ($this->relationLoaded('latestReturn')) {
            return $this->latestReturn?->status === OrderReturn::STATUS_REQUESTED ? $this->latestReturn : null;
        }

        return $this->returns()->where('status', OrderReturn::STATUS_REQUESTED)->first();
    }

    public function canRequestReturn(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->completed_at !== null
            && now()->lte($this->completed_at->copy()->addDays(config('returns.window_days')))
            && $this->pendingReturn() === null;
    }

    /**
     * Orders whose return window has fully closed — the complement of
     * canRequestReturn()'s window check (strictly past vs. that method's "up
     * to and including" boundary), so the two never both hold at once. Used
     * by PayoutService to find orders whose amount can no longer change.
     */
    public function scopePastReturnWindow($query)
    {
        return $query->whereNotNull('completed_at')
            ->where('completed_at', '<', now()->subDays(config('returns.window_days')));
    }

    /**
     * Orders with no return request still awaiting seller review — the
     * query-level counterpart of pendingReturn().
     */
    public function scopeWithoutPendingReturn($query)
    {
        return $query->whereDoesntHave('returns', fn ($q) => $q->where('status', OrderReturn::STATUS_REQUESTED));
    }

    /**
     * True once every order item's approved-return quantity has caught up to
     * its originally ordered quantity — i.e. the order is returned in full,
     * possibly across several separate return requests over time.
     */
    public function isFullyReturned(): bool
    {
        return $this->items->every(fn (OrderItem $item) => $item->returnedQuantity() >= $item->quantity);
    }

    public function isReviewWindowOpen(): bool
    {
        return $this->review_released_at === null;
    }

    public function isInCoolingPeriod(): bool
    {
        return $this->review_cooling_until !== null
            && $this->review_cooling_until->isFuture()
            && $this->review_released_at === null;
    }

    public function canTransitionStatusTo(string $target): bool
    {
        // Terminal orders cannot move (blocks reviving a cancelled/completed order).
        if (! $this->isActive()) {
            return false;
        }

        // Cannot change fulfillment status while a buyer cancellation awaits review.
        if ($this->pendingCancellation() !== null) {
            return false;
        }

        // Forward-only: target must rank strictly later than the current status.
        $current = self::STATUS_RANK[$this->status] ?? -1;
        $next = self::STATUS_RANK[$target] ?? -1;

        return $next > $current;
    }
}
