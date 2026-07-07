---
id: ADR-013
title: 售後退貨/退款（Order Returns & Refunds）
date: 2026-07-06
status: Accepted
---

# ADR-013: 售後退貨/退款（Order Returns & Refunds）

## Context

現有的「取消」流程（`OrderCancellation` / `OrderService::requestCancellation()` 等，見 ADR-002）只發生在訂單 `pending`/`paid`/`processing` 階段——出貨前。訂單一旦 `completed`，目前完全沒有退貨/退款機制，`PaymentService` 也只有 `simulatePayment()`，沒有任何退款方法或欄位（`orders` 表沒有 `refunded_at`/`refund_amount` 之類欄位）。

這是「售後」場景，跟既有取消流程在語意上不同：
- 取消是「整張訂單」層級（出貨前不會有「只取消部分商品」的需求），`OrderCancellation` 的資料模型也是整單設計，沒有品項/數量粒度。
- 售後退貨天然是**品項/數量層級**——買家收到 3 件商品，可能只想退其中 1 件，不會強迫整單退。

因此不沿用 `OrderCancellation`，另外設計一組退貨專用的模型；但**沿用**既有取消流程已驗證過的模式：`status`/`initiated_by`-like 欄位、`responder_id`/`response_reason`/`responded_at`、`DB::transaction()` + `lockForUpdate()` 的併發保護、`Notification` 的 `{type,title,body,url,meta}` 慣例。

`Order::STATUS_RANK` 是「只能往前」的單向狀態機（`pending→paid→processing→shipped→completed`），`completed`/`cancelled` 是排除在外的終態。退貨核准後訂單**不會、也不應該**離開 `completed` 狀態（不會變成新的 `returned`/`refunded` 狀態值，那會破壞現有 `canTransitionStatusTo()` 的單向假設，也會讓一堆「訂單是否完成」的既有判斷要重新盤點）。做法比照 `review_cooling_until`/`review_released_at` 當初的做法——用**額外欄位**表達退貨/退款狀態，不動 `orders.status` 本身。

## Decision

### 1. 資料模型：`OrderReturn` + `OrderReturnItem`

新增兩張表：

**`order_returns`**（一張訂單可以有多筆退貨申請紀錄，例如先退 1 件瑕疵品，之後再退另 1 件）：
- `order_id` FK → `orders`，cascadeOnDelete
- `status` string，indexed：`OrderReturn::STATUS_REQUESTED` / `STATUS_APPROVED` / `STATUS_REJECTED`
- `reason` text（買家申請退貨的原因，必填）
- `response_reason` text nullable（賣家拒絕時的原因）
- `responder_id` FK → `users`，nullable，nullOnDelete
- `responded_at` timestamp nullable
- `refund_amount` decimal(10,2) nullable（核准當下算出的實際退款金額，作為這筆申請的稽核紀錄）
- `timestamps()`

跟 `OrderCancellation`的差異：**沒有 `initiated_by`**——退貨永遠是買家發起（賣家沒有「主動幫買家退貨」的業務情境，跟取消不同），少一個沒意義的欄位。

**`order_return_items`**（品項/數量粒度）：
- `order_return_id` FK → `order_returns`，cascadeOnDelete
- `order_item_id` FK → `order_items`，cascadeOnDelete
- `quantity` integer（本次申請退貨的數量）
- `timestamps()`

`OrderReturn` model 的 `items(): HasMany`；`OrderItem` 新增 `returnItems(): HasMany` 與 `returnedQuantity(): int`（`whereHas('orderReturn', fn($q) => $q->where('status', OrderReturn::STATUS_APPROVED))->sum('quantity')`，用來防止重複退貨與判斷「是否整單都退完了」）。

**`orders` 表新增 `refunded_amount` decimal(10,2) default 0**（累計已退款金額，比照 `reviews_count`/`rating_sum` 的既有 denormalize 慣例，讓買家訂單頁與未來的撥款結算不用每次都 join `order_returns` 加總）。

### 2. 資格與流程（比照 `OrderCancellation` 的 lock + 冪等 guard 慣例）

