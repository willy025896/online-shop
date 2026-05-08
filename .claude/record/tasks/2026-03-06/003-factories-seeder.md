---
id: 2026-03-06/003
type: Feature
status: Done
---

# Task: Database Factories + Seeder

## Request

接續 progress.md Stage 5：建立所有 Model 的 Factory 與完整的 DatabaseSeeder，可產生測試用資料集。

## Changes

| File | Action | Notes |
|------|--------|-------|
| database/factories/UserFactory.php | Modified | 新增 role, phone 欄位 + seller/admin states |
| database/factories/ShopFactory.php | Added | Shop factory + pending/suspended states |
| database/factories/CategoryFactory.php | Added | Category factory + inactive state |
| database/factories/ProductFactory.php | Added | Product factory + draft/inactive/outOfStock states |
| database/factories/OrderFactory.php | Added | Order factory + paid/completed/cancelled states |
| database/seeders/DatabaseSeeder.php | Modified | 完整 seeder: admin + customers + sellers + shops + products + orders |

## Outcome

5 個 Factory 建立完成；DatabaseSeeder 可產生：1 admin、1 test customer、4 root + 12 sub categories、5 approved + 1 pending shops、每店 8-15 active products、10 customers 各 0-4 orders。
