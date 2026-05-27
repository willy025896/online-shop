# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## AI 操作規範

**每次執行任何開發任務前，必須先讀取 `.claude/AI-RULES.md`**，並遵守其中定義的記錄規範（tasks、decisions）。

---

## Project Overview

**Online Shop** — Multi-vendor e-commerce platform with buyer/seller/admin roles

### Stack

- **Laravel 12** (PHP 8.3+) — backend framework
- **Inertia.js v1** — bridges Laravel and Vue without a separate API (except for specific endpoints)
- **Vue 3** (Composition API with `<script setup>`) — frontend
- **Laravel Jetstream 5** — authentication scaffolding (Sanctum, 2FA, profile management, API tokens, teams)
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
- `userRole` — current user's role (`customer` / `seller` / `admin`)
- `flash` — flash messages (`success`, `error`)

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

Usage: `Shop::STATUS_APPROVED`, `User::ROLE_SELLER`, etc.

### Cart

Cart supports both guests and authenticated users. `CartService` identifies a cart by `user_id` for authenticated users and `session_id` for guests. On login, `CartService::mergeGuestCart()` merges the guest cart into the user's cart. Policies in `app/Policies/` govern seller/admin resource authorization.

### Adding New Pages

1. Add a route in `routes/web.php` returning `Inertia::render('PageName')`.
2. Create `resources/js/Pages/PageName.vue` using `AppLayout` as the wrapper.
3. Add `NavLink` entries in `resources/js/Layouts/AppLayout.vue` (both desktop and responsive sections).
4. Create `lang/en/page-name.php` and `lang/zh_TW/page-name.php` for page strings.

### Component Aliases

`@/` is aliased to `resources/js/` (configured via `laravel-vite-plugin`). Use `@/Components/...`, `@/Layouts/...`, `@/Pages/...`.

```
online-shop/
├── .claude/                    # AI 操作規範、task/decision 記錄、implementation records
├── app/
│   ├── Http/
│   │   ├── Controllers/        # 8 public + 2 utility + 6 seller + 6 admin = 22 controllers
│   │   └── Middleware/         # EnsureRole, SetLocale, HandleInertiaRequests
│   ├── Policies/               # 3 policies (Product, Order, Shop)
│   ├── Models/                 # 12 models (User, Shop, Product, Order, OrderCancellation, ...)
│   └── Services/               # 3 services (Cart, Order, Payment)
├── database/
│   └── migrations/             # 14 migrations
├── lang/
│   ├── en/                     # English translations
│   └── zh_TW/                  # Traditional Chinese translations
├── resources/js/
│   ├── Components/             # Custom + Jetstream defaults
│   ├── Layouts/                # AppLayout, SellerLayout, AdminLayout
│   └── Pages/                  # Public, Auth, Seller/, Admin/ pages
├── routes/
│   └── web.php                 # All routes (4 groups: public, auth, seller, admin)
└── tests/Feature/              # Pest tests: Product, Shop, Cart, Seller, Admin, Order
```

