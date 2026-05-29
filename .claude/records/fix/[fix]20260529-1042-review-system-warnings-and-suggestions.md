# [fix] 評論系統 warning + suggestion 全部修復

**日期：** 2026-05-29 10:42
**類型：** fix

## 變更內容

修復 post-change-review 剩餘的 5 個問題：
- **#4 (WARNING)** 14 天判斷改用 completed_at（不受 updated_at bump 影響）
- **#5 (WARNING)** addSellerReply 包進 transaction + lockForUpdate
- **#6 (WARNING)** 完成 14 天但無評論的訂單不再被靜默永久關閉
- **#7 (SUGGESTION)** ReviewReleasedNotification 帶完整 ProductReview 統計（count/avg/ratings），不再只取 ->first()
- **#8 (SUGGESTION)** updateAggregates 改成 grouped update：每個 product 一次 UPDATE，shop 全部累加後一次 UPDATE

## 異動檔案

- `database/migrations/2026_05_29_010000_add_completed_at_to_orders.php` — 新增 orders.completed_at 欄位，backfill 從 OrderStatusLog 推算（無 log 則 fallback updated_at）
- `app/Models/Order.php` — fillable/casts 加 completed_at；新增 `static::updating` 事件 hook 自動在 status 轉 completed 時設定 completed_at
- `app/Services/ReviewService.php` — addSellerReply 包 transaction+lockForUpdate；releaseOrder 計算完整 ProductReview 統計；updateAggregates 改 grouped update（per product / per shop 各 1 個 UPDATE）；import Product
- `app/Console/Commands/ReleaseReviews.php` — 14 天判斷改用 completed_at，且加 whereHas('items.review' OR 'buyerReview') 過濾無評論訂單
- `app/Notifications/ReviewReleasedNotification.php` — 簽章變更：第三參數從 ProductReview 改為 stats array（count/avg/ratings）；買家/賣家分支拆開

## 實作思路

**completed_at 用 model event 自動填**而非散落在 controller：Order::booted() 已監聽 status 變化寫 status log，再加一個 `updating` hook 在同樣時機設 completed_at 即可，後續任何新增的狀態轉移路徑（包括 PaymentService 跳過 paid 直接 completed 的虛擬商品流程）都自動受惠，不需要每個 caller 都記得手動填。`updating` 比 `updated` 早觸發，這樣 completed_at 與 status 在同一個 SQL UPDATE 寫入。

**14 天無評論訂單**：原本邏輯把「14 天逾期」當成「永久關閉」的觸發條件，但這違反產品意圖（評價窗口應該等到至少一方表態才有意義關閉）。改成 14 天 + whereHas 過濾，沒人評論的訂單就讓窗口持續開著（買家想評就還能評），直到雙方都送出觸發 cooling、或是有了 review 後 14 天才關。

**addSellerReply lockForUpdate 取代原本物件**：先 `lockForUpdate()->find($review->id)` 拿一份新鮮且鎖住的 review，再做檢查與 update。如果只 lock 原本傳入的 `$review` 物件，無法防止其他連線在 SELECT 與 UPDATE 之間插入並通過 seller_replied_at 檢查。

**grouped update**：原本實作對每個 review 都 issue 2 個 UPDATE（product + shop），shop 還會被重複 update N 次。改成先 groupBy product_id 一次寫，shop 在 loop 外用 sum/count 一次寫。N 件商品的訂單從 2N+1 個 query 降到 N+1+1 個，且消除 shop 行的多次寫入競爭。
