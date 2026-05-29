# [feature] 雙向盲評系統

**日期：** 2026-05-29 10:18
**類型：** feature

## 變更內容

實作買家評商品、賣家評買家的雙向盲評系統。核心機制包含：盲評（雙方看不到對方的星等直到公開）、24 小時冷靜期保護（防止競態導致修改被截斷）、公開後永久鎖定評論窗口。同時支援賣家回覆評論通知買家、商品列表星等排序與篩選、商店頁聚合星等顯示、買家信用供賣家查閱。

## 異動檔案

- `database/migrations/2026_05_29_000001~000006_*.php` — 6 個 migration：product_reviews、buyer_reviews 兩張新表，products/shops/users 聚合欄位，orders 新增 review_cooling_until / review_released_at
- `app/Models/ProductReview.php` — 新評論 Model，含 released/published scope
- `app/Models/BuyerReview.php` — 新買家評論 Model，含 released/published scope
- `app/Models/Order.php` — 新增 review 關聯、isReviewWindowOpen()、isInCoolingPeriod()
- `app/Models/Product.php` — 新增 reviews 關聯、averageRating()、聚合欄位 cast
- `app/Models/Shop.php` — 新增 productReviews 關聯、averageRating()、聚合欄位 cast
- `app/Models/User.php` — 新增 buyerReviews 關聯、averageBuyerRating()、聚合欄位 cast
- `app/Models/OrderItem.php` — 新增 review() 關聯
- `app/Services/ReviewService.php` — 核心服務：submit、updateBuyer/ProductReview、deleteBuyer/ProductReview、addSellerReply、checkAndStartCooling、releaseOrder、updateAggregates
- `app/Console/Commands/ReleaseReviews.php` — Artisan command，處理 cooling_until 到期與 14 天超時強制公開
- `app/Notifications/ReviewCoolingStartedNotification.php` — 冷靜期開始通知（雙方）
- `app/Notifications/ReviewReleasedNotification.php` — 評論公開通知（雙方）
- `app/Notifications/SellerReplyNotification.php` — 賣家回覆通知買家
- `app/Http/Controllers/ProductReviewController.php` — 買家評商品 CRUD
- `app/Http/Controllers/Seller/BuyerReviewController.php` — 賣家評買家 CRUD
- `app/Http/Controllers/Seller/ReviewReplyController.php` — 賣家回覆評論
- `app/Http/Controllers/Seller/ProductReviewIndexController.php` — 賣家評論列表（含篩選）
- `app/Http/Controllers/Seller/BuyerCreditController.php` — 買家信用頁（限自家訂單）
- `app/Policies/ProductReviewPolicy.php` — 評論 update/delete 授權
- `app/Policies/OrderPolicy.php` — 新增 createReview 授權
- `app/Http/Controllers/ProductController.php` — 新增 min_rating 篩選、rating_desc 排序，show() 帶入評論分頁與分布
- `database/factories/ProductReviewFactory.php` — 工廠
- `database/factories/BuyerReviewFactory.php` — 工廠
- `routes/web.php` — 買家/賣家評論相關路由
- `routes/console.php` — 排程 reviews:release 每 10 分鐘
- `lang/en/reviews.php` / `lang/zh_TW/reviews.php` — 評論頁 i18n
- `lang/en/notifications.php` / `lang/zh_TW/notifications.php` — 補充評論通知字串
- `lang/en/navigation.php` / `lang/zh_TW/navigation.php` — 補充 seller_reviews 導覽字串
- `resources/js/Components/StarRating.vue` — 星等元件（唯讀/可點選）
- `resources/js/Components/ReviewCard.vue` — 評論卡片（含賣家回覆區）
- `resources/js/Components/RatingDistribution.vue` — 星等分布長條圖
- `resources/js/Pages/Reviews/Create.vue` — 買家評論表單（每個 OrderItem 各自送出）
- `resources/js/Pages/Seller/Reviews/Index.vue` — 賣家評論列表（含篩選、賣場評分摘要）
- `resources/js/Pages/Seller/Reviews/BuyerReviewCreate.vue` — 賣家評買家頁面
- `resources/js/Pages/Seller/Buyers/Show.vue` — 買家信用詳情頁
- `resources/js/Pages/Products/Show.vue` — 商品頁新增評論區塊（平均分 + 分布 + 列表）
- `resources/js/Pages/Products/Index.vue` — 商品列表新增星等篩選 chips + 評分最高排序
- `resources/js/Pages/Shop/Show.vue` — 商店頁 header 顯示平均星等
- `resources/js/Pages/Orders/Show.vue` — 訂單頁新增「撰寫評論」按鈕（completed + 窗口未關）
- `resources/js/Layouts/SellerLayout.vue` — 側欄新增「評論管理」導覽項目
- `tests/Feature/ReviewTest.php` — 15 個 Pest 測試
- `tests/TestCase.php` — 加入 withoutVite() 避免測試環境 Vite manifest 錯誤

## 實作思路

公開狀態統一由 `orders.review_released_at` 控制（單一真相來源），review 表不做各自判斷，省去雙表不一致的風險。聚合欄位（reviews_count / rating_sum）在 release 時才一次性更新，確保盲評期間不洩漏資訊給任何一方。冷靜期（24 小時）在雙方都送出後才觸發，並以 `DB::lockForUpdate()` 防止 release 排程與 edit 請求的競態。賣家評買家採用獨立的 `buyer_reviews` 表而非 polymorphic，因為聚合目標（user vs product/shop）、顯示位置與星等語意都完全不同，分表更清晰。排程 `reviews:release` 同時處理冷靜期到期與 14 天超時兩種情境。
