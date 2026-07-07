<?php

namespace App\Models;

use App\Models\Concerns\HasPayoutAmounts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutItem extends Model
{
    use HasFactory, HasPayoutAmounts;

    protected $fillable = [
        'payout_id',
        'order_id',
        'gross_amount',
        'commission_amount',
        'shipping_amount',
        'net_amount',
    ];

    protected function casts(): array
    {
        return self::payoutAmountCasts();
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
