<?php

namespace App\Models\Concerns;

/**
 * Shared decimal casts for the four money columns common to both Payout
 * (batch totals) and PayoutItem (per-order snapshot), so the cast list
 * isn't copy-pasted between the two models.
 */
trait HasPayoutAmounts
{
    protected static function payoutAmountCasts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }
}
