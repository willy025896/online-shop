---
id: ADR-014
title: 平台抽成與賣家撥款（Platform Commission & Seller Payout）
date: 2026-07-07
status: Accepted
---

# ADR-014: 平台抽成與賣家撥款（Platform Commission & Seller Payout）

## Context

目前 `Order`/`Coupon` 等 model 完全沒有 commission/platform_fee/payout 欄位——平台不會從交易抽成，也沒有任何機制把「賣家應得的錢」結算、撥款給賣家。金流本身仍是 `PaymentService::simulatePayment()`/`simulateRefund()` 模擬付款/退款（見 ADR-013），真實金流串接（綠界/藍新/Stripe）與「取消已付款訂單無退款邏輯」的既有缺口，使用者已明確表示晚點再一起處理，不在本 ADR 範圍內。

本 ADR 只解決「平台如何知道該付賣家多少錢、何時可以付」這一層平台內部記帳問題，先不碰真實資金移動（撥款動作本身也是模擬，比照 `simulatePayment`/`simulateRefund` 的模式，日後串接真實金流/銀行轉帳 API 時再替換內部實作）。

ADR-013 已經預留了對接點：`orders.refunded_amount` 累計欄位 + 7 天退貨申請視窗（`config('returns.window_days')`），可以直接作為「這筆訂單可以撥款」的資格判斷與淨額計算基礎，不需要回頭改資料模型。

使用者對兩個開放問題的決策（見 clarifying questions）：
1. **抽成費率**：v1 用**全平台統一費率**（`config('commission.rate')`），不做每賣場個別費率。
2. **撥款觸發方式**：**Admin 後台手動觸發**，不做排程自動撥款。

## Decision

### 1. 抽成費率：`config/commission.php`

```php
return [
    'rate' => (float) env('PLATFORM_COMMISSION_RATE', 0.05),
];
```

比照 `config/shipping.php`/`config/inventory.php`/`config/returns.php` 的 config-driven 慣例，全平台單一費率，透過 env 覆寫。**只對商品淨營收抽成**——比照既有「折扣只影響商品 subtotal、運費不打折」的慣例（見 ADR-007/008），抽成也**不動運費**，運費 100% 轉付給賣家。

### 2. 資料模型：`Payout` + `PayoutItem`

**`payouts`**（一次撥款批次，對應一個賣場一次 admin 觸發）：
- `shop_id` FK → `shops`，cascadeOnDelete
- `gross_amount` decimal(10,2)：本次批次商品淨營收合計（`subtotal - discount - refunded_amount`，加總）
- `commission_amount` decimal(10,2)：本次批次平台抽成合計
- `shipping_amount` decimal(10,2)：本次批次運費合計（全額轉付賣家，僅供對帳顯示）
- `net_amount` decimal(10,2)：本次批次實際撥款金額（`gross_amount - commission_amount + shipping_amount`）
- `paid_at` timestamp（觸發當下即視為已撥款——v1 沒有 pending/processing 中間狀態，比照 `simulatePayment()` 立即完成的做法）
- `timestamps()`

**`payout_items`**（品項粒度：每筆訂單在被撥款當下的金額快照）：
- `payout_id` FK → `payouts`，cascadeOnDelete
- `order_id` FK → `orders`，**unique**（一筆訂單一輩子只能被撥款一次，資料庫層防止重複撥款）
- `gross_amount` / `commission_amount` / `shipping_amount` / `net_amount` decimal(10,2)：這筆訂單當下的金額拆解快照
- `timestamps()`

金額全部在 `PayoutItem` 逐筆快照，而非事後從 `Order` 即時反算——比照 `order_items.product_name`/`orders.coupon_code` 的快照慣例：日後 `commission.rate` 調整不會讓歷史撥款金額跟著變動。

`Payout::items(): HasMany`、`Payout::shop(): BelongsTo`；`PayoutItem::order(): BelongsTo`；`Order::payoutItem(): HasOne`（用來判斷「這筆訂單是否已撥款過」）；`Shop::payouts(): HasMany`。

