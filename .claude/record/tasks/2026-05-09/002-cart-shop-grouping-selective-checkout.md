---
id: 2026-05-09/002
type: Feature
status: Done
---

# Task: 購物車依商家分組，支援勾選結帳

## Request

確認購物車商品是否依商家分類，並能一次勾選商家結帳或全部勾選。原本購物車為平鋪清單、無勾選機制，結帳一律處理全部商品。

## Changes

| File | Action | Notes |
|------|--------|-------|
| resources/js/Pages/Cart/Index.vue | Modified | 依 shop 分組顯示；全選 / 單家 / 單品三層 checkbox；動態小計；結帳按鈕帶 item_ids |
| resources/js/Components/CartItemRow.vue | Modified | 新增 checked prop + toggle emit，左側顯示 checkbox |
| app/Http/Controllers/CheckoutController.php | Modified | index() 依 item_ids 過濾展示與小計；store() 接收並傳遞 item_ids |
| app/Services/OrderService.php | Modified | createOrdersFromCart() 新增 itemIds 參數，結帳後只刪已結帳商品 |
| resources/js/Pages/Checkout/Index.vue | Modified | 新增 itemIds prop，加入 form.item_ids 一併送出 |
| lang/en/cart.php | Modified | 新增 select_all、checkout_selected |
| lang/zh_TW/cart.php | Modified | 新增 select_all、checkout_selected |
| tests/Feature/OrderTest.php | Modified | 新增 4 個測試覆蓋部分結帳邏輯 |

## Outcome

購物車商品依商家分組顯示，支援全選 / 單家全選 / 單品三層勾選，小計即時反映選取狀態。結帳只處理勾選商品，未勾選商品留在購物車。85 passed，1 skipped。

測試覆蓋：結帳頁 item_ids 過濾、無效 ID 重導、部分結帳保留未選商品、多商家各建一筆訂單。