- **申請資格**：`Order::canRequestReturn()` — 僅 `status === STATUS_COMPLETED`，且 `completed_at` 在 `config('returns.window_days')`（新增 `config/returns.php`，env `ORDER_RETURN_WINDOW_DAYS`，預設 7 天，比照 `config/shipping.php`/`config/inventory.php` 的 config-driven 慣例）天內，且**沒有**尚在 `requested` 狀態的退貨申請（`Order::pendingReturn()`，比照 `pendingCancellation()`）。同一時間只允許一筆待處理的退貨申請，處理完（核准或拒絕）才能再申請下一筆。
- **買家申請**（`OrderController::requestReturn()`，複用既有 `cancel()` 所在的 controller，不另開 controller）：驗證每個選擇的 `order_item_id` 屬於這張訂單、`quantity` ≤ `該品項原始數量 - returnedQuantity()`（防止分次退貨超退），`DB::transaction` + `Order::lockForUpdate()` 建立 `OrderReturn`(`requested`) + `OrderReturnItem` 們，通知賣家（`OrderReturnRequestedNotification`）。
- **賣家審核**（`Seller\OrderController::approveReturn()`/`rejectReturn()`，複用既有 `approveCancellation()`/`rejectCancellation()` 所在的 controller）：鎖 `OrderReturn` 列（`lockForUpdate()`，guard `status === STATUS_REQUESTED`，冪等防重複核准/拒絕，比照 `approveCancellation()`）。
  - **拒絕**：設 `STATUS_REJECTED` + `response_reason` + `responded_at`，不動庫存/coupon/金額，通知買家。
  - **核准** → `OrderService::finalizeReturn(OrderReturn $return)`：
    1. 逐 `OrderReturnItem` 用既有 `finalizeCancellation()` 的還原庫存寫法（`ProductVariant::withTrashed()`/`Product` 依 `quantity` `increment('stock', ...)`）加回庫存——退貨一律加回可售庫存，不做「瑕疵品不可再售」的例外判斷（v1 範圍限縮，見 Consequences）。
    2. 算 `refund_amount`：`itemsSubtotal = Σ(order_item.unit_price × 本次退貨 quantity)`；若訂單有套用優惠券，用**比例扣減**（`discountRatio = order.discount / order.subtotal`，`refund_amount = round(itemsSubtotal × (1 - discountRatio), 2)`），與既有「免運門檻用折扣前 subtotal 判斷」（見 `ShippingService`/ADR-007）走同一套「用 subtotal/discount 比例做計算」慣例。運費（`shipping_fee`）不退——服務已履行，比照多數電商作法，也避免要另外設計逆物流費用歸屬。
    3. `PaymentService::simulateRefund($order, $refundAmount)`：`$order->increment('refunded_amount', $refundAmount); return true;`，結構對稱 `simulatePayment()`，先模擬、日後接真金流時把內部實作換成呼叫綠界/藍新的退款 API。
    4. 若**這筆退貨核准後，訂單所有品項的 `returnedQuantity()` 都等於原始 `quantity`**（整單等同全退）→ 呼叫既有 `CouponService::releaseForOrder()` 釋放優惠券使用紀錄。**部分退貨不釋放優惠券**——買家保留未退品項上的折扣優惠，`coupon_redemptions`/`used_count` 維持已使用狀態（v1 簡化，見 Consequences）。
    5. `OrderReturn` 設 `STATUS_APPROVED` + `responder_id` + `responded_at` + `refund_amount`，通知買家（`OrderReturnRespondedNotification`，帶 `refund_amount` 在 `meta`）。

### 3. Notification（比照既有命名/payload 慣例）

- `OrderReturnRequestedNotification` → 賣家，`type: 'order.return_requested'`（比照 `OrderCancellationRequestedNotification`）
- `OrderReturnRespondedNotification` → 買家，`type` 依 `status` 為 `order.return_approved`/`order.return_rejected`（比照 `OrderCancellationRespondedNotification`），核准時 `meta` 帶 `refund_amount`

兩者都 `use BroadcastsAsArray, Queueable;`，`via() => ['database', 'broadcast']`，翻譯字串放 `lang/{locale}/notifications.php` 的 `order.return_*` 鍵。

### 4. 路由 / Policy

```
POST /orders/{order}/returns                          orders.returns.store
POST /seller/orders/{order}/returns/{return}/approve   seller.orders.returns.approve
POST /seller/orders/{order}/returns/{return}/reject    seller.orders.returns.reject
```

`OrderPolicy` 新增 `requestReturn(User $user, Order $order)`（買家本人 + `canRequestReturn()`）與 `manageReturn(User $user, Order $order)`（賣家本人 + 有待處理退貨申請）。

### 5. 前端

