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
├── .claude/                    # AI task records, decisions (ADR), implementation records
├── app/
│   ├── Console/Commands/       # ReleaseReviews (排程：每 10 分鐘公開到期評論)
│   ├── Events/                 # MessageSent (chat broadcast)
│   ├── Exceptions/             # CouponException, EcpayException, EinvoiceException（機器可讀的 `reason`）
│   ├── Http/
│   │   ├── Controllers/        # public + utility + seller + admin (incl. NotificationController, EcpayController, ReviewControllers)
│   │   └── Middleware/         # EnsureRole, SetLocale, HandleInertiaRequests
│   ├── Notifications/          # 14 個 Notification classes (Order*, Shop*, Review*, Payout*)，共用 BroadcastsAsArray trait
│   ├── Policies/                # Product, Order, Shop, ProductReview, Coupon, Conversation
│   ├── Models/                 # 27 models (User, Shop, Product, Order, OrderReturn, ProductVariant, ProductReview, BuyerReview, WishlistItem, Coupon, Payout, ...)
│   └── Services/               # Cart, Order, Payment, Ecpay (gateway), EcpayInvoice (gateway), Invoice, Shipping, Coupon, Conversation, Review, Wishlist, ProductVariant, Payout, Recommendation, AdminAuditLogger
├── database/
│   └── migrations/             # 51 migrations
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
│   ├── api.php                 # 免 CSRF 的端點（component-lang、ECPay payment notify）
│   ├── console.php             # 排程指令（reviews:release 每 10 分鐘）
│   └── channels.php            # Broadcast channel authorization
└── tests/                      # Pest tests
```

## Features

- **雙向盲評論系統** - 買家評商品、賣家評買家，24h 冷靜期後自動公開
- **收藏／願望清單** - 登入會員可收藏商品，一鍵加入購物車
- **產品推薦／相關商品** - 商品頁多訊號相關商品推薦
- **運費計算** - 固定費率 + 滿額免運，依賣場各自計算
- **折扣碼／優惠券** - 賣家自訂賣場折扣碼，結帳套用與 redemption 皆有交易鎖定防護
- **商品規格／多變體（SKU）** - 賣家可選擇性新增規格組合，各 variant 各自定價與庫存
- **商品問答（Q&A）** - 買家可在商品頁直接詢問賣家，沿用聊天系統
- **售後退貨／退款** - 完成訂單 7 天內可申請退貨，核准後自動回補庫存、依優惠券折扣比例退款
- **平台抽成與賣家撥款** - 全平台統一費率，Admin 手動觸發撥款，逐筆訂單金額快照
- **金流串接（綠界 ECPay）** - 買家導向 ECPay 收銀台付款，server 端 notify webhook 驗簽後才標記付款，退款走真實 API
- **商品搜尋自動完成／熱門搜尋** - 商品搜尋列即時建議
- **SEO 基礎建設** - sitemap.xml、robots.txt、商品／賣場／分類頁 OG meta
- **平台治理三件套** - Admin 全站優惠券、操作稽核紀錄、賣家商品 CSV 匯入/匯出
- **Dashboard 數據分析** - 賣家／管理員後台時段篩選、收益趨勢、Top 商品／店鋪
- **商品篩選強化** - 價格區間、低庫存篩選
- **低庫存警示** - 賣家儀表板 widget + 商品列表篩選
- **全站非同步操作回饋** - 統一 Toast、Dark Mode、loading 狀態管理
- **無障礙（a11y）優化** - navbar aria-label、表單 label 關聯、圖片 alt、icon-only 按鈕 aria-label 與可視 focus 樣式
- **列表頁體驗優化** - 手機橫向捲動、skeleton loading 過渡、圖片 lazy load／錯誤 fallback、清單頁空狀態設計、手機版聊天室返回導覽
- **刪除確認互動統一** - 改用 ConfirmationModal 取代原生 `confirm()`

詳細實作細節（服務層設計、資料表結構、ADR 決策）請見 `CLAUDE.md`。

## Known Limitations（已知架構債）

- **`PaymentService::handleGatewayNotification()` 驗簽與退款政策決策耦合**——這個方法同時做「驗證 ECPay CheckMacValue 簽章」（gateway 機制層）跟「訂單若已離開 pending 狀態時該退多少錢」（業務政策層，目前退款金額用 `$locked->total` 內聯計算，跟其餘退款路徑的比例折扣邏輯形狀不同）。更深的修法是拆成「驗簽」與「決定 notify 結果」兩層，並把這個退款決策搬進 `OrderService`，跟其餘取消/退貨的退款邏輯放在同一處。目前只有一個呼叫點，暫不視為急迫（見 ADR-015）。
- **「退款呼叫必須是 transaction 最後一步」只靠註解約定**——`OrderService::finalizeCancellation()`/`finalizeReturn()` 都手動把 `PaymentService::refund()` 放在方法最後一行，靠註解提醒之後不能再有會失敗的步驟，沒有結構化機制強制執行。可考慮做一個 `runWithTrailingRefund(callable $body): void` 之類的 helper；目前只有 2 個呼叫點，還沒到需要抽象化的門檻，先記錄、之後有第三個退款呼叫點再評估。
- **電子發票（B2C）只做了核心路徑，尚未補測試與 review**——`InvoiceService`/`EcpayInvoiceGateway`（開立掛在 `PaymentService::markAsPaid()`，作廢/折讓掛在 `OrderService::finalizeCancellation()`/`finalizeReturn()`）已經可以運作，但**沒有任何 Pest 測試覆蓋，也還沒跑過 `post-change-review`（code-review + security-review）**，AES 加解密邏輯目前只驗證過內部一致性（加解密 round-trip），沒有對 ECPay stage 環境送過任何真實 HTTP 請求。上線前務必補齊這三項再使用。詳見 ADR-019。

## License

MIT
