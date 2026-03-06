# Change: Database factories + seeder

**Date**: 2026-03-06
**Prompt**: [001-continue-progress](../../prompts/2026-03-06/001-continue-progress.md)
**Type**: Feature

## Summary

建立所有 Model 的 Factory 與完整的 DatabaseSeeder，可產生完整的測試資料集。

## Files Changed

| Action   | File Path                              | Description                          |
|----------|----------------------------------------|--------------------------------------|
| Modified | database/factories/UserFactory.php     | 新增 role, phone 欄位 + seller/admin states |
| Added    | database/factories/ShopFactory.php     | Shop factory + pending/suspended states |
| Added    | database/factories/CategoryFactory.php | Category factory + inactive state    |
| Added    | database/factories/ProductFactory.php  | Product factory + draft/inactive/outOfStock states |
| Added    | database/factories/OrderFactory.php    | Order factory + paid/completed/cancelled states |
| Modified | database/seeders/DatabaseSeeder.php    | 完整 seeder: admin + customers + sellers + shops + products + orders |

## Seed Data

- 1 Admin user (admin@example.com)
- 1 Test customer (test@example.com)
- 4 root categories + 12 subcategories
- 5 approved shops + 1 pending shop
- 每店 8-15 active + 1-3 draft products
- 10 customers 各 0-4 orders with items

## Testing

- **Tests Added**: No
- **Tests Passed**: N/A
- **Manual Testing**: PHP syntax check passed

## Related Decisions

- N/A

## Breaking Changes

- None
