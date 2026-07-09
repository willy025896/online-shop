# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## AI 操作規範

**每次執行任何開發任務前，必須先讀取 `.claude/AI-RULES.md`**，並遵守其中定義的記錄規範（decisions ADR）與 post-change-review 流程。

---

## Project Overview

**Online Shop** — Multi-vendor e-commerce platform with buyer/seller/admin roles

### Stack

- **Laravel 12** (PHP 8.3+) — backend framework
- **Inertia.js v1** — bridges Laravel and Vue without a separate API (except for specific endpoints)
- **Vue 3** (Composition API with `<script setup>`) — frontend
- **Laravel Jetstream 5** — authentication scaffolding (Sanctum, 2FA, profile management, API tokens, teams)
- **Laravel Reverb 1** + **Laravel Echo** + **pusher-js** — WebSocket broadcasting for real-time messages and notifications
- **Tailwind CSS v3** — styling
- **Pest 3** — PHP testing framework
- **MySQL 8** — database
- **Vite 6** — build tool
- **npm** — package manager

## Commands

```bash
# Install dependencies
composer install
npm install

# Development
npm run dev          # Start Vite dev server (hot reload)
php artisan serve    # Start Laravel dev server
npm run dev:full     # Start Vite + Laravel + Reverb + queue:listen in parallel (use this when working on notifications/messaging)

# Build
npm run build        # Production Vite build

# Testing
php artisan test                              # Run all tests
php artisan test tests/Feature/AuthenticationTest.php  # Run a single test file
./vendor/bin/pest --filter "test name"        # Run a specific test by name

# Code style (Laravel Pint)
./vendor/bin/pint                # Fix all PHP files
./vendor/bin/pint app/           # Fix specific directory

# Database
php artisan migrate
php artisan migrate:fresh --seed
```

### `.env.testing` is required and is NOT copied automatically

