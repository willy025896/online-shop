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

The real, recurring gap: any `router.post/patch/delete(...)` call that **isn't** wrapped in `useForm()` must pass its own `onError` — otherwise a `back()->withErrors(...)` failure is silent (no `onSuccess`, but nothing shown either, since nothing reacts to `page.props.errors` outside of a bound `useForm()` instance or an explicit `onError` callback). Project convention: pass `onError: (errors) => toast.error(errors.<field>)` (`useToast()`) on every non-`useForm()` mutation that can fail server-side — see `Admin/Categories/Index.vue`'s `useDeleteConfirmation` usage, `Products/Show.vue`'s `addToCart`, `ImageUploader.vue`'s `uploadImages`, `Cart/Index.vue`'s `checkoutSelected`.

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

### Feature Reference Docs

單一功能的實作細節已拆到 `.claude/docs/`，**異動以下功能前必須先讀取對應文件**：

| 功能 | 文件 | 一句話摘要 |
|------|------|-----------|
| 收藏 / 願望清單 | `.claude/docs/wishlist.md` | `WishlistService` 唯一入口；`toggle` 用 `firstOrCreate` 防重複 |
| 商品/店鋪列表篩選 | `.claude/docs/product-shop-filters.md` | `$request->filled()` + partial reload `only:` 慣例 |
| 購物車 | `.claude/docs/cart.md` | `CartService` 依 `user_id`/`session_id` 識別，登入時合併 guest cart |
| 運費 | `.claude/docs/shipping.md` | `ShippingService` 唯一真相來源，per-shop 計算 flat fee + 免運門檻 |
| 優惠券 (折扣碼) | `.claude/docs/coupons.md` | `CouponService` 唯一入口；折扣只套用商品小計，運費不打折 |
| 賣家/平台儀表板分析 | `.claude/docs/dashboard-analytics.md` | `ResolvesDashboardPeriod` trait 為期間邏輯唯一來源 |
| 低庫存警示 | `.claude/docs/low-stock-alert.md` | `Product::scopeLowStock()` 唯一判斷來源，勿硬編庫存門檻 |
| 訂單狀態轉換與紀錄 | `.claude/docs/order-status-logging.md` | 只能用 model instance `update()`，不可 bulk update（會漏 log） |
| 金流 (綠界 ECPay) | `.claude/docs/payment-ecpay.md` | notify webhook 才是「已付款」唯一真相來源；退款失敗要整個 rollback |
| 電子發票 (B2C) | `.claude/docs/einvoice.md` | `InvoiceService` 決定時機，失敗 best-effort，不可 rollback 已付款交易 |
| 售後退貨/退款 | `.claude/docs/order-returns-refunds.md` | `OrderService` 唯一入口，鎖 + 冪等；退款金額算法在 `CouponService` |
| 通知系統 | `.claude/docs/notifications.md` | database+broadcast(+9 類 mail) 統一 payload，新增通知先看 trait 慣例 |
| 聊天室 / 商品問答 | `.claude/docs/conversations-qna.md` | `ConversationService` 唯一入口；Q&A 對話 `order_id` 為 null |
| 雙向盲評 | `.claude/docs/reviews.md` | `ReviewService` 唯一入口，全部包在鎖 transaction；聚合欄位只在 release 時更新 |

### Adding New Pages

1. Add a route in `routes/web.php` returning `Inertia::render('PageName')`.
2. Create `resources/js/Pages/PageName.vue` using `AppLayout` as the wrapper.
3. Add `NavLink` entries in `resources/js/Layouts/AppLayout.vue` (both desktop and responsive sections).
4. Create `lang/en/page-name.php` and `lang/zh_TW/page-name.php` for page strings.

### Component Aliases

`@/` is aliased to `resources/js/` (configured via `laravel-vite-plugin`). Use `@/Components/...`, `@/Layouts/...`, `@/Pages/...`.

```
online-shop/
├── .claude/                    # AI 操作規範、docs/（單一功能參考文件）、records/decisions（ADR）
├── app/
│   ├── Console/Commands/       # ReleaseReviews
│   ├── Events/                 # MessageSent (chat broadcast)
│   ├── Exceptions/             # CouponException, EcpayException, EinvoiceException (machine-readable `reason`)
│   ├── Http/
│   │   ├── Controllers/        # public + utility + seller + admin (incl. NotificationController, EcpayController, Review controllers)
│   │   └── Middleware/         # EnsureRole, SetLocale, HandleInertiaRequests
│   ├── Notifications/          # 16 Notification classes (Order*, Shop*, Review*, Payout*, Wishlist*); all use BroadcastsAsArray trait, 9 also use MailsAsArray (mail channel); the 2 Wishlist* ones are ShouldQueue without mail (transactional rollback-safety, see notifications.md)
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

