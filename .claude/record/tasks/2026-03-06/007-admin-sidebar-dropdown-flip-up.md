---
id: 2026-03-06/007
type: Bug Fix
status: Done
---

# Task: Admin Sidebar 底部 Dropdown 向上展開

## Request

Admin sidebar 底部下拉選單被頁面底部截斷，需改為向上展開。

## Changes

| File | Action | Notes |
|------|--------|-------|
| resources/js/Components/Dropdown.vue | Modified | 新增 `position` prop，計算 `positionClass`（`mt-2` / `bottom-full mb-2`），alignmentClasses 依 position 切換 origin |
| resources/js/Layouts/AdminLayout.vue | Modified | 底部 Dropdown 加上 `position="top"` |

## Outcome

Dropdown 元件新增 position prop（預設 `bottom`，傳入 `top` 向上展開）。既有用法不受影響。
