# Online Shop

Multi-vendor e-commerce platform with buyer/seller/admin roles, built with Laravel + Inertia.js + Vue.

## Tech Stack

| Category      | Technology        | Version |
|---------------|-------------------|---------|
| Backend       | Laravel           | 12.53.0 |
| PHP           | PHP               | 8.3.13  |
| Frontend      | Vue.js            | 3.4.35  |
| SPA Bridge    | Inertia.js        | 1.2.0   |
| Auth          | Laravel Jetstream | 5.4.0   |
| Auth Token    | Laravel Sanctum   | 4.3.1   |
| Broadcasting  | Laravel Reverb    | 1.10    |
| WebSocket     | Laravel Echo + pusher-js | 2.3 / 8.5 |
| Styling       | Tailwind CSS      | 3.4.7   |
| Build Tool    | Vite              | 6.4.1   |
| Testing       | Pest              | 3.8.5   |
| Database      | MySQL             | 8       |
| Runtime       | Node.js           | 20.18.1 |

## Requirements

- PHP >= 8.3
- Composer
- Node.js >= 20
- MySQL 8

## Installation

```bash
# Clone the repository
git clone <repo-url>
cd online-shop

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed
```

## Development

```bash
# Start Vite dev server (hot reload)
npm run dev

# Start Laravel dev server
php artisan serve

# Start Vite + Laravel + Reverb in parallel (for real-time chat / notifications)
npm run dev:full
```

Visit `http://localhost:8000` in your browser.

### Real-time features (chat & notifications)

Both the in-app chat and the notification bell rely on Laravel Reverb (WebSocket). For these to work locally:

1. Set the Reverb env vars in `.env` (see `.env.example`: `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, plus the matching `VITE_REVERB_*` mirrors)
2. Run `npm run dev:full` (which also starts `php artisan reverb:start`)

Without Reverb, the bell still works via Inertia data — the `database` notification channel keeps writing entries — but new ones won't appear until the next page navigation.

## Build

```bash
npm run build
```

## Testing

Tests use `RefreshDatabase`, which migrates and truncates whatever database `APP_ENV=testing` resolves to. Create a dedicated test database and a `.env.testing` pointing at it — **do not** let tests fall back to your `.env`'s dev database, or every run wipes it.

```bash
# One-time setup: create a test DB and copy .env, then point DB_DATABASE at the test DB
cp .env .env.testing
# edit .env.testing: set APP_ENV=testing and DB_DATABASE to your test database (e.g. online_shop_test)

# Run all tests
php artisan test

# Run a single test file
php artisan test tests/Feature/AuthenticationTest.php

# Run a specific test by name
./vendor/bin/pest --filter "test name"
```

## Code Style

```bash
# Fix all PHP files (Laravel Pint)
./vendor/bin/pint

# Fix specific directory
./vendor/bin/pint app/
```

## Project Structure

```
online-shop/
├── .claude/                    # AI task records, decisions, implementation records
├── app/
│   ├── Console/Commands/       # ReleaseReviews (排程：每 10 分鐘公開到期評論)
│   ├── Events/                 # MessageSent (chat broadcast)
│   ├── Http/
│   │   ├── Controllers/        # public + utility + seller + admin (incl. NotificationController, ReviewControllers)
│   │   └── Middleware/         # EnsureRole, SetLocale, HandleInertiaRequests
│   ├── Notifications/          # Order*, Shop*, Review* (database + broadcast)；共用 BroadcastsAsArray trait
│   ├── Policies/               # Product, Order, Shop, ProductReview
│   ├── Models/                 # 16 models (User, Shop, Product, Order, ProductReview, BuyerReview, WishlistItem, ...)
│   └── Services/               # Cart, Order, Payment, Shipping, Conversation, Review, Wishlist
├── database/
│   └── migrations/             # 23 migrations
├── lang/
│   ├── en/                     # English translations (incl. notifications.php)
│   └── zh_TW/                  # Traditional Chinese translations
├── resources/js/
│   ├── Components/             # Shared Vue components (incl. NotificationBell, FavoriteButton, StarRating, ReviewCard, RatingDistribution)
│   ├── Composables/            # useReviewCountdown
│   ├── Layouts/                # App, Seller, Admin layouts
│   └── Pages/                  # Vue pages organized by feature (incl. Wishlist/, Notifications/, Reviews/, Seller/Reviews/, Seller/Buyers/)
├── routes/
│   ├── web.php                 # HTTP routes (public, auth, seller, admin)
│   └── channels.php            # Broadcast channel authorization
└── tests/                      # Pest tests
```

## Features

- **雙向盲評論系統** - 買家評商品、賣家評買家，24h 冷靜期後自動公開
- **收藏／願望清單** - 登入會員可收藏商品，一鍵加入購物車
- **產品推薦／相關商品** - 商品頁多訊號相關商品推薦
- **運費計算** - 固定費率 + 滿額免運，依賣場各自計算
- **折扣碼／優惠券** - 賣家自訂賣場折扣碼，結帳套用與 redemption 皆有交易鎖定防護
- **Dashboard 數據分析** - 賣家／管理員後台時段篩選、收益趨勢、Top 商品／店鋪
- **商品篩選強化** - 價格區間、低庫存篩選
- **低庫存警示** - 賣家儀表板 widget + 商品列表篩選
- **全站非同步操作回饋** - 統一 Toast、Dark Mode、loading 狀態管理
- **無障礙（a11y）優化** - navbar aria-label、表單 label 關聯、圖片 alt
- **列表頁體驗優化** - 手機橫向捲動、skeleton loading 過渡、圖片 lazy load／錯誤 fallback
- **刪除確認互動統一** - 改用 ConfirmationModal 取代原生 `confirm()`

詳細實作細節（服務層設計、資料表結構、ADR 決策）請見 `CLAUDE.md`。

## License

MIT
