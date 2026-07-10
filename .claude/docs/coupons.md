# Coupons (折扣碼)

`CouponService` (`app/Services/CouponService.php`) is the single source of truth for coupon validation, discount math and redemption — controllers and `OrderService` never re-implement these rules. See ADR-008.

Ownership & scope:
- **Seller-owned, per-shop** — `coupons.shop_id` points at the owning shop. It is **nullable**: `null` = platform-wide (future admin coupons; the data layer already supports it, no migration needed later), non-null = a seller's shop coupon (v1). `CouponPolicy` gates update/delete to the owning seller or an admin.
- **Applied per shop at checkout** — one code per shop, discounting only that shop's sub-order (mirrors the per-shop order split). `Coupon::TYPE_PERCENTAGE` / `TYPE_FIXED` are model constants — never raw strings.

Discount rule:
- Discount applies to the **shop goods subtotal only** — `total = subtotal - discount + shipping_fee`. Shipping is never discounted, and the **free-shipping threshold is still evaluated on the pre-discount subtotal**.
- `discountFor($coupon, $subtotal)` — percentage respects the optional `max_discount` cap; the result is clamped to never exceed the subtotal.

Key design points:
- **Back-end is authoritative** — `POST /checkout/coupon/preview` (`CouponController`, a JSON endpoint like `LangController::getComponents`) is **display-only**; it computes the shop subtotal from the real cart (never trusts the client). `OrderService::createOrdersFromCart($cart, $shippingData, $itemIds, $appliedCoupons)` **re-validates and recomputes** the discount inside the order transaction; a stale/exhausted code throws `CouponException` and rolls back the whole checkout (same as insufficient stock).
- **Redemption is atomic & locked** — `CouponService::redeem()` runs inside the order transaction, takes `Coupon::lockForUpdate()`, re-checks `usage_limit` / `per_user_limit`, increments `used_count` and writes a `coupon_redemptions` row (mirrors the `Product::lockForUpdate()` stock decrement).
- **Code snapshot** — `orders.coupon_code` stores the code string at order time (like `order_items.product_name`), so later editing/deleting the coupon never changes historical orders.
- **Cancellation releases the coupon** — `OrderService::finalizeCancellation` calls `CouponService::releaseForOrder()` (the inverse of `redeem()` — deletes the `coupon_redemptions` row and decrements `used_count`) alongside restoring stock, so a cancelled order never permanently burns the buyer's `per_user_limit` or the total budget. Every `used_count`/redemption mutation stays inside `CouponService`.
- **Uniqueness** — active-code uniqueness is enforced in `Seller\CouponController` via `Rule::unique(...)->whereNull('deleted_at')`; the `coupons.code` column is only **indexed**, not DB-unique, so a soft-deleted code can be reused (MySQL can't express a partial unique index).
- **Error messages** — `CouponException` carries a machine `reason`; boundaries translate via `lang/{locale}/coupons.php` → `errors.{reason}` (`translatedMessage()`).
