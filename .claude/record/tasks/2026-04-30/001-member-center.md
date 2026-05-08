---
id: 2026-04-30/001
type: Feature
status: Done
---

# Task: 會員中心頁面 (/members)

## Request

規劃並實作會員中心頁面（/members），作為登入後的個人首頁 Hub，顯示個人資訊、訂單統計卡片、最近 5 筆訂單，並依 role 提供快捷入口。

## Changes

| File | Action | Notes |
|------|--------|-------|
| app/Http/Controllers/MemberController.php | Added | 查詢訂單統計 + 最近 5 筆訂單 |
| routes/web.php | Modified | /members 改為使用 MemberController |
| resources/js/Pages/Members.vue | Modified | 全新重寫會員中心頁面 |
| lang/en/members.php | Added | 英文翻譯 |
| lang/zh_TW/members.php | Added | 繁中翻譯 |

## Outcome

會員中心頁面完成：個人資訊（頭像、姓名、Email、角色 badge）、訂單統計卡片（全部/待付款/進行中/已完成）、最近 5 筆訂單列表、依 role 快捷入口。
