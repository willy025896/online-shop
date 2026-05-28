---
id: 2026-05-28/003
type: Refactor
status: Done
---

# Task: 出貨後（shipped）不再允許取消訂單

## Request
使用者指出：訂單一旦進入 `shipped` 狀態就不該被隨便取消，買賣家皆然；召回情境應走例外處理而非正常取消流程。前端對應的取消按鈕也需隱藏。

## Changes
| File | Action | Notes |
|------|--------|-------|
| app/Models/Order.php | Modified | `canRequestCancellation()` 移除 `STATUS_SHIPPED`；新增 `canBeCancelledBySeller()` 集中賣家可取消規則（pending/paid/processing 且無待審 cancellation） |
| app/Policies/OrderPolicy.php | Modified | `cancelAsSeller` 改用 `Order::canBeCancelledBySeller()` |
| app/Services/OrderService.php | Modified | `cancelBySeller` 早退守衛改用 `canBeCancelledBySeller()` |
| app/Http/Controllers/Seller/OrderController.php | Modified | `show()` 傳給前端的 `canSellerCancel` prop 改用 `canBeCancelledBySeller()` |
| tests/Feature/OrderTest.php | Modified | 新增 `buyer cannot cancel a shipped order`、`seller cannot directly cancel a shipped order` 兩個測試 |
| app/Models/Order.php | Modified (cleanup) | 統一 `pendingCancellation()` null 檢查風格為 `=== null`；`pendingCancellation()` 與 `wasCancellationRejected()` 加 `relationLoaded('cancellations')` 短路，eager-load 時免去重複查詢 |

前端 `Orders/Show.vue` 與 `Seller/Orders/Show.vue` 不需修改——取消按鈕已透過 `canRequestCancellation` / `canCancelDirectly` / `canSellerCancel` props 控制顯示，shipped 訂單的 props 全部會是 `false`，按鈕自動隱藏；OrderPolicy 也會擋下硬 POST。

## Outcome
- 規則統一：買賣家對 shipped 都不可取消，召回需走例外通道。
- 賣家可取消狀態收斂為 pending / paid / processing（加無待審 cancellation）。
- Pest 全套 35 測試通過（含新增 2 個 shipped 拒絕測試）。
