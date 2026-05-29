---
id: ADR-006
title: 雙向盲評系統設計
date: 2026-05-29
status: Accepted
---

# ADR-006: 雙向盲評系統設計

## Context
需要實作買家評商品、賣家評買家的雙向評價機制。核心挑戰：
1. 防止後評者看到前評者的星等而報復（盲評）
2. 防止「使用者正在修改，對方剛送出導致立即公開」的競態（Race Condition）
3. 公開後永久關閉窗口，不得補評或修改

## Decision

### 兩張獨立評論表
- `product_reviews`（買家評商品，unique on order_item_id）
- `buyer_reviews`（賣家評買家，unique on order_id）

未使用 polymorphic，原因：聚合目標不同（product vs shop/user）、UI 顯示位置不同、星等語意不同。

### 公開狀態由 `orders` 控制
在 `orders` 表新增兩欄：
- `review_cooling_until`：冷靜期截止時間（雙方都送出後加 24h）
- `review_released_at`：公開時間（NOT NULL = 永久關閉）

所有「是否可新建/修改/刪除」均查 `order.review_released_at IS NULL`，單一真相來源，不分散在 review 表。

### 冷靜期設計
雙方都送出後才觸發 24 小時冷靜期（不是立即公開），給雙方明確窗口修改：
- 觸發在 `ReviewService::submit()` 內，包 `DB::transaction` + `lockForUpdate`
- 防止競態：edit 端也用 `lockForUpdate` 讀 order 再檢查 released_at

### 聚合欄位 denormalized
products.reviews_count / rating_sum、shops.reviews_count / rating_sum、users.buyer_reviews_count / buyer_rating_sum 在 release 時一次性更新，不在 cooling 期間累計（防止盲評期洩漏資訊）。

排程 `reviews:release`（每 10 分鐘）處理兩種 case：
1. `cooling_until <= now()` → 正常公開
2. 訂單完成 >= 14 天且 `released_at IS NULL` → 超時強制公開（含單方或無評論）

## Consequences
- 優點：單一真相來源（order）、競態安全（DB lock）、聚合效能好（無 JOIN）、冷靜期 UX 友善
- 缺點：排程每 10 分鐘才公開（非即時）、orders 表增加 2 個欄位、賣家評論依附訂單（不能獨立於訂單評論）

## Alternatives Considered
- **立即公開**：不需排程，但無法防盲評期資訊洩漏
- **Polymorphic 單表**：更靈活但 JOIN 複雜、聚合難處理
- **released_at 放在 review 表**：分散真相，需雙表判斷，容易產生不一致
