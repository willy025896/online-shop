# [fix] 評論窗口 14 天必關閉 + UI 倒數說明

**日期：** 2026-05-29 10:48
**類型：** fix

## 變更內容

修正 #6 的修復決策。原本改成「無評論訂單不關閉窗口」會留下長尾報復評論的破口（買家數月後突然回來打差評，賣家評買家窗口早已心理上關閉，無法對等回擊）。改為 **14 天一律關閉**，但在訂單詳情頁加倒數說明，讓使用者明確知道評論時限。

## 異動檔案

- `app/Console/Commands/ReleaseReviews.php` — 移除無評論訂單的 whereHas 過濾，14 天後一律關閉
- `app/Http/Controllers/Seller/OrderController.php` — show() 多帶 buyerReview / buyerRating / canReviewBuyer 給前端
- `resources/js/Pages/Orders/Show.vue` — 加 reviewDaysLeft computed，並在 status=completed 且窗口未關時顯示黃色 banner
- `resources/js/Pages/Seller/Orders/Show.vue` — 同樣加倒數 banner，並嵌入「評價買家」按鈕；客戶資訊區帶買家評分 chip 連到信用頁
- `lang/en/reviews.php` / `lang/zh_TW/reviews.php` — 新增 window_open_notice / window_open_notice_seller / window_today_notice

## 實作思路

**為什麼要關**：盲評機制的反報復承諾依賴「雙方都在同個時間窗口內表態」。若一方可以無限期保留評論權，另一方早已過了會評論的心理週期，dual-blind 就變成單方面武器。固定 14 天讓雙方知道規則一致。

**倒數計算放前端**：`completed_at + 14d - now()`，純展示用途。後端 ReleaseReviews 每 10 分鐘掃描，是真正的執行者。前端倒數可能有時鐘飄移，但只用來顯示提醒，不需要嚴格一致。

**賣家側多顯示「評價買家」按鈕**：原本賣家只能從 buyer-reviews.create 路由的某個地方進入評買家頁，但訂單頁更自然 — 賣家完成訂單後在訂單頁就直接看到提醒與按鈕，配合倒數效果，提升評價提交率。