不開新頁面——比照取消流程，直接在既有 `Orders/Show.vue`（買家）與 `Seller/Orders/Show.vue`（賣家）加退貨相關 UI 區塊：買家用 `DialogModal` + `useForm` 選品項/數量/填原因申請；賣家看到待處理退貨用 `DialogModal`（拒絕要填 `response_reason`）+ `useAsyncAction`（核准）處理，狀態банner 顯示已退貨品項與 `refund_amount`。元件與慣例全部沿用現有取消流程頁面已經在用的那一套（`DialogModal`/`DangerButton`/`InputError`/`useAsyncAction`）。

## Consequences

- 優點：
  - 品項/數量粒度符合真實售後場景，買家不用整單退才能退一件瑕疵品。
  - 大量複用 `OrderCancellation` 已驗證過的鎖定/冪等/通知模式，風險與心智負擔低。
  - 用「新表 + 新 timestamp/decimal 欄位」而非新 `orders.status` 值，不動既有 `STATUS_RANK`/`canTransitionStatusTo()` 的單向狀態機假設。
  - `refunded_amount` 累計欄位 + `OrderReturn` 逐筆稽核紀錄，天生對接未來「平台抽成與賣家撥款」——撥款結算時可以直接排除 `refunded_amount` 或用退貨申請視窗（`completed_at + window_days`）當作撥款的最早釋放時間點，不用回頭改資料模型。
- 缺點：
  - **部分退貨不釋放優惠券**：買家部分退貨後仍保留原折扣（因為 `coupon_redemptions` 沒有品項級的折扣拆分資料，無法精算部分歸還），只有整單全退才會釋放優惠券使用紀錄。此為 v1 刻意簡化，未來若要精算需要幫 `coupon_redemptions` 補品項級折扣快照欄位。
  - **不處理退貨物流**：系統不產生退貨寄件單/追蹤，賣家「是否已收到退回商品」的確認完全在平台外進行，按下「核准」即代表賣家已確認可以退款+加回庫存。
  - **退貨商品一律加回可售庫存**：不區分瑕疵品/可再售，沒有「不可再售」的例外處理，賣家如需下架瑕疵庫存要自行手動調整商品庫存。
  - **運費不退**：無論退貨數量多寡，訂單原始 `shipping_fee` 一律不退還。
  - **不影響雙向盲評系統**：退貨與 `ReviewService` 的冷卻/釋放完全獨立，退貨不會延後或重置評價冷卻期，兩者刻意不耦合。
  - **既有缺口，本次不修**：目前 `directCancelByBuyer()` 允許取消 `STATUS_PAID`（已付款）的訂單，但 `finalizeCancellation()` 從未呼叫任何退款邏輯——這是取消流程既有的缺口（因為 `PaymentService` 原本完全沒有退款方法）。本 ADR 只新增售後退貨的退款能力，**不**回頭幫既有取消流程接上退款，是否要讓「取消已付款訂單」也走 `simulateRefund()` 留給後續獨立評估與 ADR。

## Alternatives Considered

- **沿用/擴充 `OrderCancellation`**：欄位形狀最接近，但語意上取消是整單、售後退貨是品項級，硬塞會讓 `OrderCancellation` 多出一堆退貨專屬又對取消無意義的欄位（`refund_amount`、品項明細），且 `Order::canRequestCancellation()`/`canBeCancelledBySeller()` 目前都明確排除 `completed` 狀態，混用同一張表反而要拆狀態機，不採用。
- **整單退貨（不做品項級）**：資料模型更簡單（比照 `OrderCancellation` 整單設計），但不符合「買家只想退一件」的真實需求，且既有取消流程已經是整單模式，沒有必要再做一個功能一樣的整單退貨，不採用。
- **退貨核准時對優惠券做精確比例退回**（而非只在全退時釋放）：更精確，但 `coupon_redemptions` 目前沒有品項級折扣快照，要精算就得先幫 checkout 流程加欄位記錄每個品項分攤了多少折扣，複雜度大幅提高，且目前規模用不到這麼精細，v1 先用「只在全退時釋放」的簡化規則，之後有需求再迭代。
- **新增 `orders.status` 值（例如 `returned`/`refunded`）**：更直覺地表達訂單目前狀態，但會破壞 `STATUS_RANK` 單向前進的假設（退貨後訂單邏辑上該維持「已完成」，不是進入新狀態），且所有「訂單是否完成」的既有判斷（`isActive()`、review 相關邏輯、dashboard 統計）都要重新檢查是否該把新狀態算進去，風險面過大，不採用。
