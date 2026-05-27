---
id: 2026-05-27/001
type: Feature
status: Done
---

# Task: 取消訂單功能（含理由與賣家審核流程）

## Request
新增取消訂單功能：買家發起取消需填理由，賣家未開始處理可直接取消、已處理則需賣家同意或拒絕（拒絕後不可再申請）；賣家也可主動取消。取消訂單須保留並顯示取消狀態與理由。

## Changes
| File | Action | Notes |
|------|--------|-------|
| database/migrations/2026_05_27_000000_create_order_cancellations_table.php | Added | 新表記錄每次取消申請 |
| app/Models/OrderCancellation.php | Added | model + order/responder 關聯 |
| database/factories/OrderCancellationFactory.php | Added | requested/approved/rejected/bySeller states |
| app/Models/Order.php | Modified | cancellations/latestCancellation 關聯與取消判斷 helper，移除舊 canBeCancelled |
| database/factories/OrderFactory.php | Modified | 新增 processing() state |
| app/Services/OrderService.php | Modified | cancelOrder → finalizeCancellation，新增 direct/request/approve/reject/cancelBySeller |
| app/Http/Controllers/OrderController.php | Modified | cancel 改為帶 reason，依狀態直接取消或送審 |
| app/Http/Controllers/Seller/OrderController.php | Modified | 新增 cancel/approveCancellation/rejectCancellation |
| app/Policies/OrderPolicy.php | Modified | cancel 改寫，新增 manageCancellation/cancelAsSeller |
| routes/web.php | Modified | 賣家 3 條取消相關路由 |
| resources/js/Pages/Orders/Show.vue | Modified | 取消理由 modal、取消狀態顯示 |
| resources/js/Pages/Seller/Orders/Show.vue | Modified | 審核申請、主動取消 modal、取消資訊 |
| lang/{en,zh_TW}/orders.php, lang/{en,zh_TW}/seller.php | Modified | 取消相關字串 |
| tests/Feature/OrderTest.php | Modified | 9 個取消相關案例 |

## Outcome
完成。`php artisan test` 94 passed / 1 skipped（含 19 OrderTest），Pint pass，`npm run build` 成功。
取消視窗：買家 pending/paid 直接取消、processing/shipped 送審；賣家任一活躍狀態可主動取消；拒絕後買家不可再申請。通知以訂單頁狀態+理由+flash 呈現。

## Decision
資料模型採獨立 order_cancellations 表 → 見 decisions/002-order-cancellation-model.md
