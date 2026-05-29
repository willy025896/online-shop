---
id: ADR-003
title: 訂單狀態轉移採事件式稽核 log，並以前進制取代硬鎖順序
date: 2026-05-28
status: Accepted
---

# ADR-003: 訂單狀態轉移採事件式稽核 log，並以前進制取代硬鎖順序

## Context
賣家可透過 `Seller\OrderController::updateStatus` 更新訂單狀態，但後端原本只用 `Rule::in` 驗證目標值，未檢查當前狀態，導致：可復活已取消訂單（庫存已回補卻不重扣）、可倒退狀態、可在買家取消請求待審時逕自變更。實務上訂單狀態流轉多變（貨到付款需未付款即可出貨、虛擬商品付款後可直接完成），硬鎖死線性順序會失去彈性。同時需要一份完整的狀態異動稽核軌跡。

## Decision
1. **轉移規則改為前進制**：以 `Order::STATUS_RANK`（pending<paid<processing<shipped<completed）定義順序，`Order::canTransitionStatusTo()` 只允許「來源非終態 + 無待審取消 + 目標 rank 嚴格大於來源」。如此擋掉復活取消單、倒退、待審期間變更三種情形，同時保留 `pending→shipped`、`paid→completed` 等合理跳階。非法轉移 `abort(422)`。
2. **稽核 log 採事件式**：新增 `order_status_logs` 表與 `OrderStatusLog` model，於 `Order::booted()` 掛 `updated` 事件，只要 `status` 變動即寫入 `from_status`/`to_status`/`changed_by`。自動涵蓋付款、賣家更新、取消 finalize 等所有路徑，無需在各呼叫點重複記錄。

## Consequences
- 優點：稽核軌跡集中且自動；轉移規則保有彈性又能擋掉危險操作；新增狀態流程無須各自寫 log。
- 限制（已寫入 CLAUDE.md）：
  - **原子性**——log insert 發生在 `updated` 事件內，須讓 `$order->update()` 跑在 `DB::transaction` 中才會與狀態變更一起 commit。現有所有改狀態路徑（`updateStatus`、`PaymentService::simulatePayment`、`OrderService` 取消相關方法）皆已包交易，新增者亦須比照。
  - **bulk update 繞過**——Eloquent `updated` 事件不會在 query builder 的 `Order::where(...)->update([...])` 觸發，會無聲漏記。一律以 model instance 改狀態。

## Alternatives Considered
- **硬鎖線性順序白名單**（paid→processing→shipped→completed 逐步）：最嚴格，但失去貨到付款、虛擬商品等合理跳階彈性，故不採用。
- **集中式 `changeStatus()` 服務方法**作為唯一狀態變更入口：可天然原子化並控制 log，但失去事件式的自動攔截，需仰賴每個呼叫點的紀律（任何直接 `$order->update(['status'=>...])` 都會漏記），故選擇事件式 + 交易包裹。
