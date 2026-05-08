---
id: 2026-03-06/009
type: Feature
status: Done
---

# Task: Stage 10 i18n — 完成剩餘 11 個頁面

## Request

完成 Stage 10 所有頁面的 i18n，將 11 個 Vue 頁面的硬編碼文字改為使用 `usePage().props.lang` 字串。

## Changes

| File | Action | Notes |
|------|--------|-------|
| resources/js/Pages/Seller/Products/Edit.vue | Modified | 加入 lang，替換所有硬編碼文字 |
| resources/js/Pages/Seller/Orders/Index.vue | Modified | 加入 lang，替換表頭、空狀態、items_count |
| resources/js/Pages/Seller/Orders/Show.vue | Modified | 加入 lang，替換所有標籤與 mark_as 動態替換 |
| resources/js/Pages/Seller/Register.vue | Modified | 加入 lang，替換標題、說明與表單標籤 |
| resources/js/Pages/Seller/Shop/Edit.vue | Modified | 加入 lang，替換標題與表單標籤 |
| resources/js/Pages/Admin/Dashboard.vue | Modified | 加入 lang，替換 6 個統計卡片文字 |
| resources/js/Pages/Admin/Users/Index.vue | Modified | 加入 lang，替換表頭與角色選項 |
| resources/js/Pages/Admin/Shops/Index.vue | Modified | 加入 lang，替換表頭、狀態標籤與操作按鈕 |
| resources/js/Pages/Admin/Categories/Index.vue | Modified | 加入 lang，替換所有 UI 文字與 delete_confirm 動態替換 |
| resources/js/Pages/Admin/Orders/Index.vue | Modified | 加入 lang，替換表頭與空狀態 |
| resources/js/Pages/Admin/Products/Index.vue | Modified | 加入 lang，替換表頭與空狀態 |

## Outcome

11 個 Seller/Admin 頁面全面改用 lang 字串，硬編碼文字清除完畢。
