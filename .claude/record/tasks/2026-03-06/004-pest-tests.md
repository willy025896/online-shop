---
id: 2026-03-06/004
type: Feature
status: Done
---

# Task: Pest 測試 + Migration Bug 修正

## Request

接續 progress.md Stage 6：新增 Pest 測試涵蓋主要功能，並修正 migration 排序問題與 Controller 缺少 AuthorizesRequests trait。

## Changes

| File | Action | Notes |
|------|--------|-------|
| tests/Feature/ProductTest.php | Added | 商品頁面測試 (4 tests) |
| tests/Feature/ShopTest.php | Added | 店鋪頁面測試 (2 tests) |
| tests/Feature/CartTest.php | Added | 購物車測試 (2 tests) |
| tests/Feature/SellerTest.php | Added | 賣家功能測試 (11 tests) |
| tests/Feature/AdminTest.php | Added | 管理功能測試 (16 tests) |
| tests/Feature/OrderTest.php | Added | 訂單測試 (6 tests) |
| app/Http/Controllers/Controller.php | Modified | Bug fix: 加入 AuthorizesRequests trait |
| database/migrations/*_create_products_table | Renamed | Bug fix: 修正 migration 排序 |
| database/migrations/*_create_carts_table | Renamed | Bug fix: 修正 migration 排序 |
| database/migrations/*_create_orders_table | Renamed | Bug fix: 修正 migration 排序 |

## Outcome

41 個新測試案例，71 passed, 0 failed（含既有 Jetstream 測試）。Migration 排序問題修正（parent tables 先於 child tables 執行）。
