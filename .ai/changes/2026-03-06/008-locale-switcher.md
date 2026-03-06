# Change: 新增語系切換功能

**Date**: 2026-03-06
**Prompt**: 005-locale-switcher
**Type**: Feature

## Summary

新增 en / zh_TW 語系切換。語系偏好存於 session，所有頁面透過 Inertia 共享 `locale`，AppLayout 提供切換按鈕。

## Files Changed

| Action | File Path | Description |
|--------|-----------|-------------|
| Added | `app/Http/Middleware/SetLocale.php` | 讀取 session locale 並呼叫 App::setLocale() |
| Added | `app/Http/Controllers/LocaleController.php` | POST /locale — 儲存語系至 session |
| Modified | `bootstrap/app.php` | 在 web middleware 加入 SetLocale |
| Modified | `routes/web.php` | 新增 POST /locale 路由 |
| Modified | `app/Http/Middleware/HandleInertiaRequests.php` | 共享 locale 給前端 |
| Modified | `resources/js/Layouts/AppLayout.vue` | 桌面版 EN｜中文切換按鈕 + 手機版切換按鈕 |

## Testing

- **Tests Added**: No
- **Tests Passed**: Yes（71 passed）
- **Manual Testing**: 點擊 EN / 中文 → 頁面語系更換並保留至下次造訪

## Related Decisions

- N/A

## Breaking Changes

- None
