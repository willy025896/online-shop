<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function update(User $user, Coupon $coupon): bool
    {
        // Shop-scoped coupons: the owning seller (or an admin). Platform-wide
        // coupons (shop_id null) are admin-only.
        return ($coupon->shop !== null && $user->id === $coupon->shop->user_id) || $user->isAdmin();
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $this->update($user, $coupon);
    }
}
