# Change: Stage 9 導覽列調整

**Date**: 2026-03-06
**Prompt**: 003-stage9-navbar-changes
**Type**: Feature

## Summary

調整 AppLayout.vue 導覽列結構：移除 Dashboard 連結、將 Orders 移入 account dropdown、未登入時顯示 Log In 按鈕。

## Files Changed

| Action | File Path | Description |
|--------|-----------|-------------|
| Modified | `resources/js/Layouts/AppLayout.vue` | 移除 Dashboard NavLink、Orders 移至 dropdown、加入 guest Log In 按鈕 |

## Testing

- **Tests Added**: No
- **Tests Passed**: Yes（71 passed）
- **Manual Testing**: 桌面版與手機響應版均需驗證三個變更點

## Related Decisions

- N/A

## Breaking Changes

- None（路由 `/dashboard` 仍存在，只是不在導覽列顯示）
