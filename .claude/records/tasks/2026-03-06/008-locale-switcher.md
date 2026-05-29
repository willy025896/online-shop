---
id: 2026-03-06/008
type: Feature
status: Done
---

# Task: 語系切換功能 (en / zh_TW)

## Request

新增 en / zh_TW 語系切換功能，語系偏好存於 session，所有頁面透過 Inertia 共享 locale，AppLayout 提供切換按鈕。

## Changes

| File | Action | Notes |
|------|--------|-------|
| app/Http/Middleware/SetLocale.php | Added | 讀取 session locale 並呼叫 App::setLocale() |
| app/Http/Controllers/LocaleController.php | Added | POST /locale — 儲存語系至 session |
| bootstrap/app.php | Modified | 在 web middleware 加入 SetLocale |
| routes/web.php | Modified | 新增 POST /locale 路由 |
| app/Http/Middleware/HandleInertiaRequests.php | Modified | 共享 locale 給前端 |
| resources/js/Layouts/AppLayout.vue | Modified | 桌面版 EN｜中文切換按鈕 + 手機版切換按鈕 |

## Outcome

語系切換完成，點擊 EN / 中文後頁面語系更換並保留至下次造訪。71 passed。
