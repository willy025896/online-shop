<?php

namespace App\Models;

use App\Models\Concerns\HasPayoutAmounts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payout extends Model
{
    use HasFactory, HasPayoutAmounts;

    protected $fillable = [
        'shop_id',
        'gross_amount',
        'commission_amount',
        'shipping_amount',
        'net_amount',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [...self::payoutAmountCasts(), 'paid_at' => 'datetime'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayoutItem::class);
    }
}
