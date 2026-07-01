<?php

namespace App\Services;

use App\Exceptions\CouponException;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;

/**
 * Single source of truth for coupon validation, discount math and redemption
 * (and its inverse, release). Mirrors ShippingService's role: controllers and
 * OrderService never re-implement these rules. Discount always applies to a
 * shop's goods subtotal (never shipping).
 */
class CouponService
{
    /**
     * Validate a code for a given shop + subtotal + user. Returns the Coupon
     * when applicable, otherwise throws CouponException with a reason code.
     *
     * $shopId is the shop whose sub-order the code is being applied to. A coupon
     * with shop_id === null is platform-wide (future) and matches any shop.
     */
    public function validate(string $code, ?int $shopId, float $subtotal, int $userId): Coupon
    {
        $coupon = Coupon::where('code', self::normalize($code))->first();

        if ($coupon === null) {
            throw new CouponException('not_found');
        }

        if (! $coupon->is_active) {
            throw new CouponException('inactive');
        }

        if ($coupon->starts_at !== null && now()->lt($coupon->starts_at)) {
            throw new CouponException('not_started');
        }

        if ($coupon->expires_at !== null && now()->gt($coupon->expires_at)) {
            throw new CouponException('expired');
        }

        if ($coupon->shop_id !== null && $coupon->shop_id !== $shopId) {
            throw new CouponException('wrong_shop');
        }

        if ($subtotal < (float) $coupon->min_spend) {
            throw new CouponException('min_spend');
        }

        if (! $coupon->hasRemainingUses()) {
            throw new CouponException('usage_exhausted');
        }

        if ($this->perUserExhausted($coupon, $userId)) {
            throw new CouponException('per_user_exhausted');
        }

        return $coupon;
    }

    /**
     * Discount amount for $coupon against $subtotal, clamped to never exceed
     * the subtotal. Percentage discounts respect the optional max_discount cap.
     */
    public function discountFor(Coupon $coupon, float $subtotal): float
    {
        if ($coupon->type === Coupon::TYPE_PERCENTAGE) {
            $raw = $subtotal * ((float) $coupon->value / 100);
            if ($coupon->max_discount !== null) {
                $raw = min($raw, (float) $coupon->max_discount);
            }
        } else {
            $raw = (float) $coupon->value;
        }

        return round(min($raw, $subtotal), 2);
    }

    /**
     * Record a redemption. MUST run inside the caller's DB::transaction (e.g.
     * OrderService's order-creation transaction) so the usage counters commit
     * atomically with the order. Re-checks the usage limits under a row lock to
     * serialize concurrent redemptions (mirrors the stock lock in OrderService).
     */
    public function redeem(Coupon $coupon, int $userId, Order $order, float $amount): void
    {
        $locked = Coupon::lockForUpdate()->find($coupon->id);

        if (! $locked->hasRemainingUses()) {
            throw new CouponException('usage_exhausted');
        }

        if ($this->perUserExhausted($locked, $userId)) {
            throw new CouponException('per_user_exhausted');
        }

        $locked->increment('used_count');

        $locked->redemptions()->create([
            'user_id' => $userId,
            'order_id' => $order->id,
            'discount_amount' => $amount,
        ]);
    }

    /**
     * Inverse of redeem: release the coupon an order consumed (on cancellation)
     * so a cancelled order never permanently burns the buyer's per-user
     * allowance or the total budget. Runs inside the caller's transaction.
     */
    public function releaseForOrder(Order $order): void
    {
        $redemption = CouponRedemption::where('order_id', $order->id)->first();

        if ($redemption === null) {
            return;
        }

        Coupon::whereKey($redemption->coupon_id)
            ->where('used_count', '>', 0)
            ->decrement('used_count');

        $redemption->delete();
    }

    /**
     * The canonical form of a coupon code — the single definition shared by
     * the seller write path and the checkout lookup path.
     */
    public static function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }

    private function perUserExhausted(Coupon $coupon, int $userId): bool
    {
        if ($coupon->per_user_limit === null) {
            return false;
        }

        $used = $coupon->redemptions()->where('user_id', $userId)->count();

        return $used >= $coupon->per_user_limit;
    }
}
