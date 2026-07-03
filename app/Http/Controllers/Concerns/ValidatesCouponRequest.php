<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Shared coupon field validation for both Seller\CouponController (shop-scoped
 * coupons) and Admin\CouponController (platform-wide coupons) — the field
 * rules are identical, only the shop_id assignment differs per caller.
 */
trait ValidatesCouponRequest
{
    private function validatedCouponFields(Request $request, ?Coupon $coupon = null): array
    {
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('coupons', 'code')->whereNull('deleted_at')->ignore($coupon?->id),
            ],
            'type' => ['required', Rule::in([Coupon::TYPE_PERCENTAGE, Coupon::TYPE_FIXED])],
            'value' => [
                'required', 'numeric', 'min:0.01',
                // A percentage discount can never exceed 100%.
                Rule::when($request->input('type') === Coupon::TYPE_PERCENTAGE, ['max:100']),
            ],
            'min_spend' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = CouponService::normalize($validated['code']);
        $validated['min_spend'] = $validated['min_spend'] ?? 0;

        return $validated;
    }
}
