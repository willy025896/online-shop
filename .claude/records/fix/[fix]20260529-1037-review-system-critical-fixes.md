# [fix] 評論系統 5 個 critical 問題

**日期：** 2026-05-29 10:37
**類型：** fix

## 變更內容

修復 post-change-review 審查中標記為 CRITICAL 的 5 個問題：3 個邏輯/品質問題（關係定義錯誤、冷靜期保護缺失、賣家可在訂單未完成時評買家）+ 2 個安全問題（商品頁與買家信用頁洩漏 PII 給匿名訪客/其他賣家）。

## 異動檔案

- `app/Models/Order.php` — productReviews() 改用 hasManyThrough(ProductReview, OrderItem)，移除錯誤的 hasMany+join
- `app/Services/ReviewService.php` — submitProductReview/submitBuyerReview 加 STATUS_COMPLETED 檢查；update/delete review 路徑呼叫新增的 resetCoolingIfActive() 在冷靜期內重置 cooling 並通知對方
- `app/Notifications/ReviewCoolingResetNotification.php` — 新建：冷靜期重置時通知另一方
- `app/Http/Controllers/ProductController.php` — show() 評論 with('user') 改成 with(['user:id,name,profile_photo_path'])，移除 email/phone/role 等 PII 從公開商品頁的 JSON 回應
- `app/Http/Controllers/Seller/BuyerCreditController.php` — 移除 with('order')（含 shipping_address/phone/total），shop 也限縮到 with(['shop:id,name'])
- `lang/en/notifications.php` / `lang/zh_TW/notifications.php` — 補充 review.cooling_reset 字串

## 實作思路

**STATUS_COMPLETED 檢查放 Service 層**而非 controller：上一版 controller 才有檢查，service 沒有，造成 controller 的兩個 entry point 不一致。改放 service 後，所有路徑（包括未來新增的）都自動受保護。

**冷靜期重置設計**：當一方在冷靜期內修改/刪除自己評論時，將 review_cooling_until 設回 null（回到「等待對方再次送出」狀態），並送 ReviewCoolingResetNotification 給另一方。對方可選擇修改自己的評論，雙方再次送出後 cooling 會再啟動。這守住了盲評的反報復承諾——你看到對方改評論時還有對等的修改機會。

**hasManyThrough 取代手動 join**：Order → OrderItem → ProductReview 是標準的 has-many-through 結構，Eloquent 原生支援。原本的手動 join 不僅 FK/local key 對錯欄位，加上 join 還會引發 ambiguous column 錯誤。

**PII 修復用 column selection 而非 hidden**：用 `with('user:id,name,...')` 比改 User::$hidden 更精準——只在這個 query 限縮欄位，不影響其他需要 email 的合法使用情境（例如賣家訂單頁顯示買家聯絡資訊）。