### 3. 撥款資格與 `PayoutService`

新增 `PayoutService`（單一入口，比照 `CouponService`/`ShippingService` 的 service 慣例）：

```php
public function eligibleOrders(Shop $shop): Collection
{
    return Order::where('shop_id', $shop->id)
        ->where('status', Order::STATUS_COMPLETED)
        ->whereNotNull('completed_at')
        ->where('completed_at', '<=', now()->subDays(config('returns.window_days')))
        ->whereDoesntHave('payoutItem')
        ->whereDoesntHave('returns', fn ($q) => $q->where('status', OrderReturn::STATUS_REQUESTED))
        ->get();
}
```

撥款資格四條件：
1. `status === STATUS_COMPLETED`
2. `completed_at` 已超過退貨申請視窗（`completed_at + config('returns.window_days') <= now()`）——**這是安全撥款的關鍵不變量**：一旦超過退貨視窗，`Order::canRequestReturn()` 就不會再放行新的退貨申請，代表這筆訂單的金額從此不會再變動，撥款後不需要任何「已撥款訂單被追加退貨」的追溯處理。
3. **沒有**尚在 `requested` 狀態的退貨申請——避免視窗邊界卡著一筆賣家還沒審核完的退貨就先撥款。
4. **尚未**被任何 `PayoutItem` 撥過款（`whereDoesntHave('payoutItem')`，加上資料庫 `unique(order_id)` 雙重防線）。

`generateForShop(Shop $shop): ?Payout`：`DB::transaction` 內對符合資格的訂單 `lockForUpdate()`（防止同時間兩個 admin 重複觸發），逐筆訂單算 `goodsRevenue = max(0, subtotal - discount - refunded_amount)`、`commission = round(goodsRevenue * config('commission.rate'), 2)`、`net = round(goodsRevenue - commission + shipping_fee, 2)`，建立 `Payout` + 逐筆 `PayoutItem`，回傳 `Payout`；沒有符合資格的訂單則回傳 `null`（冪等，比照 `requestCancellation()` 等既有 service 方法「沒有可處理的就靜默略過」慣例）。

`generateForAllShops(): Collection`：只掃 `Shop::STATUS_APPROVED` 的賣場（停權賣場即使有符合資格的已完成訂單也不自動撥款，需 admin 個別處理），逐賣場呼叫 `generateForShop()`，過濾掉 `null`。

### 4. Admin 觸發（手動）

```
GET  /admin/payouts        admin.payouts.index   — 列出歷史撥款紀錄 + 每個賣場目前「可撥款但尚未撥款」的預覽金額
POST /admin/payouts/run    admin.payouts.run     — 觸發 generateForAllShops()
```

`Admin\PayoutController::index()` 額外用 `eligibleOrders()` 算出每個賣場的預覽合計（不建立任何紀錄，純顯示），讓 admin 觸發前能看到這次會撥多少錢給誰。`store()`/`run()` 呼叫 `generateForAllShops()`，對每一筆新建立的 `Payout` 呼叫 `AdminAuditLogger::log($admin, 'payout.generated', $payout, ['shop_id' => ..., 'net_amount' => ...])`（比照 ADR-009 既有的 admin 操作稽核慣例）。

### 5. 通知

`PayoutCompletedNotification` → 賣家，`type: 'order.payout_completed'`（沿用既有 `{type,title,body,url,meta}` 慣例，`meta` 帶 `net_amount`/`payout_id`），`via() => ['database','broadcast']`，`use BroadcastsAsArray, Queueable`。撥款批次建立成功後在 `PayoutService::generateForShop()` 內對賣場擁有者發送。

### 6. 賣家頁面（唯讀）

新增 `Seller\PayoutController::index()` → `Seller/Payouts/Index.vue`：列出自己賣場的歷史撥款紀錄（`Payout` 分頁列表，展開可看 `PayoutItem` 逐筆訂單明細）。純顯示，沒有任何賣家可操作的動作（撥款完全由 admin 觸發，賣家只能看結果）。

## Consequences

