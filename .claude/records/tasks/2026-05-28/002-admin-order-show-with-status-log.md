---
id: 2026-05-28/002
type: Feature
status: Done
---

# Task: 管理員訂單詳細頁與狀態變更紀錄顯示

## Request
為剛建立的 `order_status_logs` 加上顯示介面。管理員端必須能看，賣家端暫不做（log 大多是賣家自己的動作，且 admin 介入操作不適合對賣家曝光，等有實際需求再加）。

由於 admin 端原本只有訂單列表沒有詳細頁，本任務一併建立新的 detail page。

## Changes
| File | Action | Notes |
|------|--------|-------|
| routes/web.php | Modified | 新增 `admin.orders.show`（GET `/admin/orders/{order}`） |
| app/Http/Controllers/Admin/OrderController.php | Modified | 新增 `show(Order $order)`，eager-load `user`/`shop`/`items.product`/`cancellations.responder`/`statusLogs.changedBy`（log 以 id desc 排序） |
| resources/js/Pages/Admin/Orders/Show.vue | Added | read-only 詳細頁：訂單摘要、收件資訊、商品明細、取消記錄表格、狀態變更紀錄表格（時間 / from→to 用 OrderStatusBadge / 操作者+角色，無操作者顯示「系統」） |
| resources/js/Pages/Admin/Orders/Index.vue | Modified | 訂單編號改為 `Link` 跳轉至 show 頁 |
| lang/zh_TW/admin.php、lang/en/admin.php | Modified | `admin.orders` 區塊擴充：details、order_items、shipping_to、cancellations、status_log、cancellation_statuses、role_* 等字串 |
| tests/Feature/OrderTest.php | Modified | 新增 2 個測試：非 admin（guest/buyer/seller）無法存取 admin.orders.show；admin 可看到 status_logs prop 且內容正確（from/to/changed_by.id） |
| public/build/* | Modified | `npm run build` 重新產生 Vite 資產 |

## Outcome
- 全套件 108 passed / 1 skipped（含本次新增 2 筆）；Pint 全綠；Vite build 成功。
- 賣家端 log 顯示故意未實作；待有實際使用需求再加。
