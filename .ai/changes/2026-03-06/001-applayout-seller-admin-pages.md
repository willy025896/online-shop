# Change: AppLayout 更新 + Seller/Admin 頁面

**Date**: 2026-03-06
**Prompt**: [001-continue-progress](../../prompts/2026-03-06/001-continue-progress.md)
**Type**: Feature

## Summary

完成 progress.md 中 Stage 1-3 待辦項目：
1. AppLayout 新增 cart badge、Products/Shops/Orders 導航、Seller/Admin Panel 連結
2. 7 個 Seller 頁面 (Dashboard, Register, Products CRUD, Orders, Shop Edit)
3. 6 個 Admin 頁面 (Dashboard, Users, Shops, Categories, Orders, Products)

## Files Changed

| Action   | File Path                                      | Description                     |
|----------|-------------------------------------------------|---------------------------------|
| Modified | resources/js/Layouts/AppLayout.vue              | 新增 cart badge, nav links, role-based panel links |
| Added    | resources/js/Pages/Seller/Dashboard.vue         | Seller dashboard with stats     |
| Added    | resources/js/Pages/Seller/Register.vue          | Seller registration form        |
| Added    | resources/js/Pages/Seller/Products/Index.vue    | Products list table             |
| Added    | resources/js/Pages/Seller/Products/Create.vue   | Product creation form           |
| Added    | resources/js/Pages/Seller/Products/Edit.vue     | Product edit form + image upload |
| Added    | resources/js/Pages/Seller/Orders/Index.vue      | Orders list table               |
| Added    | resources/js/Pages/Seller/Orders/Show.vue       | Order detail + status update    |
| Added    | resources/js/Pages/Seller/Shop/Edit.vue         | Shop settings form              |
| Added    | resources/js/Pages/Admin/Dashboard.vue          | Admin dashboard with stats      |
| Added    | resources/js/Pages/Admin/Users/Index.vue        | User list + role management     |
| Added    | resources/js/Pages/Admin/Shops/Index.vue        | Shop list + approve/suspend     |
| Added    | resources/js/Pages/Admin/Categories/Index.vue   | Category CRUD (inline form)     |
| Added    | resources/js/Pages/Admin/Orders/Index.vue       | Orders list (read-only)         |
| Added    | resources/js/Pages/Admin/Products/Index.vue     | Products list (read-only)       |

## Testing

- **Tests Added**: No
- **Tests Passed**: N/A
- **Manual Testing**: Vite build passes successfully

## Related Decisions

- N/A

## Breaking Changes

- AppLayout nav links changed: removed Members and Advertisement links from primary nav, added Products, Shops, Orders