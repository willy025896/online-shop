<?php

namespace App\Http\Controllers;

use App\Exceptions\CouponException;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\ShippingService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Live coupon preview for the checkout page (JSON endpoint, like
     * LangController::getComponents). The shop subtotal is computed from the
     * user's real cart — never trusted from the client — but this is display
     * only; OrderService re-validates and recomputes at order creation.
     */
    public function preview(Request $request, CartService $cartService, CouponService $couponService, ShippingService $shippingService)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
            'shop_id' => 'required|integer',
            'item_ids' => 'array',
            'item_ids.*' => 'integer',
        ]);

        $cart = $cartService->getOrCreateCart();
        $cart->load('items.product.shop'); // enough for ShippingService::breakdownForItems (no images)

        if ($cart->items->isEmpty()) {
            return response()->json(['valid' => false, 'message' => __('coupons.errors.empty_cart')]);
        }

        $items = ! empty($data['item_ids'])
            ? $cart->items->whereIn('id', $data['item_ids'])
            : $cart->items;

        // Reuse the single owner of the per-shop grouping rule (also skips
        // soft-deleted products) rather than re-summing here.
        $shopRow = $shippingService->breakdownForItems($items)
            ->firstWhere('shop_id', (int) $data['shop_id']);
        $subtotal = $shopRow['subtotal'] ?? 0;

        try {
            $coupon = $couponService->validate($data['code'], (int) $data['shop_id'], (float) $subtotal, $request->user()->id);
            $discount = $couponService->discountFor($coupon, (float) $subtotal);

            return response()->json([
                'valid' => true,
                'code' => $coupon->code,
                'discount' => $discount,
            ]);
        } catch (CouponException $e) {
            return response()->json(['valid' => false, 'message' => $e->translatedMessage()]);
        }
    }
}
