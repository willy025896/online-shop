---
id: 2026-05-27/002
type: Bug Fix
status: Done
---

# Task: 修復訂單取消漏洞與併發競爭問題

## Request
根據訂單取消修復計畫，解決併發雙重審核、雙重提交、繞過賣家政策、前後端狀態不同步等漏洞。

## Changes
| File | Action | Notes |
|------|--------|-------|
| app/Http/Controllers/OrderController.php | Modified | 傳遞 `canCancelDirectly` 與 `canRequestCancellation` 至前端 Show.vue |
| app/Http/Controllers/Seller/OrderController.php | Modified | 傳遞 `canSellerCancel` 與 `nextStatuses` 至前端，並在 approve/reject 時加入 abort 檢查防範 null 錯誤 |
| app/Policies/OrderPolicy.php | Modified | 在 `cancelAsSeller` 中加入 pendingCancellation() 的限制，防止賣家繞過審核 |
| app/Services/OrderService.php | Modified | 引入 `DB::transaction` 與鎖機制（`lockForUpdate`）確保併發與資料安全，並實作冪等處理防止 duplicate request |
| database/factories/OrderCancellationFactory.php | Modified | 移除寫死字串，使用 Model 中的具名常數（如 `INITIATED_BY_BUYER`） |
| resources/js/Pages/Orders/Show.vue | Modified | 狀態改由 Props 傳遞值判斷，同步前後端邏輯，不再寫死狀態計算 |
| resources/js/Pages/Seller/Orders/Show.vue | Modified | 取消與下一個狀態改由 Props 傳遞值判斷，同步前後端邏輯 |
| tests/Feature/OrderTest.php | Modified | 新增 3 個 Pest 測試（驗證併發重複申請、繞過政策限制、與無申請重複審核之防範） |

## Outcome
- 成功解決併發雙重審核、重複提交、繞過政策、前後端狀態不一致等漏洞。
- 所有 Pest 測試全部通過（含新增之 3 個整合測試案例，無任何失敗）。
- 執行 `npm run build` 編譯前端資源，產出正確之 Vite assets。
