# Shipping

`ShippingService` (`app/Services/ShippingService.php`) is the single source of truth for shipping fees. The rule is a **flat fee with a free-shipping threshold**, configured globally in `config/shipping.php` (`flat_fee`, `free_threshold`; both overridable via `SHIPPING_FLAT_FEE` / `SHIPPING_FREE_THRESHOLD` env vars). Set `free_threshold` to `null` to disable free shipping.

Key design points:
- **Per-shop calculation** — shipping is evaluated **per shop**, mirroring the per-shop order split in `OrderService::createOrdersFromCart` (one `Order` per shop). Each shop's subtotal independently qualifies for free shipping or pays the flat fee.
- **`feeForSubtotal($subtotal)`** — the core rule: `subtotal >= free_threshold ? 0 : flat_fee`. Used by `OrderService` when creating each order.
- **`breakdownForItems($items)`** — groups a cart/order item collection into one row per shop (`shop_id`, `shop_name`, `subtotal`, `shipping_fee`), skipping soft-deleted products (null `product` relation). Used by `CheckoutController::index` (per-shop display) and `CartService::calculateTotals` (aggregate). This is the **only** place that knows the per-shop grouping rule — don't re-implement it in controllers.
- **`publicConfig()`** — the rule as a plain array (`flat_fee`, `free_threshold`) shipped to the front-end (`shippingConfig` prop) so the cart/checkout pages can **estimate** fees client-side. The **back-end is the source of truth**; the client value is display-only and never trusted when creating orders.
