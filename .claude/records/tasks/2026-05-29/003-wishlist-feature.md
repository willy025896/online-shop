---
id: 2026-05-29/003
type: Feature
status: Done
---

# Task: 收藏 / 願望清單（Wishlist）

## Request
新增收藏/願望清單功能：登入會員可收藏商品，導覽列顯示愛心徽章，收藏清單頁可移除或加入購物車（加入後保留收藏）。

## Changes
| File | Action | Notes |
|------|--------|-------|
| `database/migrations/2026_05_29_020000_create_wishlist_items_table.php` | Added | `wishlist_items` 資料表，unique(user_id, product_id) |
| `app/Models/WishlistItem.php` | Added | Model，belongsTo User/Product |
| `app/Models/User.php` | Modified | 加 `wishlistItems()`、`favoritedProducts()` 關聯 |
| `app/Models/Product.php` | Modified | 加 `wishlistItems()` 關聯 |
| `app/Services/WishlistService.php` | Added | toggle/remove/getItemsWithProducts/favoritedProductIds（清單依加入時間倒序） |
| `app/Http/Controllers/WishlistController.php` | Added | index/toggle/destroy |
| `routes/web.php` | Modified | 加 wishlist.index/toggle/destroy（auth group） |
| `app/Http/Middleware/HandleInertiaRequests.php` | Modified | 加 wishlistProductIds shared prop（count 由前端 `.length` 推導） |
| `resources/js/Components/FavoriteButton.vue` | Added | 愛心切換按鈕，partial reload，訪客導向 login |
| `resources/js/Components/ProductCard.vue` | Modified | 圖片右上角疊 FavoriteButton |
| `resources/js/Pages/Products/Show.vue` | Modified | 加入購物車旁加 FavoriteButton（md 尺寸） |
| `resources/js/Layouts/AppLayout.vue` | Modified | 桌機版愛心徽章、響應式收藏連結 |
| `resources/js/Pages/Wishlist/Index.vue` | Added | 收藏清單頁，grid 顯示，移除/加入購物車 |
| `lang/en/wishlist.php` | Added | 英文語系 |
| `lang/zh_TW/wishlist.php` | Added | 繁中語系 |
| `lang/en/navigation.php` | Modified | 加 `wishlist` 鍵 |
| `lang/zh_TW/navigation.php` | Modified | 加 `wishlist` 鍵 |
| `tests/Feature/WishlistTest.php` | Added | 9 tests，全通過 |

## Outcome
功能完整實作，9 項 Pest 測試全通過（含身份驗證守衛、toggle 新增/移除、防重複、destroy 不誤新增、清單隔離、加購後保留收藏）。全套測試通過，無回歸。pint 風格檢查乾淨。

### Post-change review 修正
- **[WARNING]** `destroy()` 原用 `toggle()`，DELETE 未收藏商品反而會新增 → 改為新增 `WishlistService::remove()` 並由 `destroy()` 呼叫。
- **[SUGGESTION]** `toggle()` 新增路徑改用 `firstOrCreate`，避免並發/雙擊撞 unique 約束 500。
- **[SUGGESTION]** 清單改用 `favoritedProducts()` 關聯 + `orderByPivot('created_at', 'desc')`，最近收藏在前。

### /simplify 精簡
- 移除冗餘的 `wishlistCount` shared prop 與 `WishlistService::getCount()`：`wishlistProductIds` 本就全站共享，count 等同 `ids.length`，前端（AppLayout）改由 `.length` 推導。每個已登入 request 省一條查詢、少一個需同步的 prop。
