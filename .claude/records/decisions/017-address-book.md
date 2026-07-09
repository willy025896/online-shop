---
id: ADR-017
title: 地址簿（常用收件地址）
date: 2026-07-09
status: Accepted
---

# ADR-017: 地址簿（常用收件地址）

## Context

`CheckoutController`/`orders` 表的 `shipping_name`/`shipping_phone`/`shipping_address` 一直是每次結帳都要手動輸入的自由文字欄位，完全沒有「常用地址」可選、沒有 `Address` model（README.md 已記錄這個缺口）。這次補上完整的地址簿功能：獨立的地址簿管理頁面（新增/編輯/刪除/設預設），並與結帳頁串接（可挑選常用地址、也可在結帳時把手動輸入的地址另存起來）。

## Decision

### 1. `Address` 與 `Order` 不建外鍵，Order 收件資訊維持「快照」

比照 `order_items.product_name`、`orders.coupon_code` 的既有慣例——訂單上的收件資訊代表「下單當下」的事實，不該因為買家事後編輯/刪除地址簿裡的某筆地址而改變歷史訂單的內容。因此：

- `orders` 表**不新增** `address_id` 欄位。
- 結帳頁選擇常用地址純粹是**前端行為**：點選地址卡片後把 `recipient_name`/`phone`/`address` 填入既有的 `shipping_name`/`shipping_phone`/`shipping_address` 文字欄位，`CheckoutController::store()` 完全不需要認得「這次是用哪一筆地址」。
- 結帳時要「另存為常用地址」是獨立的 `save_address` boolean 參數，成功建立訂單後才呼叫 `AddressService::create()`，避免結帳失敗（例如優惠券驗證失敗、庫存不足）卻已經寫入了一筆地址。

### 2. 預設地址互斥邏輯集中在 `AddressService`，用 transaction + `lockForUpdate` 保護

一個使用者同時最多只能有一筆 `is_default = true` 的地址。所有會影響這個不變量的操作（`create`/`update`/`delete`/`setDefault`）都包在 `DB::transaction` 內，用 `lockForUpdate()` 鎖住該使用者其他地址列再清空 `is_default`，避免併發請求（例如同時把兩筆地址都設成預設）造成資料不一致——比照 `CouponService::redeem()`、`Order::lockForUpdate()` 的既有鎖定慣例。

第一筆地址會自動設為預設（`create()` 內判斷 `$user->addresses()->exists()`），刪除預設地址後若還有其他地址則自動升級最新一筆為預設，減少「使用者忘記設預設」的體驗落差。

### 3. `label` 是自由文字，不是 model 常數

地址標籤（家/公司/其他）純粹是使用者自訂的顯示用文字，不驅動任何後端流程分支（不像 `Order::STATUS_*`、`Shop::STATUS_*` 會決定狀態機轉換規則）。前端在 `AddressForm.vue` 提供三個快選 chip 方便輸入，但欄位本身是 nullable string，使用者可以輸入任意自訂標籤（例如「爸媽家」），不受限於固定列舉。

### 4. 地址簿管理頁面獨立於 `/addresses`，不塞進 Jetstream 的 `/profile`

`resources/js/Pages/Profile/Show.vue` 是 Jetstream 的預設 scaffold 頁面，把自訂功能塞進去會增加未來 Jetstream 升級時的衝突面。改為獨立的 `AddressController`（頂層，不在 Seller/Admin 命名空間，因為這是買家個人功能）+ `resources/js/Pages/Addresses/` 頁面組，比照 `Seller/Coupons/`（Index + Create + Edit + 共用 `Partials/AddressForm.vue`）的既有模式，並在 `AppLayout.vue` 帳號選單加一個「地址簿」連結。

## Consequences

- 優點：
  - Order 的收件資訊快照語意完全不受影響，既有的售後退貨/退款、通知等依賴 `orders.shipping_*` 欄位的邏輯零改動。
  - `AddressService` 是預設地址互斥規則的唯一入口，controller 不需要重複寫鎖定邏輯。
  - 地址簿頁面獨立於 Jetstream scaffold，未來 Jetstream 升級不會有衝突風險。
- 缺點：
  - 結帳頁選擇地址與後端完全解耦，代表如果使用者選了地址 A 之後又手動修改了地址欄位，後端無法得知這筆訂單「原本」對應哪一筆常用地址——但這本來就是快照設計的預期行為，不是缺陷。
  - 目前沒有地址筆數上限，理論上使用者可以無限新增；因為是個人資料、風險低，暫不加限制。

## Alternatives Considered

- **`orders.address_id` 外鍵 + nullable**：讓訂單可以回溯到當初選的是哪一筆地址。放棄的原因是這會破壞既有的「訂單收件資訊是不可變快照」設計慣例，且地址被刪除或編輯後外鍵語意會變得混亂（究竟該用 `nullOnDelete` 顯示「地址已刪除」還是複製一份快照欄位），複雜度遠大於效益。
- **地址簿頁面整合進 Jetstream `/profile`**：UI 集中、少一個導覽項目，但會直接修改 Jetstream 的 scaffold 頁面，增加未來框架升級的維護成本，放棄。
