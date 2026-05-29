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

```bash
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
│   └── Services/               # Cart, Order, Payment, Conversation, Review, Wishlist
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

## TODO

### Core Features
- [x] **評論/評分系統** - 雙向盲評：買家評商品 + 賣家評買家，24h 冷靜期，14 天自動公開
- [x] **收藏/願望清單** - 登入會員可收藏商品，導覽列愛心徽章，收藏清單頁支援移除及一鍵加入購物車
- [ ] **運費計算** - 完善結帳流程，根據重量/地區等計算運費（目前硬編碼為 0）
- [ ] **折扣碼/優惠券** - 實現折扣碼和優惠券功能，促進銷售
- [x] **產品推薦/相關商品** - 商品頁相關商品（多訊號：一起購買 → 同分類 → 同賣場遞補），由 `RecommendationService` 產生
- [ ] **瀏覽歷史追蹤** - 記錄用戶瀏覽商品的歷史記錄
- [ ] **購物車本地化** - 未登入用戶購物車以 localStorage 暫存，登入後自動合併

### UI/UX Optimization
- [ ] 前端組件視覺美化
- [ ] 使用者體驗流暢性改善
- [ ] 響應式設計完善
- [ ] 動畫與過渡效果

## License

MIT
