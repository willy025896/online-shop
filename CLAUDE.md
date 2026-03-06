# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

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
- `cartCount` — current user's cart item count
- `userRole` — current user's role (buyer/seller/admin)
- `flash` — flash messages

### i18n (Localization) System

There is a custom two-part localization approach:

1. **Page-level strings**: `HandleInertiaRequests` reads the current route path, maps it to a lang file (e.g., `/members` → `lang/en/members.php`), and shares the whole array as `$page.props.lang` on every Inertia request.

2. **Component-level strings**: Components that need their own translations fetch them via `GET /api/component-lang/{name}`, handled by `LangController::getComponents()`. This reads from `lang/{locale}/components.php` keyed by component name.

Language files exist for `en` and `zh_TW`. When adding a new page, create matching lang files in both locales.

### Authentication & Jetstream

Auth is handled entirely by Jetstream/Fortify. The middleware group `['auth:sanctum', config('jetstream.auth_session'), 'verified']` protects authenticated routes. The `User` model and Jetstream actions live in `app/Actions/`.

### Adding New Pages

1. Add a route in `routes/web.php` returning `Inertia::render('PageName')`.
2. Create `resources/js/Pages/PageName.vue` using `AppLayout` as the wrapper.
3. Add `NavLink` entries in `resources/js/Layouts/AppLayout.vue` (both desktop and responsive sections).
4. Create `lang/en/page-name.php` and `lang/zh_TW/page-name.php` for page strings.

### Component Aliases

`@/` is aliased to `resources/js/` (configured via `laravel-vite-plugin`). Use `@/Components/...`, `@/Layouts/...`, `@/Pages/...`.

```
online-shop/
├── .ai/                        # AI 操作記錄 (prompts, changes, decisions, sessions)
├── app/
│   ├── Http/
│   │   ├── Controllers/        # 7 public + 6 seller + 6 admin = 19 controllers
│   │   ├── Middleware/         # EnsureRole, HandleInertiaRequests
│   │   └── Policies/           # 3 policies (Product, Order, Shop)
│   ├── Models/                 # 8 models + User
│   └── Services/               # 3 services (Cart, Order, Payment)
├── database/
│   └── migrations/             # 14 migrations
├── lang/
│   ├── en/                     # English translations (11 files)
│   └── zh_TW/                  # Traditional Chinese translations (11 files)
├── resources/js/
│   ├── Components/             # 9 custom + Jetstream defaults
│   ├── Layouts/                # 3 layouts (App, Seller, Admin)
│   └── Pages/                  # 9 public pages + Auth; Seller/Admin dirs exist but pages TODO
├── routes/
│   └── web.php                 # All routes (4 groups: public, auth, seller, admin)
└── tests/                      # TODO: Add Pest tests
```

