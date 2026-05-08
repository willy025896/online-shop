---
id: 2026-03-06/010
type: Feature
status: Done
---

# Task: Navigation Lang + Layout 語系切換按鈕

## Request

網頁內容寫死的導覽文字改為語系切換支援：新增 navigation.php 統一管理導覽字串，三個 Layout 全面改用 nav 字串，Seller/Admin 後台加入語系切換按鈕。

## Changes

| File | Action | Notes |
|------|--------|-------|
| lang/en/navigation.php | Added | 英文導覽字串（top nav + seller + admin） |
| lang/zh_TW/navigation.php | Added | 中文導覽字串 |
| app/Http/Middleware/HandleInertiaRequests.php | Modified | 共享 `nav` 給所有頁面 |
| resources/js/Layouts/AppLayout.vue | Modified | 加入 nav computed，替換所有硬編碼導覽文字 |
| resources/js/Layouts/SellerLayout.vue | Modified | 加入 nav/locale computed、navItems 改為 computed、語系切換按鈕 |
| resources/js/Layouts/AdminLayout.vue | Modified | 同 SellerLayout，語系切換放在 sidebar 底部 |

## Outcome

三個 Layout 的導覽文字全面改用 lang 字串；切換語系後 Seller/Admin 後台導覽文字即時更新。71 passed。
