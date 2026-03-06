# Change: Admin sidebar 底部下拉選單向上展開

**Date**: 2026-03-06
**Prompt**: 004-admin-sidebar-dropdown-flip-up
**Type**: Bug Fix

## Summary

`Dropdown` 元件新增 `position` prop，支援 `top`（向上展開）。`AdminLayout` 底部選單套用 `position="top"`，開啟時改為向上顯示，不再被頁面底部截斷。

## Files Changed

| Action | File Path | Description |
|--------|-----------|-------------|
| Modified | `resources/js/Components/Dropdown.vue` | 新增 `position` prop，計算 `positionClass`（`mt-2` / `bottom-full mb-2`），`alignmentClasses` 依 position 切換 `origin-top-*` / `origin-bottom-*` |
| Modified | `resources/js/Layouts/AdminLayout.vue` | 底部 Dropdown 加上 `position="top"` |

## Testing

- **Tests Added**: No
- **Tests Passed**: N/A（待執行）
- **Manual Testing**: 登入 admin → 點擊左側欄底部三點選單 → 確認向上展開

## Related Decisions

- N/A

## Breaking Changes

- None（`position` 預設為 `bottom`，既有用法不受影響）
