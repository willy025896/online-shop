# [refactor] /simplify — 評論系統清理與小修

**日期：** 2026-05-29 11:07
**類型：** refactor

## 變更內容

`/code-review --fix` 找到的 9 項修復：4 個正確性、3 個重用、2 個簡化。同步重構所有 10 個 Notification 類別套用共用 trait。

## 異動檔案

**正確性 / 行為**
- `app/Console/Commands/ReleaseReviews.php` — `->get()` 改 `chunkById(100, ...)`，避免 backlog 載入全部 order OOM
- `app/Services/ReviewService.php` — `resetCoolingIfActive` 改為「永遠通知對方」（無論 cooling 是否已被前一方清掉）；`releaseOrder` 統一查詢 productReviews 一次並傳給 `updateAggregates`；`buyerReview` 改用 `->first()` 強制 fresh fetch（避免 cached null）
- `app/Models/Order.php` — `productReviews()` 加 `->where('product_reviews.status', PUBLISHED)` 防止 hidden 評論透過此關係外洩
- `app/Notifications/ReviewReleasedNotification.php` — 買家側 meta 欄位 `buyer_rating` 改名為 `seller_rating_of_buyer`，正確表達「賣家對這位買家的評分」

**重用 (DRY)**
- `app/Notifications/Concerns/BroadcastsAsArray.php` — 新建 trait
- `app/Notifications/*.php` (10 個) — 全部套用 trait，移除重複的 toBroadcast() 樣板
- `resources/js/Composables/useReviewCountdown.js` — 新建，封裝 14 天倒數邏輯
- `resources/js/Pages/Orders/Show.vue`、`Seller/Orders/Show.vue` — 改用 composable，移除重複的時間計算

**簡化**
- `resources/js/Components/StarRating.vue` — `sizeClass / starSizeClass / countSizeClass` 改用 `computed()`，支援 `:size` 動態變動
- `resources/js/Components/ReviewCard.vue` — `useForm` 移到 setup（不在 click handler 內），啟用 `processing` 狀態 disable 按鈕防雙擊，並顯示 server-side errors

## 實作思路

**chunkById 而非 chunk**：`chunk()` 用 OFFSET，當迴圈內修改 review_released_at 後 chunk 邊界會跳列；`chunkById` 用主鍵游標推進，安全。

**resetCoolingIfActive 永遠通知**：原本只在 `cooling_until !== null` 時才通知，意圖是「冷靜期內的修改才有意義通知」。但這代表第二位編輯者的變更會被吞掉（第一位已把 cooling 清空）。改為永遠通知對方，反映實際語意 ──「窗口開著時對方的修改你應該知道」。

**Notification trait**：之前 10 個類別每個都有相同 3 行 `toBroadcast`，新增類別時容易忘記、或者未來要在 broadcast payload 加共通欄位（如 `notification_id`）需修 10 處。`BroadcastsAsArray` trait 集中規範「broadcast = database 載荷」這個專案約定。

**useReviewCountdown 抽離**：14 天規則是業務常數（與 `ReleaseReviews` 一致），重複在兩個 Vue 頁面寫死數字會漂移。Composable 用 `() => props.order` 而非 reactive ref，因為 props 已是 reactive，直接傳會破壞 reactivity 鏈；getter 模式較穩。

**StarRating computed**：原本用普通 `const`，prop 變動不會 re-render size 樣式。包 `computed()` 一行修正，沒有效能成本。

**ReviewCard useForm**：`useForm` 設計為「綁定到組件實例」，每次 click 重建會丟失 `processing` / `errors` 狀態，導致 server 422 不顯示、按鈕無法 disable。

## 跳過的 findings

- **Seller/Orders/Show.vue `route(..., order.user?.id)` 防護** — controller 已保證 `user` null 時 `buyerRating` 也 null，現有 v-if 充分
- **`completed_at` bulk update bypass** — 與既有 status-log 事件同樣的 Eloquent caveat，CLAUDE.md 已記錄；非真 bug
- **Post-release aggregate drift on admin hide** — 目前無 admin moderation UI 可觸發此路徑；應與該 feature 一同設計
- **Shop-ownership Policy 重構** — 跨 6 個 controller，獨立 task 較合理
- **3-timestamp 改 state-machine column** — 架構性變更，超出此次 cleanup 範疇
