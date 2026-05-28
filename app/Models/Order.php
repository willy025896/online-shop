<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    protected static function booted(): void
    {
        static::updated(function (Order $order) {
            if ($order->wasChanged('status')) {
                $order->statusLogs()->create([
                    'from_status' => $order->getOriginal('status'),
                    'to_status' => $order->status,
                    'changed_by' => auth()->id(),
                ]);
            }
        });
    }

    protected $fillable = [
        'order_number',
        'user_id',
        'shop_id',
        'status',
        'subtotal',
        'shipping_fee',
        'total',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'payment_method',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function conversation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(OrderCancellation::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function latestCancellation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrderCancellation::class)->latestOfMany();
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
