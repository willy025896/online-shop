---
id: 2026-05-29/002
type: Feature
status: Done
---

# Task: 產品推薦 / 相關商品（多訊號）

## Request
實作 README TODO 的「產品推薦/相關商品」：商品頁顯示相關商品以增加交叉銷售。
使用者選定「多訊號 + 一起購買」策略，預設排除 cancelled 訂單、露出 4 筆。

## Changes
| File | Action | Notes |
|------|--------|-------|
| app/Services/RecommendationService.php | Added | `relatedTo(Product, limit)`：一起購買(order_items 共現) → 同分類(評分/featured 加權) → 同賣場 遞補；去重、排除自己/inactive/缺貨、補滿 N 筆 |
| app/Http/Controllers/ProductController.php | Modified | `show()` 改注入 `RecommendationService`，移除原本內嵌的同分類查詢 |
| tests/Feature/RecommendationTest.php | Added | 6 個 Pest 測試（共現排序、忽略 cancelled、同分類/同賣場遞補、排除規則、limit 上限） |
| README.md | Modified | TODO 勾選「產品推薦/相關商品」 |

## Outcome
- `RecommendationService` 以三段式 fallback 產生相關商品，全部沿用既有資料表，無新 migration。
- 共現訊號用 `COUNT(DISTINCT order_id)` 排序、排除 `cancelled` 訂單；over-fetch 後再以 active+in-stock 過濾並保留排序。
- 前端 `Products/Show.vue` 既有渲染不需改動；新查詢額外 eager load `shop`（原本只載 primaryImage，賣場名稱會空白）已修正。
- 測試：RecommendationTest 6 passed、ProductTest 4 passed（無回歸）。Pint 通過。

## Decision
無 ADR：一般功能開發，未引入新套件或改變資料模型。
