---
id: 2026-03-06/006
type: Feature
status: Done
---

# Task: Stage 9 導覽列調整

## Request

調整 AppLayout.vue 導覽列：移除 Dashboard 連結、將 Orders 移入 account dropdown、未登入時顯示 Log In 按鈕。

## Changes

| File | Action | Notes |
|------|--------|-------|
| resources/js/Layouts/AppLayout.vue | Modified | 移除 Dashboard NavLink、Orders 移至 dropdown、加入 guest Log In 按鈕 |

## Outcome

導覽列精簡完成。路由 `/dashboard` 仍存在，只是不在導覽列顯示。71 passed。
