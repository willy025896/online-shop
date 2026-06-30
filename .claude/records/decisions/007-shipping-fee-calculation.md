---
id: ADR-007
title: 運費計算 — 全站統一固定費率 + 滿額免運
date: 2026-06-30
status: Accepted
---

# ADR-007: 運費計算 — 全站統一固定費率 + 滿額免運

## Context

結帳的 `shipping_fee` 原本在四處硬編碼為 `0`（`CheckoutController::index`、`OrderService::createOrdersFromCart`、`CartService::calculateTotals`、前端 `Cart/Index.vue`）。需要實作真正的運費計算。

訂單在結帳時已**依賣場拆分**（`OrderService` 用 `groupBy('product.shop_id')`，每賣場一張 `Order`），因此運費的自然計算單位是「每賣場 / 每訂單」。

商品目前**沒有重量欄位**，依重量計費需先擴充資料模型，成本較高。

## Decision

採用**全站統一**的運費規則，而非各賣場自訂或依重量計費：

- 設定集中在 `config/shipping.php`：`flat_fee`（預設 100）、`free_threshold`（預設 1000），皆可用 env 覆寫（`SHIPPING_FLAT_FEE`、`SHIPPING_FREE_THRESHOLD`）。
- 規則：**每賣場小計 ≥ free_threshold → 免運；否則收 flat_fee**。`free_threshold` 設為 null 即停用免運。
- 新增 `ShippingService::feeForSubtotal(float): float` 作為**唯一計算來源**，後端建立訂單、購物車彙總、結帳頁與前端預估全部走同一規則。
- 後端為真實來源，前端 `shippingConfig` 僅供即時預估顯示。

## Consequences

- 優點：規則單一、改動最小、無 migration 與賣家後台 UI；計算邏輯收斂在 `ShippingService`，未來要改規則只動一處。
- 優點：per-shop 計算與既有的 per-shop 訂單拆分天然一致。
- 缺點：賣家無法自訂運費（多商家平台的真實情境受限）。
- 缺點：前端預估與後端各持一份規則實作，需留意兩邊同步（已用相同 per-shop 分組邏輯對齊，並有測試守住後端）。

## Alternatives Considered

- **各賣場自訂運費**：在 `shops` 加欄位 + 賣家後台 UI。較貼近多商家真實情境，但工作量大；先以全站統一上線，未來可平滑升級（`ShippingService` 改讀 shop 設定即可）。
- **依重量計費**：需在 `products` 加 `weight` 並改商品建立/編輯表單與既有資料回填，成本最高，暫不採用。
