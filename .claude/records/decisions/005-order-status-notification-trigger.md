---
id: ADR-005
title: 訂單狀態變更通知走 Model `updated` event 而非 Service 顯式呼叫
date: 2026-05-28
status: Accepted
---

# ADR-005: 訂單狀態變更通知走 Model event

## Context

訂單狀態可由多個路徑改變：

- `PaymentService::simulatePayment` (pending → paid)
- `Seller\OrderController::updateStatus` (paid → processing → shipped → completed)
- `OrderService::finalizeCancellation` (* → cancelled)
- 未來可能新增更多路徑

當買家需要被通知「我的訂單變成 shipped 了」時，要選擇通知該在哪裡發。

兩種選項：

**A. Model event 集中發**：在 `Order::booted()` 的 `updated` event 內檢查 `$order->wasChanged('status')`，自動發出 `OrderStatusChangedNotification` 給 `$order->user`。

**B. 各 Service 顯式發**：在 `PaymentService`、`Seller\OrderController::updateStatus`、`OrderService::finalizeCancellation` 都各自加 `$user->notify(...)`。

## Decision

採用 **A：走 Model `updated` event**，與既有的 `order_status_logs` 同模式。

實作位置：`app/Models/Order.php` 的 `booted()` 方法（既有的 `updated` 監聽器已存在），加上白名單過濾：

```php
private const BUYER_NOTIFY_STATUSES = [
    self::STATUS_PAID,
    self::STATUS_SHIPPED,
    self::STATUS_COMPLETED,
];
```

跳過 `processing`（內部狀態，買家無感）、`pending`（初始狀態，不會由變更觸發）、**`cancelled`（每條取消路徑都已自帶具語意的 notification，再發 generic 會 double-notify；買家自主取消時甚至會 self-notify）**。

**例外**：`OrderPaidNotification`（給賣家）仍由 `PaymentService` 顯式發。原因：對象不同——「狀態變 paid」要通知買家，「賣家有新付款訂單」要通知賣家，混在一起會讓 Model 變胖。

**`cancelled` 排除的取捨**：

| 取消路徑 | 觸發點 | 通知對象 | Notification |
|---------|-------|---------|-------------|
| 買家直接取消（pending/paid） | `OrderService::directCancelByBuyer` | （不發，自己的動作）| — |
| 賣家直接取消 | `OrderService::cancelBySeller` | 買家 | `OrderCancelledBySellerNotification` |
| 賣家核准取消請求 | `OrderService::approveCancellation` | 買家 | `OrderCancellationRespondedNotification` |
| 賣家拒絕取消請求 | `OrderService::rejectCancellation` | 買家 | `OrderCancellationRespondedNotification` |

若未來新增取消路徑，記得顯式發 notification，不會由 Model event 自動補上。

## Consequences

優點：

- 新增任何修改 status 的路徑，買家通知自動覆蓋，不會漏發
- 與 `order_status_logs` 邏輯對稱，認知負擔低
- 白名單在 Model 常數，未來加新狀態（e.g. `refunded`）只需改一處

缺點：

- 必須記得：所有改 status 的程式碼**必須**在 `DB::transaction` 內，否則 `updated` event 觸發的通知會與狀態變更不同步（已在 `CLAUDE.md` 的「Order Status Transitions & Logging」段註明此原則）
- Eloquent 的 `updated` event 不會被 bulk update（`Order::where(...)->update(...)`）觸發 → 同樣的注意事項已寫進 CLAUDE.md
- 通知對象固定為 `$order->user`（買家），無法輕易擴充給其他對象——但這正是它聚焦的價值

## Alternatives Considered

**B. 各 Service 顯式發**：

- 優點：對象選擇彈性高、單元測試容易追蹤
- 缺點：新增 status 變更路徑時容易漏發、要在每個 Service 都 import + 寫一段、會違反 DRY

不採用。本專案目前所有 status 變更已都在 transaction 內，未來新增路徑也已有 CLAUDE.md 規範守住，事件式集中通知的好處大於成本。