- 優點：
  - 完全複用 ADR-013 預留的對接點（`refunded_amount`、退貨視窗），不需要改 `Order`/`OrderReturn` 既有欄位或狀態機。
  - `PayoutItem` 逐筆金額快照 + 資料庫 `unique(order_id)`，杜絕重複撥款，且歷史撥款金額不受未來費率調整影響。
  - Admin 手動觸發降低 v1 風險——在真實金流串接前，撥款只是平台內部記帳，不會有「自動誤觸發」的疑慮；之後若要改排程自動化，只需新增一個呼叫 `generateForAllShops()` 的 Artisan command，`PayoutService` 本身不用改。
- 缺點：
  - **全平台統一費率，不支援每賣場個別議價費率**——大型賣家若談了特殊費率，目前無法表達，需要之後另外設計 `shops.commission_rate` 覆寫欄位（v1 刻意簡化，見 Alternatives Considered）。
  - **沒有撥款反轉/追討機制**——訂單一旦撥款，理論上不會再變動（見上述關鍵不變量），但若日後發生詐欺/爭議需要追討已撥款項，目前沒有任何 clawback 流程，需要另外設計。
  - **停權賣場的已完成訂單會卡住不撥款**——`generateForAllShops()` 只掃 `STATUS_APPROVED` 賣場，若一個賣場在訂單完成後才被停權，該訂單會一直卡在「符合資格但沒被自動撥款」。v1 只把 `PayoutService::generateForShop($shop)` 設計成可被單一賣場呼叫，但**目前沒有對應的 Admin 路由/按鈕**，實務上只能透過 `php artisan tinker` 手動呼叫，不是一個真正可從後台操作的補救路徑——之後若要補，應該是在 Admin 賣場列表或撥款頁面加一個「針對此賣場立即撥款」的動作。
  - **撥款本身仍是模擬**——`Payout`/`PayoutItem` 只是平台內部記帳，沒有任何真實資金移動（銀行轉帳/API 呼叫）。真實金流串接時，「建立 = 撥款成功」這個假設不只是加一個 `status` 欄位就能解決：`payout_items.order_id` 的 DB unique 限制與 `whereDoesntHave('payoutItem')` 資格判斷都預設「有記錄 = 已撥款」，若要支援「撥款失敗/待重試」，這兩處都要一併改成感知 `status` 的版本（例如 unique 限制排除 `failed` 狀態），加上撥款完成通知的觸發時機也要從「建立當下」延後到「金流回調確認成功」——是比表面看起來更大的一次重構，先在此明確記錄，避免日後低估工作量。

## Alternatives Considered

- **每賣場個別抽成費率**（`shops.commission_rate` nullable 覆寫全域預設，比照 coupon 的 `shop_id` nullable 設計）：更貼近真實市場需求（大賣家議價），但需要 Admin 介面管理每個賣場的費率、且目前沒有實際的差異化需求，v1 用全平台統一費率，之後有需求再迭代（使用者已確認選擇統一費率）。
- **排程自動撥款**（比照 `reviews:release` 用 `Schedule::command(...)->everyWeek()`）：不需要人工介入，但撥款涉及真實金錢移動的記帳起點，在真實金流尚未串接前，先讓 admin 保有手動控制權更保守；且改自動化的成本很低（只是加一個呼叫既有 `PayoutService::generateForAllShops()` 的 command），先手動不影響未來擴充（使用者已確認選擇手動觸發）。
- **撥款時即時反算金額（不逐筆快照）**：省去 `PayoutItem` 的欄位設計，但無法保證歷史撥款金額不受後續費率調整影響，且無法對帳「這筆訂單當初實際撥了多少」，不採用。
- **用 `orders.payout_id` 取代獨立 `PayoutItem` 表**：資料模型更簡單（少一張表），但無法表達「一個 Payout 對應多筆訂單」的聚合關係，且無法存下逐筆的金額快照（`orders` 表沒有適合放 commission/net 拆解的欄位，硬塞會讓 `orders` 表混入撥款專屬概念），不採用。
