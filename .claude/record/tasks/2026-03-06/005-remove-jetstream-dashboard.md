---
id: 2026-03-06/005
type: Refactor
status: Done
---

# Task: 移除 Jetstream 預設 Dashboard

## Request

移除 Jetstream 預設的 Dashboard（只顯示 Welcome 元件），改為根據使用者角色（buyer/seller/admin）顯示不同行動入口的自訂儀表板。

## Changes

| File | Action | Notes |
|------|--------|-------|
| resources/js/Pages/Dashboard.vue | Modified | 移除 Welcome 元件，改為自訂角色導向儀表板 |

## Outcome

Dashboard 頁面改為角色導向：buyer 顯示「成為賣家」入口、seller 顯示「賣家後台」、admin 顯示「管理後台」；所有角色均顯示訂單連結。
