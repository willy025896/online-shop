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
npm run dev:full     # Start Vite + Laravel + Reverb in parallel (use this when working on notifications/messaging)

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

### i18n (Localization) System

There is a custom two-part localization approach:

1. **Page-level strings**: `HandleInertiaRequests` reads the current route path, maps it to a lang file (e.g., `/members` → `lang/en/members.php`), and shares the whole array as `$page.props.lang` on every Inertia request.

2. **Component-level strings**: Components that need their own translations fetch them via `GET /api/component-lang/{name}`, handled by `LangController::getComponents()`. This reads from `lang/{locale}/components.php` keyed by component name.

Language files exist for `en` and `zh_TW`. When adding a new page, create matching lang files in both locales.

### Authentication & Jetstream

Auth is handled entirely by Jetstream/Fortify. The middleware group `['auth:sanctum', config('jetstream.auth_session'), 'verified']` protects authenticated routes. The `User` model and Jetstream actions live in `app/Actions/`.

Role-based access uses the `EnsureRole` middleware registered as `role` — e.g. `'role:seller,admin'`. User roles are stored as a string column on `users` and can be `customer`, `seller`, or `admin`.

Locale is set per-request by `SetLocale` middleware (`app/Http/Middleware/SetLocale.php`), which reads `session('locale')` and calls `App::setLocale()`. The `LocaleController` stores the chosen locale into the session.

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

- **Atomicity** — the log insert fires inside the `updated` event, so it only commits atomically with the status change if the `$order->update()` runs inside a `DB::transaction`. All current status-mutating paths (`updateStatus`, `PaymentService::simulatePayment`, `OrderService` cancellation methods) are wrapped in a transaction for this reason. Wrap any new one too.
- **Bulk-update caveat** — Eloquent's `updated` event does **not** fire on query-builder bulk updates (`Order::where(...)->update(['status' => ...])`), so those would silently bypass the log. Always change order status via a model instance (`$order->update(...)`), never a bulk query.

### Notifications

Uses Laravel's built-in `Notifiable` pipeline. Each Notification's `via()` returns `['database', 'broadcast']`:

- **database** — written to the `notifications` table; the `NotificationBell` dropdown and `/notifications` index page read from it via `$request->user()->notifications()` / `unreadNotifications()`.
- **broadcast** — pushed over Reverb to Laravel's default `private-App.Models.User.{id}` channel (authorization is registered in `routes/channels.php`). The front-end `NotificationBell.vue` subscribes via `Echo.private(...).notification(cb)` and prepends new entries without a page reload.

Notification classes live in `app/Notifications/`. Each `toArray()` returns a uniform payload — `{ type, title, body, url, meta }` — so the bell renders any type from a single template.

**Trigger map:**

| Event | Triggered in | Notification | Recipient |
|-------|--------------|--------------|-----------|
| Payment success | `PaymentService::simulatePayment` | `OrderPaidNotification` | Seller |
| Status changes to `paid`/`shipped`/`completed` | `Order::booted()` `updated` event (whitelist `BUYER_NOTIFY_STATUSES`) | `OrderStatusChangedNotification` | Buyer |
| Buyer requests cancellation | `OrderService::requestCancellation` | `OrderCancellationRequestedNotification` | Seller |
| Seller approves/rejects cancellation | `OrderService::approveCancellation` / `rejectCancellation` | `OrderCancellationRespondedNotification` | Buyer |
| Seller directly cancels | `OrderService::cancelBySeller` | `OrderCancelledBySellerNotification` | Buyer |
| Shop `approved`/`suspended` | `Admin\ShopController::updateStatus` | `ShopStatusChangedNotification` | Seller |

`cancelled` is intentionally **excluded** from `Order::BUYER_NOTIFY_STATUSES` — every cancellation path already fires a path-specific notification, so including it would double-notify the buyer (or self-notify when they cancel their own order). If you add a new cancellation path, dispatch the relevant notification explicitly inside the same `DB::transaction`.

Channel auth is in `routes/channels.php`: `App.Models.User.{id}` accepts only the channel owner. Don't add unscoped channels.

The new-message broadcast (`MessageSent` event, `Conversation` chat) is **separate** from the notification pipeline — chat keeps its own `unreadMessageCount` badge and `private-conversation.{id}` channel; don't merge them.

All Notification classes share the `BroadcastsAsArray` trait (`app/Notifications/Concerns/BroadcastsAsArray.php`), which implements `toBroadcast()` as `new BroadcastMessage($this->toArray($notifiable))`. This enforces the project-wide convention that broadcast payload = database payload. New Notification classes must `use BroadcastsAsArray, Queueable;` and must NOT add a custom `toBroadcast()`.

**Review notification trigger map (additions):**

| Event | Triggered in | Notification | Recipient |
|-------|--------------|--------------|-----------|
| Both parties reviewed → cooling starts | `ReviewService::checkAndStartCooling` | `ReviewCoolingStartedNotification` | Buyer + Seller |
| Edit/delete during cooling → cooling reset | `ReviewService::resetCoolingIfActive` | `ReviewCoolingResetNotification` | Counterparty |
| Cooling expires or 14-day timeout → release | `ReviewService::releaseOrder` | `ReviewReleasedNotification` | Buyer + Seller |
| Seller replies to product review | `ReviewService::addSellerReply` | `SellerReplyNotification` | Buyer |

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
│   ├── Http/
│   │   ├── Controllers/        # public + utility + seller + admin (incl. NotificationController, Review controllers)
│   │   └── Middleware/         # EnsureRole, SetLocale, HandleInertiaRequests
│   ├── Notifications/          # 10 Notification classes (Order*, Shop*, Review*); all use BroadcastsAsArray trait
│   │   └── Concerns/           # BroadcastsAsArray trait
│   ├── Policies/               # 4 policies (Product, Order, Shop, ProductReview)
│   ├── Models/                 # 16 models (User, Shop, Product, Order, ProductReview, BuyerReview, WishlistItem, ...)
│   └── Services/               # Cart, Order, Payment, Shipping, Conversation, Review, Wishlist
├── database/
│   └── migrations/             # 23 migrations (incl. product_reviews, buyer_reviews, aggregates, completed_at, wishlist_items)
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
│   ├── console.php             # Scheduled commands (reviews:release every 10 min)
│   └── channels.php            # Broadcast channel authorization (App.Models.User.{id}, conversation.{id})
└── tests/Feature/              # Pest tests: Product, Shop, Cart, Wishlist, Seller, Admin, Order, Notification, Conversation, Review
```

