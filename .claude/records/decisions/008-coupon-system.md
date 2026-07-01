---
id: ADR-008
title: 折扣碼系統 — 賣家 per-shop 折扣碼、結帳每賣場套用
date: 2026-07-01
status: Accepted
---

# ADR-008: 折扣碼系統 — 賣家 per-shop 折扣碼、結帳每賣場套用

## Context

平台缺少促銷能力（README TODO「折扣碼/優惠券」）。訂單在結帳時已**依賣場拆單**（`OrderService::createOrdersFromCart` 用 `groupBy('product.shop_id')`，每賣場一張 `Order`），運費也已是 per-shop 計算（ADR-007）。折扣碼需要決定：擁有權（誰建立）、套用範圍、折抵基礎、以及如何與既有拆單/交易流程整合。

## Decision

- **擁有權：賣家 per-shop**。`coupons.shop_id` 設為 **nullable**——非空 = 賣家賣場碼（v1）、null = 全站碼（未來 admin 用，資料層已支援、屆時不必改 schema）。
- **套用：結帳每賣場各一碼**，各自折抵該賣場子訂單；天然對齊 per-shop 拆單。
- **折抵基礎：僅賣場商品小計**，`total = subtotal - discount + shipping_fee`；運費不折，且**免運門檻仍以折扣前小計判定**。
- **`CouponService` 為唯一真相來源**（比照 `ShippingService`）：`validate()`（回帶原因碼的 `CouponException`）、`discountFor()`（百分比含 `max_discount` 上限、clamp 不超過小計）、`redeem()`（於呼叫端交易內 `lockForUpdate` 重驗次數、`increment('used_count')` 並寫 `coupon_redemptions`）。
- **後端為準**：結帳頁 `POST /checkout/coupon/preview` 僅供即時預覽（小計由伺服器端從真實購物車計算，不信任前端）；`OrderService` 於建單交易內**重新驗證並重算**，失效碼丟例外、整筆結帳 rollback（比照庫存不足）。
- **快照**：`orders.coupon_code` 存下當下碼字串（比照 `order_items.product_name`），日後改碼/刪碼不影響歷史訂單顯示。
- 併發：`usage_limit` / `per_user_limit` 於 `redeem` 內鎖定重驗（比照 `OrderService` 對 `Product::lockForUpdate()` 扣庫存）。

## Consequences

- 優點：規則收斂於 `CouponService`；per-shop 套用與既有拆單/運費一致；`shop_id` nullable 讓未來全站碼零 schema 異動。
- 優點：redeem 於建單同一交易內，計數與訂單原子提交；失效碼安全 rollback。
- 缺點：v1 結帳 UI 每賣場一碼，多賣場時操作稍繁；前端預覽與後端各持驗證入口（已用同一 `CouponService` 對齊，並有測試守後端）。
- 缺點：目前無 admin 全站碼 CRUD（僅資料層預留）；折扣不作用於運費（如「免運券」需未來擴充折抵目標）。

## Alternatives Considered

- **全站碼（admin 建立）**：需決定多賣場訂單如何分攤折扣，較複雜；改以 nullable `shop_id` 預留，未來加 admin CRUD 即可。
- **整筆一碼**：UI 較簡單，但與 per-shop 拆單/運費不一致，且難表達「某賣場的活動」；不採用。
- **折抵含運費**：需在 `CouponService` 增加折抵目標欄位與規則，v1 先只折商品小計。
