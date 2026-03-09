# Change: Stage 4 補完 i18n 語系資料 + 後台語系切換

**Date**: 2026-03-06
**Prompt**: 006-stage4-i18n-complete
**Type**: Feature

## Summary

新增 navigation.php 統一管理導覽文字，三個 Layout 全面改用 nav 字串，Seller/Admin 後台加入語系切換按鈕。

## Files Changed

| Action | File Path | Description |
|--------|-----------|-------------|
| Added | `lang/en/navigation.php` | 英文導覽字串（top nav + seller + admin） |
| Added | `lang/zh_TW/navigation.php` | 中文導覽字串 |
| Modified | `app/Http/Middleware/HandleInertiaRequests.php` | 共享 `nav` 給所有頁面 |
| Modified | `resources/js/Layouts/AppLayout.vue` | 加入 nav computed，替換所有硬編碼導覽文字 |
| Modified | `resources/js/Layouts/SellerLayout.vue` | 加入 nav/locale computed、navItems 改為 computed、語系切換按鈕 |
| Modified | `resources/js/Layouts/AdminLayout.vue` | 同 SellerLayout，語系切換放在 sidebar 底部 |

## Testing

- **Tests Added**: No
- **Tests Passed**: Yes（71 passed）
- **Manual Testing**: 切換語系後三個 Layout 的導覽文字均更新

## Related Decisions

- N/A

## Breaking Changes

- None
