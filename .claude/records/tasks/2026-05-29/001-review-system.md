---
id: 2026-05-29/001
type: Feature
status: In Progress
---

# Task: 雙向評分系統

## Request
實作買家評商品、賣家評買家的雙向盲評系統，含：星等/文字評論、冷靜期保護、公開後永久鎖定、賣家回覆通知買家、商品列表星等排序篩選、商店頁聚合星等顯示、買家信用供賣家查閱。

## Changes
| File | Action | Notes |
|------|--------|-------|
| database/migrations/2026_05_29_000001_create_product_reviews_table.php | Added | 商品評論表 |
| database/migrations/2026_05_29_000002_create_buyer_reviews_table.php | Added | 買家信用評論表 |
| database/migrations/2026_05_29_000003_add_review_aggregates_to_products.php | Added | products 聚合欄位 |
| database/migrations/2026_05_29_000004_add_review_aggregates_to_shops.php | Added | shops 聚合欄位 |
| database/migrations/2026_05_29_000005_add_review_fields_to_orders.php | Added | orders review_cooling_until / review_released_at |
| database/migrations/2026_05_29_000006_add_buyer_review_aggregates_to_users.php | Added | users buyer_reviews_count / buyer_rating_sum |
| app/Models/ProductReview.php | Added | 含 model event 維護聚合 |
| app/Models/BuyerReview.php | Added | 含 model event 維護聚合 |
| app/Models/Order.php | Modified | 新增 review 相關 relation / method |
| app/Models/Product.php | Modified | 新增 reviews relation / 聚合欄位 cast |
| app/Models/Shop.php | Modified | 新增 reviews relation / 聚合欄位 cast |
| app/Models/User.php | Modified | 新增 buyerReviews relation / 聚合欄位 cast |
| app/Services/ReviewService.php | Added | submit / cooling / release 核心邏輯 |
| app/Console/Commands/ReleaseReviews.php | Added | Artisan Command 每 10 分鐘排程 |
| app/Notifications/ReviewCoolingStartedNotification.php | Added | |
| app/Notifications/ReviewReleasedNotification.php | Added | |
| app/Notifications/SellerReplyNotification.php | Added | |
| app/Http/Controllers/ProductReviewController.php | Added | 買家評商品 |
| app/Http/Controllers/Seller/BuyerReviewController.php | Added | 賣家評買家 |
| app/Http/Controllers/Seller/ReviewReplyController.php | Added | 賣家回覆評論 |
| app/Http/Controllers/Seller/BuyerCreditController.php | Added | 買家信用頁 |
| app/Policies/ProductReviewPolicy.php | Added | |
| app/Policies/BuyerReviewPolicy.php | Added | |
| database/factories/ProductReviewFactory.php | Added | |
| database/factories/BuyerReviewFactory.php | Added | |
| routes/web.php | Modified | 新增評論相關路由 |
| routes/console.php | Modified | 排程 reviews:release |
| lang/en/reviews.php | Added | |
| lang/zh_TW/reviews.php | Added | |
| lang/en/notifications.php | Modified | 補充評論通知字串 |
| lang/zh_TW/notifications.php | Modified | 補充評論通知字串 |
| resources/js/Components/StarRating.vue | Added | 星等顯示（唯讀/可點選）|
| resources/js/Components/ReviewCard.vue | Added | 單則評論卡片 |
| resources/js/Components/RatingDistribution.vue | Added | 星等分布長條圖 |
| resources/js/Pages/Reviews/Create.vue | Added | 買家評論表單 |
| resources/js/Pages/Seller/Reviews/Index.vue | Added | 賣家評論列表 |
| resources/js/Pages/Seller/Buyers/Show.vue | Added | 買家信用頁 |
| resources/js/Pages/Products/Index.vue | Modified | 星等排序/篩選 UI |
| resources/js/Pages/Products/Show.vue | Modified | 評論區塊 |
| resources/js/Pages/Shop/Show.vue | Modified | 商店星等顯示 |
| tests/Feature/ReviewTest.php | Added | |

## Outcome
（實作完成後填寫）

## Decision
→ decisions/006-review-system.md
