# [feature] Wishlist / Favorite System

**日期：** 2026-05-29 15:09
**類型：** feature

## 變更內容

新增收藏/願望清單功能，讓登入會員可在商品卡片、商品詳情頁一鍵收藏商品。導覽列顯示愛心徽章（含數量），收藏清單頁支援移除及一鍵加入購物車（加入後保留收藏）。

## 異動檔案

- `database/migrations/2026_05_29_020000_create_wishlist_items_table.php` — wishlist_items 資料表，unique(user_id, product_id) 防重複
- `app/Models/WishlistItem.php` — 新增 Model
- `app/Models/User.php` — 加 wishlistItems() / favoritedProducts() 關聯
- `app/Models/Product.php` — 加 wishlistItems() 關聯
- `app/Services/WishlistService.php` — toggle（firstOrCreate）/remove/getItemsWithProducts（依加入時間倒序）/favoritedProductIds
- `app/Http/Controllers/WishlistController.php` — index/toggle/destroy 三個 action（destroy 呼叫 remove）
- `routes/web.php` — 三條 wishlist 路由放入 auth middleware group
- `app/Http/Middleware/HandleInertiaRequests.php` — 加 wishlistProductIds shared prop（lazy closure）；收藏數由前端 `.length` 推導
- `resources/js/Components/FavoriteButton.vue` — 愛心切換元件，partial reload，訪客導向 login
- `resources/js/Components/ProductCard.vue` — 圖片右上角疊加 FavoriteButton
- `resources/js/Pages/Products/Show.vue` — 購物車按鈕旁加 FavoriteButton（md 尺寸）
- `resources/js/Layouts/AppLayout.vue` — 桌機版愛心徽章、響應式收藏連結（僅登入顯示）
- `resources/js/Pages/Wishlist/Index.vue` — 收藏清單頁，空狀態 + grid + 移除/加入購物車
- `lang/en/wishlist.php`、`lang/zh_TW/wishlist.php` — 清單頁語系
- `lang/en/navigation.php`、`lang/zh_TW/navigation.php` — 加 wishlist nav 字串
- `tests/Feature/WishlistTest.php` — 8 項 Pest 測試

## 實作思路

整體架構刻意對齊既有購物車（CartService + CartController + shared prop lazy closure），降低認知負擔。FavoriteButton 用 Inertia partial reload（`only: ['wishlistProductIds', 'flash']`）切換狀態，避免重抓整頁資料。訪客點收藏導向 login 而非靜默失敗，符合「僅登入會員」設計。收藏與購物車完全獨立，加入購物車不自動移除收藏，符合使用者確認的行為。

收藏數量不另存 shared prop：`wishlistProductIds` 已全站共享，導覽列徽章直接由 `ids.length` 推導，省去一條 COUNT 查詢與一個需同步的 prop。刪除採用獨立的 `remove()` 而非 `toggle()`，避免 DELETE 對未收藏商品反而新增的反直覺行為。
