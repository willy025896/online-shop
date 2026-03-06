# Change: 移除 Jetstream 預設 Dashboard 頁面

**Date**: 2026-03-06
**Type**: Refactor
**Status**: Completed

## Files Modified

- `resources/js/Pages/Dashboard.vue` — 移除 Welcome 元件，改為自訂角色導向儀表板

## Summary

將 Jetstream 預設的 Dashboard（只顯示文件連結的 Welcome 元件）替換為自訂儀表板：

- 使用 `usePage()` 取得 `lang`、`userRole`、`auth.user`
- 顯示個人化歡迎訊息
- 根據 `userRole` 條件顯示對應行動入口：
  - `buyer` → seller.register
  - `seller` → seller.dashboard
  - `admin` → admin.dashboard
- 所有角色皆顯示 orders.index 連結
