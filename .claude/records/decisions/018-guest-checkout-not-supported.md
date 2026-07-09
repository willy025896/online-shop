---
id: ADR-018
title: 不支援訪客結帳，但保留訪客購物車
date: 2026-07-09
status: Accepted
---

# ADR-018: 不支援訪客結帳，但保留訪客購物車

## Context

`CartService` 原生就支援 guest（以 `session_id` 識別）與登入使用者（以 `user_id` 識別）雙軌購物車，`/cart` 系列路由也開放給未登入使用者使用。但 `/checkout` 系列路由（`checkout.index`/`checkout.store`/`checkout.coupon.preview`）一直放在 `auth:sanctum` middleware 群組內，等於購物車支援訪客、但結帳強制要求登入。討論是否要補上完整的訪客結帳流程。

## Decision

**不實作訪客結帳**，`/checkout` 維持要求登入。**訪客購物車維持現狀，不拿掉。**

不做訪客結帳的原因：目前多個核心模組的資料模型與商業邏輯都直接綁定在已登入的 `User` 上，要支援訪客結帳需要動到的範圍遠大於「把 middleware 移掉」：

- `OrderService::createOrdersFromCart` 直接把 `(int) $cart->user_id` 寫進 `orders.user_id`（non-nullable）。
- 優惠券的 `per_user_limit`（見 ADR-008）、雙向盲評（見 ADR-006）、9 類 mail 通知的 `Notifiable`/`HasLocalePreference` pipeline（見 ADR-016）全部假設訂單背後有一個真實的 `User`。
- 訪客沒有帳號，勢必需要另一套「訂單編號 + email 查詢」的訂單查詢流程，取代現有 `/orders` 列表頁。

保留訪客購物車的原因：`/cart` 與 `/checkout` 是兩個獨立的摩擦點，讓使用者在「加入購物車」這種低承諾動作上不被強迫登入，只在「送出訂單」這個高承諾動作才要求登入，是常見且合理的電商模式。`CartService::mergeGuestCart()` 已經處理好登入當下把 guest 購物車併入使用者購物車，體驗是連貫的，沒有必要為了「不做訪客結帳」而連帶砍掉訪客購物車。

## Consequences

- 優點：
  - 不需要改動 `orders.user_id`、優惠券/評價/通知系統既有的「訂單必屬於一個 User」假設，維持現有架構單純。
  - 訪客仍可自由瀏覽、加入購物車，只有結帳這一步才需要登入，UX 上不是「一開始就強迫註冊」。
- 缺點：
  - 未登入使用者在購物車頁按下「結帳」會被導去登入頁，可能造成部分使用者在最後一步放棄（轉換率損失）——這是刻意接受的取捨，非架構限制。

## Alternatives Considered

- **完整訪客結帳**（訂單支援 nullable `user_id` + 訪客 email/姓名快照、訂單編號+email 查詢、優惠券/評價/通知對訪客訂單分別處理或跳過）：技術上可行，但牽動範圍涵蓋 Coupon、Review、Notification 三大既有模組，工程成本與既有架構複雜度不成比例，暫不採用。若未來轉換率數據顯示強烈需求，可重新評估。
- **拿掉訪客購物車，購物車也要求登入**：會讓「加入購物車」這個最低摩擦的動作也被迫登入，體驗只會更差，且與目前決定不做訪客結帳的取捨方向矛盾，不採用。
