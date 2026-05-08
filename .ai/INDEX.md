# AI Operations Index

**Project**: Online Shop
**Last Updated**: 2026-04-30

---

## Statistics

- **Total Prompts**: 8
- **Total Changes**: 11
- **Total Decisions**: 0

---

## Recent Activities

| Date | ID | Type | Description | Status |
|------|-----|------|-------------|--------|
| 2026-05-08 | 012 | Feature | 新增 npm run dev:full (concurrently) | Completed |
| 2026-04-30 | 011 | Feature | 買家賣家即時通訊（Reverb + WebSocket） | Completed |
| 2026-04-30 | 010 | Feature | 會員中心頁面（/members） | Completed |
| 2026-03-06 | 001 | Feature | AppLayout + Seller/Admin pages | Completed |
| 2026-03-06 | 002 | Feature | Lang files (en + zh_TW) | Completed |
| 2026-03-06 | 003 | Feature | Database factories + seeder | Completed |
| 2026-03-06 | 004 | Feature | Pest tests + migration/controller bug fixes | Completed |
| 2026-03-06 | 005 | Refactor | 移除 Jetstream 預設 Dashboard，改為角色導向儀表板 | Completed |
| 2026-03-06 | 006 | Feature | Stage 9 導覽列調整（移除 Dashboard、Orders 移入 dropdown、guest Log In） | Completed |
| 2026-03-06 | 007 | Bug Fix | Admin sidebar 底部 Dropdown 向上展開 | Completed |
| 2026-03-06 | 008 | Feature | 語系切換功能 (en / zh_TW) | Completed |
| 2026-03-06 | 009 | Feature | Stage 10 i18n 完成剩餘 11 個頁面 | Completed |

---

## Architecture Decisions

| ID | Title | Date | Status |
|----|-------|------|--------|
| - | - | - | - |

---

## Daily Summary

### 2026-04-30
- 會員中心：MemberController + Members.vue + lang files (en + zh_TW)
- 即時通訊：Reverb + Echo + Conversation/Message + 5 個 Vue 元件 + 10 個 Pest 測試

### 2026-03-06
- Stage 1-3: AppLayout 更新 + Seller 頁面 (7) + Admin 頁面 (6)
- Stage 4: Lang 翻譯檔 (en + zh_TW, 20 files)
- Stage 5: Database factories (5) + seeder
- Stage 6: Pest tests (41 new) + bug fixes (migration ordering, AuthorizesRequests)
- Stage 7-9: Dashboard 重寫、Shop/Show 搜尋過濾排序、AppLayout 導覽列調整

---

_此檔案由 AI Assistant 自動維護_