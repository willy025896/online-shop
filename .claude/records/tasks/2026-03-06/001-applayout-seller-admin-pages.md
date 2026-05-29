---
id: 2026-03-06/001
type: Feature
status: Done
---

# Task: AppLayout 更新 + Seller/Admin 頁面

## Request

接續 progress.md Stage 1-3：更新 AppLayout（cart badge、導覽連結、角色面板入口），建立 7 個 Seller 頁面與 6 個 Admin 頁面。

## Changes

| File | Action | Notes |
|------|--------|-------|
| resources/js/Layouts/AppLayout.vue | Modified | 新增 cart badge、nav links、role-based panel links |
| resources/js/Pages/Seller/Dashboard.vue | Added | Seller dashboard with stats |
| resources/js/Pages/Seller/Register.vue | Added | Seller registration form |
| resources/js/Pages/Seller/Products/Index.vue | Added | Products list table |
| resources/js/Pages/Seller/Products/Create.vue | Added | Product creation form |
| resources/js/Pages/Seller/Products/Edit.vue | Added | Product edit form + image upload |
| resources/js/Pages/Seller/Orders/Index.vue | Added | Orders list table |
| resources/js/Pages/Seller/Orders/Show.vue | Added | Order detail + status update |
| resources/js/Pages/Seller/Shop/Edit.vue | Added | Shop settings form |
| resources/js/Pages/Admin/Dashboard.vue | Added | Admin dashboard with stats |
| resources/js/Pages/Admin/Users/Index.vue | Added | User list + role management |
| resources/js/Pages/Admin/Shops/Index.vue | Added | Shop list + approve/suspend |
| resources/js/Pages/Admin/Categories/Index.vue | Added | Category CRUD (inline form) |
| resources/js/Pages/Admin/Orders/Index.vue | Added | Orders list (read-only) |
| resources/js/Pages/Admin/Products/Index.vue | Added | Products list (read-only) |

## Outcome

AppLayout 更新含購物車 badge 與角色導向連結；7 個 Seller 頁面與 6 個 Admin 頁面建立完成。Vite build 通過。