`.env` and `.env.testing` are both gitignored (not tracked in git) — `phpunit.xml` sets `APP_ENV=testing`, and Laravel loads `.env.testing` for that environment (`.env.testing` points at a real `online_shop_test` MySQL database; `config/database.php`'s default connection is `env('DB_CONNECTION', 'sqlite')`).

**When creating a new git worktree (or any fresh checkout) to work in this repo, `.env`/`.env.testing` must be manually copied or created before running tests.** `git worktree add` does **not** copy gitignored files from the original checkout. If `.env.testing` is missing, Laravel silently falls back — either to `.env`'s DB (pointing at the real dev database, `online_shop`) or, if `.env` is also missing, to the bare `config/database.php` default (`sqlite`) — instead of failing loudly. Either way tests run against the wrong database without any error. Always verify `.env.testing` exists (and points at `DB_DATABASE=online_shop_test`, not `online_shop`) before running `php artisan test`/`pest` in a new worktree.

## Architecture

### Inertia.js Data Flow

Pages are served by Laravel controllers/routes that call `Inertia::render('PageName', $props)`. Inertia handles the SPA navigation without a REST API for page data. Vue pages live in `resources/js/Pages/`.

**Shared data** is injected into every page via `HandleInertiaRequests::share()` (`app/Http/Middleware/HandleInertiaRequests.php`). Currently shared data includes:
- `lang` — i18n strings for the current page (auto-loaded based on URL path)
- `nav` — navigation strings from `lang/{locale}/navigation.php` (always loaded)
- `locale` — current locale string (`en` or `zh_TW`)
- `cartCount` — current user's cart item count
- `wishlistProductIds` — array of product IDs the auth user has favorited (empty array for guests); the navbar badge derives count via `.length` — no separate `wishlistCount` prop
- `unreadMessageCount` — unread chat-message count for the auth user
- `unreadNotificationCount` — unread notification count (Laravel `notifications` table)
- `recentNotifications` — 10 most recent notifications (for the `NotificationBell` dropdown)
- `notificationBellLang` — i18n bundle from `lang/{locale}/notifications.php` (the bell renders on every page, so its strings ship globally)
- `userRole` — current user's role (`customer` / `seller` / `admin`)
- `flash` — flash messages (`success`, `error`)

All user-scoped props are lazy closures and only computed when Inertia requests them (full load or partial reload).

### Inertia `onSuccess`/`onError` — how it actually decides

Confirmed by reading `node_modules/@inertiajs/core/dist/index.js` (not documented behavior we'd otherwise guess at): Inertia's `visit()` decides `onSuccess` vs `onError` by checking whether the **final rendered page's `page.props.errors` is non-empty** — it does **not** branch on the response's HTTP status code:

```js
.then(() => {
    let r = this.page.props.errors || {};
    if (Object.keys(r).length > 0) return onError(r);
    return onSuccess(page);
})
```

This means a controller returning `back()->withErrors([...])` (a plain 302 redirect, not a thrown `ValidationException`/422) still correctly triggers the front-end's `onError` — because the redirected-to page's `errors` prop (shared by Inertia's base `Middleware::share()`, inherited via `parent::share($request)` in `HandleInertiaRequests`) is populated from `session('errors')`. **`back()->withErrors()` is not a bug or a "silent success" trap** — don't treat it as one during review; only `useForm()` submissions have automatic error rendering (via `form.errors.*`).

The real, recurring gap: any `router.post/patch/delete(...)` call that **isn't** wrapped in `useForm()` must pass its own `onError` — otherwise a `back()->withErrors(...)` failure is silent (no `onSuccess`, but nothing shown either, since nothing reacts to `page.props.errors` outside of a bound `useForm()` instance or an explicit `onError` callback). Project convention: pass `onError: (errors) => toast.error(errors.<field>)` (`useToast()`) on every non-`useForm()` mutation that can fail server-side — see `Admin/Categories/Index.vue`'s `useDeleteConfirmation` usage, `Products/Show.vue`'s `addToCart`, `ImageUploader.vue`'s `uploadImages`.

### i18n (Localization) System

There is a custom two-part localization approach:

1. **Page-level strings**: `HandleInertiaRequests` reads the current route path, maps it to a lang file (e.g., `/members` → `lang/en/members.php`), and shares the whole array as `$page.props.lang` on every Inertia request.

2. **Component-level strings**: Components that need their own translations fetch them via `GET /api/component-lang/{name}`, handled by `LangController::getComponents()`. This reads from `lang/{locale}/components.php` keyed by component name.

Language files exist for `en` and `zh_TW`. When adding a new page, create matching lang files in both locales.

### Authentication & Jetstream

Auth is handled entirely by Jetstream/Fortify. The middleware group `['auth:sanctum', config('jetstream.auth_session'), 'verified']` protects authenticated routes. The `User` model and Jetstream actions live in `app/Actions/`.

Role-based access uses the `EnsureRole` middleware registered as `role` — e.g. `'role:seller,admin'`. User roles are stored as a string column on `users` and can be `customer`, `seller`, or `admin`.

Locale is set per-request by `SetLocale` middleware (`app/Http/Middleware/SetLocale.php`), which reads `session('locale')` — falling back to the authenticated user's persisted `users.locale` column (e.g. a fresh session on a new device) before the app default — and calls `App::setLocale()`, writing the resolved value back into the session when it changed. `LocaleController::store()` persists the chosen locale into both the session and (for authenticated users) `users.locale`; `CreateNewUser` seeds `locale` from `app()->getLocale()` at registration. `users.locale` is also what `User::preferredLocale()` (`HasLocalePreference`) reads for queued notifications — see ADR-016. The set of selectable locales (`en`/`zh_TW`) is centralized in `config('app.supported_locales')` — both `SetLocale` and `LocaleController`'s validation rule read from it, so adding a locale is a one-line config change, not two hand-kept lists.

### Model Constants

Status strings and role values are defined as public constants on their respective models — **never use raw strings**. Always reference the constant so that typos cause a compile-time error rather than a silent bug.

| Model | Constants |
|-------|-----------|
| `User` | `ROLE_CUSTOMER`, `ROLE_SELLER`, `ROLE_ADMIN` |
| `Shop` | `STATUS_PENDING`, `STATUS_APPROVED`, `STATUS_SUSPENDED` |
| `Product` | `STATUS_DRAFT`, `STATUS_ACTIVE`, `STATUS_INACTIVE` |
| `Order` | `STATUS_PENDING`, `STATUS_PAID`, `STATUS_PROCESSING`, `STATUS_SHIPPED`, `STATUS_COMPLETED`, `STATUS_CANCELLED` |
| `OrderCancellation` | `STATUS_REQUESTED`, `STATUS_APPROVED`, `STATUS_REJECTED`, `INITIATED_BY_BUYER`, `INITIATED_BY_SELLER` |
| `ProductReview` | `STATUS_PUBLISHED`, `STATUS_HIDDEN` |
| `BuyerReview` | `STATUS_PUBLISHED`, `STATUS_HIDDEN` |

Usage: `Shop::STATUS_APPROVED`, `User::ROLE_SELLER`, etc.

### Loading States (Skeleton)

All list/dashboard pages that do an Inertia partial reload (filter change, pagination, period switch) show a skeleton placeholder instead of a blank/frozen screen during the request. Shared components: `Skeleton.vue` (base pulsing block), `ProductCardSkeleton.vue`, `StatCardSkeleton.vue`, `TableSkeletonRows.vue` (row count driven by the actual per-page item count, not a hard-coded number).

Key design point — **per-request `onStart`/`onFinish`, never a global listener**: each page tracks its own `isLoading` ref, toggled by the `onStart`/`onFinish` callbacks of the specific `router.get(...)` call (or `@start`/`@finish` on `<Pagination>` / `<Link>`) that triggers that page's reload. An earlier version used a global `router.on('start'/'finish', ...)` listener, which fired on *any* Inertia request on the page — including unrelated ones like toggling a wishlist heart, marking a notification read, or saving a dashboard widget preference — incorrectly replacing the whole page with skeletons. When adding skeleton loading to a new list page, always scope the loading flag to that page's own reload trigger.

All skeleton components carry `aria-hidden="true"` (or `role="status" aria-busy="true"` on the wrapping container) so screen readers don't announce placeholder content.

### Wishlist (收藏 / 願望清單)

`WishlistService` (`app/Services/WishlistService.php`) is the single entry point for wishlist mutations. All operations are scoped to `Auth::id()`.

**Wishlist state** is stored in the `wishlist_items` pivot table with `unique(user_id, product_id)`.

Key design points:
- **Auth-only** — wishlist routes are inside the auth middleware group; guests clicking the heart icon are redirected to login, not silently ignored.
- **`toggle(Product)`** — uses `firstOrCreate` on the add path to avoid a `QueryException` on concurrent/double-clicked requests.
- **`remove(Product)`** — a dedicated remove method used by `WishlistController::destroy()`; never uses `toggle()` to avoid the semantic bug where DELETE on an un-favorited product would add it.
- **`getItemsWithProducts()`** — returns products via the `User::favoritedProducts()` `BelongsToMany` relation, ordered by `pivot.created_at DESC` (most-recently favorited first).
- **`wishlistProductIds` shared prop** — shipped on every authenticated request as a lazy closure; `FavoriteButton` reads it to determine fill state. The navbar badge derives count via `.length` — no separate count query.
- **`FavoriteButton.vue`** uses Inertia partial reload (`only: ['wishlistProductIds', 'flash']`) so toggling a heart reloads only those two props, not the full page.
- Adding a wishlist item to cart (`cart.store`) does **not** remove it from the wishlist — the two are independent.

### Product / Shop Listing Filters

Both `ProductController::index` and `ShopController::show` apply optional query-string filters to their product listings. Two conventions apply everywhere:

- **`$request->filled()`** — use this to test "present and not empty-string" for optional filters. Never use `!== null && !== ''` manually.
- **Partial reloads** — filter/sort navigations use `only: ['products', 'filters']` so the server skips the category query on every keystroke. The `categories` prop in both controllers is a **lazy closure** (`fn() => ...`) so Inertia's partial-reload mechanism skips evaluating it entirely when it is not in the `only` list.

Filters currently supported on `/products`:
`search` (full-text), `category`, `min_rating`, `min_price`, `max_price`, `sort`

Filters currently supported on `/shops/{shop}`:
`search` (LIKE), `category`, `min_price`, `max_price`, `sort`

### Cart

Cart supports both guests and authenticated users. `CartService` identifies a cart by `user_id` for authenticated users and `session_id` for guests. On login, `CartService::mergeGuestCart()` merges the guest cart into the user's cart. Policies in `app/Policies/` govern seller/admin resource authorization.

### Shipping

`ShippingService` (`app/Services/ShippingService.php`) is the single source of truth for shipping fees. The rule is a **flat fee with a free-shipping threshold**, configured globally in `config/shipping.php` (`flat_fee`, `free_threshold`; both overridable via `SHIPPING_FLAT_FEE` / `SHIPPING_FREE_THRESHOLD` env vars). Set `free_threshold` to `null` to disable free shipping.

Key design points:
- **Per-shop calculation** — shipping is evaluated **per shop**, mirroring the per-shop order split in `OrderService::createOrdersFromCart` (one `Order` per shop). Each shop's subtotal independently qualifies for free shipping or pays the flat fee.
- **`feeForSubtotal($subtotal)`** — the core rule: `subtotal >= free_threshold ? 0 : flat_fee`. Used by `OrderService` when creating each order.
- **`breakdownForItems($items)`** — groups a cart/order item collection into one row per shop (`shop_id`, `shop_name`, `subtotal`, `shipping_fee`), skipping soft-deleted products (null `product` relation). Used by `CheckoutController::index` (per-shop display) and `CartService::calculateTotals` (aggregate). This is the **only** place that knows the per-shop grouping rule — don't re-implement it in controllers.
- **`publicConfig()`** — the rule as a plain array (`flat_fee`, `free_threshold`) shipped to the front-end (`shippingConfig` prop) so the cart/checkout pages can **estimate** fees client-side. The **back-end is the source of truth**; the client value is display-only and never trusted when creating orders.

### Coupons (折扣碼)

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

### Dashboard Analytics (Seller + Admin)

Both `Seller\DashboardController` and `Admin\DashboardController` render period-scoped analytics. The period-window logic is **not duplicated** — it lives in the `ResolvesDashboardPeriod` trait (`app/Http/Controllers/Concerns/ResolvesDashboardPeriod.php`), the single source of truth for both dashboards. Don't re-implement period math in a controller; `use` the trait.

Key design points:
- **Periods** — `today` / `week` / `month` / `all`, resolved via `resolvePeriod($request)` (invalid input falls back to `month`). `periodRange()` / `prevPeriodRange()` return the current and preceding windows; `'all'` has no comparison window (returns `[null, null]`), so growth is null for `'all'`.
- **Timestamp convention** — **revenue is keyed on `paid_at`** (when payment cleared); **order-activity counts are keyed on `created_at`** (when the order was placed). The trait owns only the period math, not the choice of column — each query picks the right timestamp.
- **`periodGrowth($current, $prev)`** — percent change vs the previous period (1dp); returns `null` when there is no baseline and `100.0` when growing from zero.
- **`dailyRevenueSeries($base, $period, $start, $end)`** — zero-filled daily revenue buckets for the line chart. `$base` is a **fresh** `Order` query already scoped to the caller (`Order::query()` for admin, `Order::where('shop_id', ...)` for a seller); the method mutates the builder, so never pass one you intend to reuse. For `'all'` it shows the last 30 days.
- **Scope difference** — the seller dashboard scopes every query to its own shop; the admin dashboard is platform-wide (all shops) and adds a **Top Shops by revenue** table (the platform analog of the seller's top-products). Admin also keeps period-independent all-time totals (users/shops/products/orders/revenue) as a header row.
- **Front-end** — the `PeriodTabs` component (`resources/js/Components/Dashboard/PeriodTabs.vue`) renders the shared period selector for both pages; each page keeps its own `setPeriod` because their partial-reload `only:` lists differ. Charts/cards reuse `RevenueLineChart`, `StatCard`, `OrderStatusGrid`. Period navigations use `only: [...]` partial reloads (same convention as listing filters).

### Low Stock Alert

`Product::scopeLowStock($query, ?int $threshold = null)` is the **single source of truth** for "what counts as low stock" — `stock <= threshold` (includes out-of-stock at 0), defaulting to `config('inventory.low_stock_threshold')`. The threshold lives in `config/inventory.php` (overridable via `INVENTORY_LOW_STOCK_THRESHOLD` env), same config-driven pattern as Shipping. Don't hard-code a stock cutoff anywhere — call the scope.

Surfaced in two places, both scoped to the seller's own shop:
- **Seller dashboard** — a toggleable `low_stock` widget (part of `DEFAULT_WIDGETS`; whitelist it in `PreferenceController` when adding widget keys) showing a count badge + the 5 lowest-stock products, rendered by `LowStockAlert.vue`. It is **period-independent** (current inventory, not a time window), so its data is not in the period `only:` partial-reload list — but `low_stock_count` rides inside the `stats` prop which *is* reloaded, and stays correct because the controller recomputes it every request.
- **Products list** — a `low_stock` boolean filter (`ProductController::index`, tested with `$request->boolean()` since it is a flag, not a value filter) plus an amber/red stock badge on each row.

### Order Status Transitions & Logging

Seller status changes go through `Seller\OrderController::updateStatus`, guarded by `Order::canTransitionStatusTo()`. The rule is **forward-only** (by the `Order::STATUS_RANK` map) and rejects terminal sources (`completed`/`cancelled`) and orders with a pending cancellation — but it deliberately allows legitimate skips such as `pending → shipped` (cash-on-delivery) and `paid → completed` (virtual goods). Invalid transitions `abort(422)`.

**Every status change is logged** to the `order_status_logs` table. Logging is **event-based**: the `Order` model's `updated` event (registered in `Order::booted()`) writes a row whenever `status` changes, capturing `from_status` / `to_status` / `changed_by`. This auto-captures all paths (payment, seller update, cancellation finalize) without per-call-site code.

Two consequences to keep in mind when changing order status:

- **Atomicity** — the log insert fires inside the `updated` event, so it only commits atomically with the status change if the `$order->update()` runs inside a `DB::transaction`. All current status-mutating paths (`updateStatus`, `PaymentService::markAsPaid`, `OrderService` cancellation methods) are wrapped in a transaction for this reason. Wrap any new one too.
- **Bulk-update caveat** — Eloquent's `updated` event does **not** fire on query-builder bulk updates (`Order::where(...)->update(['status' => ...])`), so those would silently bypass the log. Always change order status via a model instance (`$order->update(...)`), never a bulk query.

### Payment Gateway (ECPay 綠界)

`PaymentService` (`app/Services/PaymentService.php`) orchestrates payment/refund; all ECPay wire-format details (CheckMacValue signing/verification, HTTP calls) live in `EcpayGateway` (`app/Services/EcpayGateway.php`) — callers never touch raw ECPay params. Credentials/mode are config-driven (`config/ecpay.php`, `ECPAY_*` env vars), defaulting to ECPay's officially published stage/test merchant credentials so local dev works with zero setup. See ADR-015.

Key design points:
- **Notify webhook is the only source of truth for "paid"** — the buyer's "Pay" button (`OrderController::pay`) redirects to an auto-submitting form (`resources/views/payments/ecpay-redirect.blade.php`) that POSTs straight to ECPay's hosted checkout page; it does **not** mark the order paid. Only ECPay's server-to-server notify callback (`POST /api/payments/ecpay/notify` → `EcpayController::notify` → `PaymentService::handleGatewayNotification()`), after verifying `CheckMacValue` and `RtnCode == 1`, calls `PaymentService::markAsPaid()`. The notify route lives on `routes/api.php` (no CSRF, no session auth) since it's ECPay's server calling it, not a logged-in user.
- **`pay()` only accepts a still-`STATUS_PENDING` order** (not just "not yet paid") — otherwise a cancelled/completed order could still generate a fresh, validly-signed checkout session.
- **Idempotent + status-checked notify, under a row lock** — `handleGatewayNotification()` locks the order (`Order::lockForUpdate()`) before deciding: `isPaid()` already true is a no-op success (ECPay retries until it gets `"1|OK"`, so replays must not double-notify); if the order is no longer `STATUS_PENDING` (e.g. the buyer cancelled it while payment was in flight at ECPay), it does **not** resurrect the order to `paid` — it captures the `TradeNo` this notify carries and immediately refunds the full charged amount instead, since nothing was fulfilled.
- **`MerchantTradeNo` is derived from the order id**, not `order_number` (ECPay requires alnum-only, ≤20 chars) — `EcpayGateway::merchantTradeNoFor()` / `tradeNoToOrderId()` are the only two places that encode/decode this mapping.
- **`orders.gateway_trade_no`** stores ECPay's own `TradeNo` (captured from the notify payload), distinct from our `MerchantTradeNo`/`order_number`. It's required to issue a refund — `EcpayGateway::refund()` throws `EcpayException` if it's missing.
- **Refund failures roll back the whole transaction** — `EcpayGateway::refund()` throws `EcpayException` on any network failure or non-`1` `RtnCode`. Both refund call sites (`OrderService::finalizeReturn`, `finalizeCancellation`) call `PaymentService::refund()` as the **last statement** in their transaction (so nothing afterward can fail and roll back a refund ECPay already sent), from inside their existing `DB::transaction` — a gateway failure rolls back stock/coupon/status changes together, leaving the return/cancellation request retryable rather than half-applied. `EcpayException` defines `render()` (mirroring `CouponException`'s `translatedMessage()` via `lang/{locale}/orders.php` → `payment_errors.{reason}`), so Laravel auto-flashes a translated error — controllers don't need their own try/catch.
- **`PaymentService::refund()` rounds once** (ECPay only accepts whole-TWD amounts) and uses that same rounded figure for both the gateway call and the local `refunded_amount` increment, so the internal ledger never drifts from what ECPay actually refunded.
- **CheckMacValue is hand-implemented** (no third-party SDK dependency, matching this project's zero-dependency convention) — `EcpayGateway::generateCheckMacValue()` mirrors ECPay's own official SDK algorithm: case-insensitive key sort (`uksort`+`strcasecmp`, not a plain `ksort`), `urlencode` + lowercase + the documented PHP→.NET character fixups, SHA256, uppercase.

### Electronic Invoice (電子發票, B2C)

> ⚠️ **Core path implemented only — no Pest test coverage and no post-change-review yet.** Treat this section as accurate for how the code is wired, but not yet verified. See ADR-019.

`InvoiceService` (`app/Services/InvoiceService.php`) decides *whether* an order's e-invoice state should change; `EcpayInvoiceGateway` (`app/Services/EcpayInvoiceGateway.php`) is the wire-format layer (AES-128-CBC encryption, ECPay's B2C invoice HTTP calls). This is a **separate integration from the payment gateway above** — different credentials (`config/ecpay_invoice.php`, `ECPAY_INVOICE_*` env vars) and a different crypto scheme (AES over the whole JSON payload, not a SHA256 `CheckMacValue`). See ADR-019 for the full design rationale, including why 字軌/配號 (invoice number track allocation) is an ECPay-backend administrative setup step, not something this codebase decides.

Key design points:
- **Issued on payment confirmation** — `PaymentService::markAsPaid()` calls `InvoiceService::issueForOrder()` in the same transaction as `OrderPaidNotification`, since that's currently the only "payment received" moment in the system (no real cash-on-delivery path exists yet).
- **Cancellation voids or allowances depending on the invoice's age** — `OrderService::finalizeCancellation()` calls `InvoiceService::voidForOrder()` if the invoice was issued in the current calendar month, otherwise `InvoiceService::allowanceForOrder()` (a deliberately simplified stand-in for ECPay's actual reporting-cutoff rule — this is a side project, not a real business, so the exact statutory boundary wasn't chased down; see ADR-019).
- **Returns always allowance, never void** — `OrderService::finalizeReturn()` always calls `InvoiceService::allowanceForOrder()` with the returned items' amount, regardless of month.
- **Invoice failures are best-effort and must never roll back a real payment/refund** — every `InvoiceService` call site (`PaymentService::markAsPaid`, `OrderService::finalizeCancellation`/`finalizeReturn`) wraps the call in `try/catch(\Throwable)` and only `Log::warning`s on failure. This is the opposite of `PaymentService::refund()`'s "throw and roll back the whole transaction" policy — deliberately so, because by the time these calls run, ECPay has already moved real money; rolling back the transaction would desync our DB state from ECPay's actual state, which is worse than a manually-fixable missing invoice.
- **`orders.invoice_status`** (`Order::INVOICE_ISSUED` / `INVOICE_VOIDED` / `INVOICE_ALLOWANCED`, `null` = not yet issued) is what every `InvoiceService` method checks before acting, making all three methods idempotent — callers never need their own guard.
- **`EinvoiceException`** (`app/Exceptions/EinvoiceException.php`) carries a machine `reason`, mirroring `EcpayException`'s shape, but does **not** define `render()` — unlike `EcpayException`, it's always caught internally by `InvoiceService`'s callers and never bubbles up to a controller.

### Order Returns/Refunds (售後退貨/退款)

`OrderService` owns return mutations (`requestReturn`, `approveReturn`, `rejectReturn`, private `finalizeReturn`) — same single-entry-point, lock+idempotent-guard convention as cancellations. See ADR-013.

Key design points:
- **Window** — a buyer may request a return within `config('returns.window_days')` (default 7, overridable via `ORDER_RETURN_WINDOW_DAYS`) days after `completed_at`. `Order::canRequestReturn()`.
- **One pending return at a time** — `Order::pendingReturn()` gates new requests; since only one return can be pending at once, `pendingReturn()` uses the already eager-loaded `latestReturn` relation (`hasOne(...)->latestOfMany()`) as a fast path instead of firing an extra query when it's loaded, falling back to a fresh query otherwise.
- **Partial + repeated returns over time** — `OrderReturn`/`OrderReturnItem` (one row per returned line item per request). `OrderItem::returnedQuantity()` / `remainingReturnableQuantity()` sum only **approved** return items, so a line item can be returned across several separate approved requests. `Order::isFullyReturned()` checks every item has caught up to its ordered quantity.
- **Refund math lives in `CouponService`** — `CouponService::refundableAmount($order, $itemsSubtotal)` applies the same discount ratio used at checkout (`discount / subtotal`) to the returned items' subtotal (proportional deduction); shipping is never refunded. Coupon usage (`used_count` / `CouponRedemption`) is only released via `releaseForOrder()` once `isFullyReturned()` — a partial return does not free up the buyer's coupon allowance.
- **Stock restock is shared** — `OrderService::restockOrderItem()` (used by both `finalizeCancellation` and `finalizeReturn`) resolves the stock owner (variant or plain product) with `withTrashed()` so a since-removed listing still gets its stock restored.
- **`orders.refunded_amount`** — a denormalized running total, incremented via `PaymentService::refund()` (calls ECPay's real credit card refund API — see the Payment Gateway section above; throws and rolls back on failure). Refunding does **not** change `orders.status` — a refunded order stays `completed`.
- **Duplicate-row validation guard** — `OrderController::requestReturn`'s cross-field validator groups requested quantities **by `order_item_id`** before comparing against the remaining returnable quantity, so splitting one line item across two request rows can't bypass the per-item cap; `order_return_items` also has a DB-level `unique(order_return_id, order_item_id)` as a second line of defense.
- **Cancelling an already-paid order now refunds too** (closes the gap ADR-013 originally left open) — `OrderService::finalizeCancellation()` calls `PaymentService::refund()` for the full goods amount (net of any coupon discount, shipping excluded) whenever `$order->isPaid()`, before the status flips to `cancelled`. See ADR-015.

### Notifications

Uses Laravel's built-in `Notifiable` pipeline. Each Notification's `via()` returns `['database', 'broadcast']`, plus `'mail'` for the 9 order-lifecycle-critical events listed below (see ADR-016):

- **database** — written to the `notifications` table; the `NotificationBell` dropdown and `/notifications` index page read from it via `$request->user()->notifications()` / `unreadNotifications()`.
- **broadcast** — pushed over Reverb to Laravel's default `private-App.Models.User.{id}` channel (authorization is registered in `routes/channels.php`). The front-end `NotificationBell.vue` subscribes via `Echo.private(...).notification(cb)` and prepends new entries without a page reload.
- **mail** — via `toMail()` from the `MailsAsArray` trait (mirrors `BroadcastsAsArray`, see below). Only added to notifications representing events a recipient shouldn't miss while logged out: `OrderPaidNotification`, `OrderStatusChangedNotification`, `OrderCancellationRequestedNotification`, `OrderCancellationRespondedNotification`, `OrderCancelledBySellerNotification`, `OrderReturnRequestedNotification`, `OrderReturnRespondedNotification`, `PayoutCompletedNotification`, `ShopStatusChangedNotification`. Chat (`NewMessageNotification`) and review-flow notifications deliberately stay database+broadcast only, to avoid mail spam. `MAIL_MAILER=log` by default — no real SMTP is configured out of the box (see ADR-016).

These same 9 classes `implements ShouldQueue` — required so the mail send actually happens asynchronously on the queue rather than blocking the request/webhook thread that triggered it. Several dispatch sites hold a `DB::transaction` + `lockForUpdate()` while calling `->notify()` (`PaymentService::markAsPaid`, `OrderService`'s cancellation/return methods, `Order::booted()`'s `updated` event, `PayoutService::generateForShop`); without `ShouldQueue`, a slow/failed mail send would extend the row lock and could roll back an already-correct business transaction over a transient SMTP hiccup. This means local dev needs a running queue worker for these 9 to actually deliver — `npm run dev:full` starts `php artisan queue:listen` alongside Vite/Reverb for this reason. The other 5 classes (chat, review-flow) are **not** `ShouldQueue` — they're cheap local writes (no external I/O) and, for `NewMessageNotification` specifically, need to land synchronously alongside the immediate chat broadcast.

Notification classes live in `app/Notifications/`. Each `toArray()` returns a uniform payload — `{ type, title, body, url, meta }` — so the bell renders any type from a single template.

**Trigger map:**

| Event | Triggered in | Notification | Recipient |
|-------|--------------|--------------|-----------|
| Payment success | `PaymentService::markAsPaid` (called from `EcpayController::notify` after signature verification) | `OrderPaidNotification` | Seller |
| Status changes to `paid`/`shipped`/`completed` | `Order::booted()` `updated` event (whitelist `BUYER_NOTIFY_STATUSES`) | `OrderStatusChangedNotification` | Buyer |
| Buyer requests cancellation | `OrderService::requestCancellation` | `OrderCancellationRequestedNotification` | Seller |
| Seller approves/rejects cancellation | `OrderService::approveCancellation` / `rejectCancellation` | `OrderCancellationRespondedNotification` | Buyer |
| Seller directly cancels | `OrderService::cancelBySeller` | `OrderCancelledBySellerNotification` | Buyer |
| Shop `approved`/`suspended` | `Admin\ShopController::updateStatus` | `ShopStatusChangedNotification` | Seller |
| New chat message (order chat or product Q&A) | `ConversationService::sendMessage` | `NewMessageNotification` | The other participant |
| Buyer requests a return | `OrderService::requestReturn` | `OrderReturnRequestedNotification` | Seller |
| Seller approves/rejects a return | `OrderService::approveReturn` / `rejectReturn` | `OrderReturnRespondedNotification` | Buyer |
| Payout generated | `PayoutService::generateForShop` | `PayoutCompletedNotification` | Seller |

`cancelled` is intentionally **excluded** from `Order::BUYER_NOTIFY_STATUSES` — every cancellation path already fires a path-specific notification, so including it would double-notify the buyer (or self-notify when they cancel their own order). If you add a new cancellation path, dispatch the relevant notification explicitly inside the same `DB::transaction`.

Channel auth is in `routes/channels.php`: `App.Models.User.{id}` accepts only the channel owner. Don't add unscoped channels.

The `MessageSent` event (`Conversation` chat) is a **separate broadcast channel** (`private-conversation.{id}`) from the notification pipeline's `App.Models.User.{id}` channel — chat keeps its own `unreadMessageCount` badge and real-time bubble rendering via `MessageSent`; don't merge the two channels. They are not mutually exclusive, though: `ConversationService::sendMessage()` fires **both** — `broadcast(new MessageSent($message))->toOthers()` for the open chat thread, **and** `NewMessageNotification` (database + bell) so the recipient is told about a new message even when they aren't on the Messages page.

All Notification classes share the `BroadcastsAsArray` trait (`app/Notifications/Concerns/BroadcastsAsArray.php`), which implements `toBroadcast()` as `new BroadcastMessage($this->toArray($notifiable))`. This enforces the project-wide convention that broadcast payload = database payload. New Notification classes must `use BroadcastsAsArray, Queueable;` and must NOT add a custom `toBroadcast()`. The 9 mail-enabled classes additionally `use MailsAsArray;` (`app/Notifications/Concerns/MailsAsArray.php`), which implements **both** `toMail()` (built entirely from `toArray()`'s `title`/`body`/`url`) **and** `via()` (`['database', 'broadcast', 'mail']` — since none of the 9 classes vary this per-notifiable, it lives once in the trait rather than being copy-pasted into each class). Adding mail to a new event is therefore just `use MailsAsArray;` + `implements ShouldQueue` — no custom `toMail()` or `via()` needed. Any class NOT using `MailsAsArray` must still declare its own `via()` (typically `['database', 'broadcast']`).

**Locale for queued sends** — the 9 mail-enabled notifications are genuinely queued (`QUEUE_CONNECTION=database`, `ShouldQueue`), so by the time a queue worker renders one, the HTTP session that triggered it is long gone. `User implements HasLocalePreference` (`preferredLocale()` returns the `users.locale` column, persisted by `LocaleController::store()` whenever an authenticated user switches language, and seeded at registration by `CreateNewUser`) — Laravel's `NotificationSender::sendNow()` automatically wraps the entire send (`toArray()`/`toBroadcast()`/`toMail()`) in the recipient's preferred locale when this contract is implemented. This is a general fix, not mail-specific: it also corrects the same latent locale bug for the database/broadcast channels on these 9 classes. See ADR-016.

**Review notification trigger map (additions):**

| Event | Triggered in | Notification | Recipient |
|-------|--------------|--------------|-----------|
| Both parties reviewed → cooling starts | `ReviewService::checkAndStartCooling` | `ReviewCoolingStartedNotification` | Buyer + Seller |
| Edit/delete during cooling → cooling reset | `ReviewService::resetCoolingIfActive` | `ReviewCoolingResetNotification` | Counterparty |
| Cooling expires or 14-day timeout → release | `ReviewService::releaseOrder` | `ReviewReleasedNotification` | Buyer + Seller |
| Seller replies to product review | `ReviewService::addSellerReply` | `SellerReplyNotification` | Buyer |

### Conversations (Chat) & Product Q&A (商品問答)

`ConversationService` (`app/Services/ConversationService.php`) is the single entry point for creating conversations and sending messages; `ConversationController` / `ConversationPolicy` cover authorization (a `Conversation` is only visible to its `buyer_id` / `seller_user_id`).

A `Conversation` is created one of two ways:

- **Order chat** — `getOrCreateForOrder(Order $order)`. `order_id` is set and **unique** (one conversation per order).
- **Product Q&A (pre-purchase, no order needed)** — `getOrCreateForProduct(Product $product, User $buyer)`. `order_id` is **nullable**; a Q&A conversation has `order_id = null`. One buyer asking one seller about *any* of that seller's products reuses the **same** `order_id IS NULL` conversation (found via `firstOrCreate(['buyer_id', 'seller_user_id', 'order_id' => null])`) — product differences are expressed at the **message** level (see below), not by opening a new conversation per product. See ADR-010.

Key design points:

- **`messages.product_id`** (nullable, `nullOnDelete`) attaches a product "card" to a message — thumbnail + name + price, clickable through to the product page. `ConversationService::sendMessage()` accepts an optional `?Product $product`; a message is valid if it has `body`, `image`, **or** `product` (at least one). Clicking "Ask Seller" on a product page (`POST /products/{product:slug}/ask` → `ConversationController::askAboutProduct`) gets/creates the Q&A conversation and immediately sends a `product`-only message (no body), then redirects to `messages.show`. A seller cannot ask about their own product (`abort_if($product->shop->user_id === auth()->id(), 403)`).
- **Soft-deleted products render as unavailable, not an error** — `Product` uses `SoftDeletes`, so its global scope makes `Message::product()` resolve to `null` for a since-removed product even though `messages.product_id` is still set. The front-end keys off `message.product_id` (always present) to decide "was this a product card message", and separately checks `message.product` (nullable) to render either the card or a "product no longer available" fallback (`ProductInquiryCard.vue`).
- **Shop name is sourced from the seller, not the order** — `$conversation->seller->shop->name` (via `Conversation::seller()` → `User::shop()`), not `$conversation->order->shop`, since Q&A conversations have no order. `order` in the front-end payload only carries order-specific fields (`order_number`/`status`/`total`); `OrderCardBanner.vue` takes `shop-name` as a separate prop and only renders `v-if="conversation.order"`.
- **No DB-level dedup for Q&A conversations** — unlike `order_id` (unique, NOT NULL originally), there's no unique index preventing two `order_id IS NULL` rows for the same buyer/seller pair (MySQL can't express a partial unique index — same limitation noted for coupon codes in ADR-008). `firstOrCreate` covers the normal case; a duplicate under concurrent double-click is a cosmetic risk, not a data-integrity one.
- **Every message notifies the other participant** — `NewMessageNotification` (see Notifications trigger map above) fires for both order chat and product Q&A, since chat previously had no bell/database notification at all (only the in-thread real-time bubble and the navbar unread badge).

### Review System (雙向盲評)

`ReviewService` (`app/Services/ReviewService.php`) is the single entry point for all review mutations. **All methods wrap in `DB::transaction` with `Order::lockForUpdate()`** to prevent race conditions.

**Review state** is tracked on the `orders` table via three columns:
- `completed_at` — set automatically by `Order::booted() updating` hook when `status → completed`
- `review_cooling_until` — set when both parties submit; cleared if either edits/deletes during cooling
- `review_released_at` — set at release; **NOT NULL = permanently locked, no further writes allowed**

Rule: `isReviewWindowOpen()` = `review_released_at === null`. `isInCoolingPeriod()` = cooling_until set & future & window open.

**Release is handled by `php artisan reviews:release`** (registered in `routes/console.php`, runs every 10 minutes via `Schedule::command(...)->everyTenMinutes()`). It uses `chunkById(100)` to prevent OOM. Two trigger conditions:
1. `review_cooling_until <= now()` — normal path after 24h cooling
2. `completed_at <= now()-14d` — 14-day timeout (fires regardless of whether reviews exist, to prevent long-tail retaliation)

**Aggregate columns** (`reviews_count`, `rating_sum` on `products`/`shops`; `buyer_reviews_count`, `buyer_rating_sum` on `users`) are updated **only at release time** in `updateAggregates()`. Before release, aggregates never change — this preserves the blind-reveal guarantee. `updateAggregates` uses grouped updates (one `UPDATE` per product, one for the shop) rather than N individual UPDATEs.

**Bulk-update caveat (same as order status logging)**: `completed_at` is set by a model `updating` event hook, which does **not** fire on query-builder bulk updates. Any code that bulk-updates `orders.status` to `completed` must also manually set `completed_at`. Use `$order->update(...)` on model instances, never `Order::where(...)->update(...)`.

**`Order::productReviews()` relation** uses `hasManyThrough(ProductReview::class, OrderItem::class)` and is scoped to `STATUS_PUBLISHED`. Do not use this relation when you need all reviews (including hidden) — query `ProductReview` directly.

**Review ownership rules:**
- Buyer can review an `OrderItem` only if `$order->user_id === $user->id && status === COMPLETED && isReviewWindowOpen()`
- Seller can review a Buyer only if `$order->shop_id === $seller->shop->id && status === COMPLETED && isReviewWindowOpen()`
- Both checks are enforced in `ReviewService` (Service layer), not just Policy layer — defense in depth

**PII protection in review responses:**
- Public product page: `->with(['user:id,name,profile_photo_path'])` only — no email/phone/role
- Seller buyer-credit page: `->with(['shop:id,name'])` with explicit `select(...)` — no order shipping data

### Adding New Pages

1. Add a route in `routes/web.php` returning `Inertia::render('PageName')`.
2. Create `resources/js/Pages/PageName.vue` using `AppLayout` as the wrapper.
3. Add `NavLink` entries in `resources/js/Layouts/AppLayout.vue` (both desktop and responsive sections).
4. Create `lang/en/page-name.php` and `lang/zh_TW/page-name.php` for page strings.

### Component Aliases

`@/` is aliased to `resources/js/` (configured via `laravel-vite-plugin`). Use `@/Components/...`, `@/Layouts/...`, `@/Pages/...`.

```
online-shop/
├── .claude/                    # AI 操作規範、implementation records、decisions ADR
├── app/
│   ├── Console/Commands/       # ReleaseReviews
│   ├── Events/                 # MessageSent (chat broadcast)
│   ├── Exceptions/             # CouponException, EcpayException, EinvoiceException (machine-readable `reason`)
│   ├── Http/
│   │   ├── Controllers/        # public + utility + seller + admin (incl. NotificationController, EcpayController, Review controllers)
│   │   └── Middleware/         # EnsureRole, SetLocale, HandleInertiaRequests
│   ├── Notifications/          # 14 Notification classes (Order*, Shop*, Review*, Payout*); all use BroadcastsAsArray trait, 9 also use MailsAsArray (mail channel)
│   │   └── Concerns/           # BroadcastsAsArray, MailsAsArray traits
│   ├── Policies/               # 6 policies (Product, Order, Shop, ProductReview, Coupon, ...)
│   ├── Models/                 # 27 models (User, Shop, Product, Order, OrderReturn, ProductVariant, ProductReview, BuyerReview, WishlistItem, Coupon, Payout, PayoutItem, ...)
│   └── Services/               # Cart, Order, Payment, Ecpay (gateway), EcpayInvoice (gateway), Invoice, Shipping, Coupon, Conversation, Review, Wishlist, ProductVariant, Payout, Recommendation, AdminAuditLogger
├── database/
│   └── migrations/             # 51 migrations (incl. product_reviews, buyer_reviews, aggregates, completed_at, wishlist_items, product_variants, order_returns, payouts, gateway_trade_no, users.locale, einvoice fields)
├── lang/
│   ├── en/                     # English translations (incl. notifications.php, reviews.php, wishlist.php)
│   └── zh_TW/                  # Traditional Chinese translations
├── resources/js/
│   ├── Components/             # Custom (NotificationBell, FavoriteButton, StarRating, ReviewCard, RatingDistribution, ...) + Jetstream defaults
│   ├── Composables/            # useReviewCountdown
│   ├── Layouts/                # AppLayout, SellerLayout, AdminLayout (all mount <NotificationBell />)
│   └── Pages/                  # Public, Auth, Seller/, Admin/, Notifications/, Reviews/, Wishlist/ pages
├── routes/
│   ├── web.php                 # All HTTP routes (4 groups: public, auth, seller, admin)
│   ├── api.php                 # No-CSRF endpoints (component-lang, ECPay payment notify)
│   ├── console.php             # Scheduled commands (reviews:release every 10 min)
│   └── channels.php            # Broadcast channel authorization (App.Models.User.{id}, conversation.{id})
└── tests/Feature/              # Pest tests: Product, Shop, Cart, Wishlist, Seller, Admin, Order, OrderReturn, Coupon, Notification, Conversation, Review
```

