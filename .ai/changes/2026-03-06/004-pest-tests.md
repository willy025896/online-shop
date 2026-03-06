# Change: Pest tests + bug fixes

**Date**: 2026-03-06
**Prompt**: [001-continue-progress](../../prompts/2026-03-06/001-continue-progress.md)
**Type**: Feature / Bug Fix

## Summary

新增 6 個 Pest 測試檔案共 41 個測試案例，涵蓋公開頁面、購物車、賣家功能、管理功能、訂單流程。
同時修正 2 個 bug：migration 排序問題 + Controller 缺少 AuthorizesRequests trait。

## Files Changed

| Action   | File Path                                     | Description                           |
|----------|-----------------------------------------------|---------------------------------------|
| Added    | tests/Feature/ProductTest.php                 | 商品頁面測試 (4 tests)                 |
| Added    | tests/Feature/ShopTest.php                    | 店鋪頁面測試 (2 tests)                 |
| Added    | tests/Feature/CartTest.php                    | 購物車測試 (2 tests)                   |
| Added    | tests/Feature/SellerTest.php                  | 賣家功能測試 (11 tests)                |
| Added    | tests/Feature/AdminTest.php                   | 管理功能測試 (16 tests)                |
| Added    | tests/Feature/OrderTest.php                   | 訂單測試 (6 tests)                     |
| Modified | app/Http/Controllers/Controller.php           | Bug fix: 加入 AuthorizesRequests trait |
| Renamed  | database/migrations/*_create_products_table   | Bug fix: 修正 migration 排序           |
| Renamed  | database/migrations/*_create_carts_table      | Bug fix: 修正 migration 排序           |
| Renamed  | database/migrations/*_create_orders_table     | Bug fix: 修正 migration 排序           |

## Testing

- **Tests Added**: Yes (41 new tests)
- **Tests Passed**: 71 passed, 0 failed (including existing Jetstream tests)
- **Manual Testing**: N/A

## Related Decisions

- N/A

## Breaking Changes

- Migration files renamed (parent tables now run before child tables)
