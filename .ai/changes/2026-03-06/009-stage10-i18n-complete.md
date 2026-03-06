# Change: Stage 10 i18n — 完成剩餘 11 個頁面

**Date**: 2026-03-06 19:00
**Prompt**: 006-stage10-i18n-complete
**Type**: Feature

## Summary

完成 Stage 10 所有頁面的 i18n。11 個 Vue 頁面更新為使用 `usePage().props.lang` 取代硬編碼文字。lang 檔案已齊全，無需新增 key。

## Files Changed

| Action | File Path | Description |
|--------|-----------|-------------|
| Modified | resources/js/Pages/Seller/Products/Edit.vue | 加入 lang，替換所有硬編碼文字 |
| Modified | resources/js/Pages/Seller/Orders/Index.vue | 加入 lang，替換表頭、空狀態、items_count |
| Modified | resources/js/Pages/Seller/Orders/Show.vue | 加入 lang，替換所有標籤與 mark_as 動態替換 |
| Modified | resources/js/Pages/Seller/Register.vue | 加入 lang，替換標題、說明與表單標籤 |
| Modified | resources/js/Pages/Seller/Shop/Edit.vue | 加入 lang，替換標題與表單標籤 |
| Modified | resources/js/Pages/Admin/Dashboard.vue | 加入 lang，替換 6 個統計卡片文字 |
| Modified | resources/js/Pages/Admin/Users/Index.vue | 加入 lang，替換表頭與角色選項 |
| Modified | resources/js/Pages/Admin/Shops/Index.vue | 加入 lang，替換表頭、狀態標籤與操作按鈕 |
| Modified | resources/js/Pages/Admin/Categories/Index.vue | 加入 lang，替換所有 UI 文字與 delete_confirm 動態替換 |
| Modified | resources/js/Pages/Admin/Orders/Index.vue | 加入 lang，替換表頭與空狀態 |
| Modified | resources/js/Pages/Admin/Products/Index.vue | 加入 lang，替換表頭與空狀態 |
| Modified | PLAN.md | Stage 10 全部標記為完成 |

## Testing

- **Tests Added**: No
- **Tests Passed**: N/A
- **Manual Testing**: 需手動確認語系切換正常

## Related Decisions

- N/A

## Breaking Changes

- None
