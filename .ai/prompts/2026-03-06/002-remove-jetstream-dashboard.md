# Prompt: 移除 Jetstream 預設 Dashboard 頁面

**Date**: 2026-03-06
**Type**: Feature / Refactor

## Request

移除 Jetstream 預設的 Dashboard 頁面（只顯示 Welcome 元件），改為根據使用者角色（buyer/seller/admin）顯示不同行動入口的自訂儀表板。

## Scope

- 修改: `resources/js/Pages/Dashboard.vue`

## Outcome

Dashboard 頁面現在顯示：
- 歡迎訊息（含使用者名稱）
- Buyer → "Become a Seller" 連結
- Seller → "Seller Panel" 連結
- Admin → "Admin Panel" 連結
- 全部角色 → Orders 連結
