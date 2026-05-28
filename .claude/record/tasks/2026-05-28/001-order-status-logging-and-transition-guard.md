---
id: 2026-05-28/001
type: Feature / Bug Fix
status: Done
---

# Task: 訂單狀態轉移守衛、併發鎖補強與狀態稽核 log

## Request
延續訂單取消修復：
1. 補上 `directCancelByBuyer` / `cancelBySeller` 缺少的併發鎖（避免重複提交重複回補庫存）。
2. 修掉狀態轉移漏洞中的情形 1（復活已取消訂單）、4（狀態倒退）、5（待審取消時變更），但保留情形 2（未付款→出貨，貨到付款）、3（paid→completed，虛擬商品），改採前進制而非硬鎖順序。
3. 新增狀態轉移稽核 log（事件式），並確保 log 與狀態變更原子化。
4. 開發文件補上 log 機制說明。

## Changes
| File | Action | Notes |
|------|--------|-------|
| app/Services/OrderService.php | Modified | `directCancelByBuyer` / `cancelBySeller` 改用 `lockForUpdate` 鎖訂單列後重新檢查狀態，冪等跳過重複呼叫 |
| app/Models/Order.php | Modified | 新增 `STATUS_RANK`、`canTransitionStatusTo()`（前進制+非終態+無待審取消）、`statusLogs()` 關聯、`booted()` 掛 `updated` 事件自動寫 log |
| app/Models/OrderStatusLog.php | Added | 新 model（order/changedBy 關聯） |
| database/migrations/2026_05_28_000000_create_order_status_logs_table.php | Added | `order_status_logs` 表（from_status, to_status, changed_by, note） |
| app/Http/Controllers/Seller/OrderController.php | Modified | `updateStatus` 加 `abort_unless(canTransitionStatusTo, 422)`，並以 `DB::transaction` 包 update 確保 log 原子性 |
| app/Services/PaymentService.php | Modified | `simulatePayment` 的 update 包進 `DB::transaction`（log 原子性） |
| tests/Feature/OrderTest.php | Modified | 新增併發直接取消、轉移守衛（擋 1/4/5、放行 2/3）、log 記錄共 9 個測試 |
| CLAUDE.md | Modified | 新增「Order Status Transitions & Logging」段落（前進制規則、事件式 log、原子性與 bulk-update 注意事項）；模型數更新為 13 |
| .claude/record/decisions/003-order-status-logging.md | Added | ADR：事件式稽核 log + 前進制取代硬鎖順序之決策 |

## Outcome
- 併發鎖補強已 commit（`0372b62`）；狀態守衛 + 稽核 log + 文件為後續變更。
- `php artisan test tests/Feature/OrderTest.php` 31 passed（98 assertions）；全套件 106 passed / 1 skipped。
- Laravel Pint 全綠。
- 事件式 log 自動涵蓋付款、賣家更新、取消 finalize 所有路徑；原子性透過交易包裹保證；bulk-update 限制已文件化。
