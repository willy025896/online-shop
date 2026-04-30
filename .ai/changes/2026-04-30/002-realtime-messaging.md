# Change: 買家賣家即時通訊

**Date**: 2026-04-30 09:00
**Prompt**: [002-realtime-messaging](../prompts/2026-04-30/002-realtime-messaging.md)
**Type**: Feature

## Summary

新增基於 Laravel Reverb WebSocket 的買賣家即時通訊系統，每筆訂單對應一個獨立對話。從訂單頁可直接「詢問賣家」/「回覆客戶」進入對話，對話頂部固定訂單卡片可回到訂單頁。

## Files Changed

| Action | File Path | Description |
|--------|-----------|-------------|
| Added | app/Models/Conversation.php | 對話 Model |
| Added | app/Models/Message.php | 訊息 Model |
| Added | app/Services/ConversationService.php | getOrCreateForOrder/sendMessage/markAsRead |
| Added | app/Http/Controllers/ConversationController.php | index/show/storeMessage/markAsRead |
| Added | app/Policies/ConversationPolicy.php | 限買賣雙方 |
| Added | app/Events/MessageSent.php | broadcast 到 PrivateChannel |
| Added | routes/channels.php | conversation.{id} channel auth |
| Added | database/migrations/*_create_conversations_table.php | |
| Added | database/migrations/*_create_messages_table.php | |
| Added | database/factories/ConversationFactory.php / MessageFactory.php | |
| Added | resources/js/Pages/Messages/Index.vue / Show.vue | |
| Added | resources/js/Components/Messages/ConversationList.vue | |
| Added | resources/js/Components/Messages/MessageThread.vue | 含 Echo 訂閱 |
| Added | resources/js/Components/Messages/MessageBubble.vue | |
| Added | resources/js/Components/Messages/MessageComposer.vue | 含圖片上傳 |
| Added | resources/js/Components/Messages/OrderCardBanner.vue | 依角色切換訂單頁連結 |
| Added | lang/{en,zh_TW}/messages.php | |
| Added | tests/Feature/ConversationTest.php | 10 個測試全通過 |
| Modified | app/Http/Controllers/OrderController.php | startConversation |
| Modified | routes/web.php | 5 條新路由 |
| Modified | resources/js/Pages/Orders/Show.vue | 詢問賣家按鈕 |
| Modified | resources/js/Pages/Seller/Orders/Show.vue | 回覆客戶按鈕 |
| Modified | resources/js/Layouts/AppLayout.vue | 訊息圖示 + 未讀紅點 |
| Modified | resources/js/bootstrap.js | Echo 配置 |
| Modified | app/Http/Middleware/HandleInertiaRequests.php | unreadMessageCount 共享 |
| Modified | bootstrap/app.php | channels 路由註冊 |
| Modified | app/Models/User.php / Order.php | 新增關聯 |
| Modified | lang/{en,zh_TW}/orders.php / navigation.php | 補翻譯鍵 |
| Modified | composer.json / package.json | reverb / echo / pusher-js |
| Modified | .env / .env.example | REVERB_* 環境變數 |

## Testing

- **Tests Added**: Yes (10 個 Pest 測試)
- **Tests Passed**: Yes — `php artisan test` 81 passed, 1 skipped
- **Manual Testing**: 待手動測試（需要兩個瀏覽器 + reverb:start）

## Related Decisions

- 訂單卡片固定為 banner，不做為 message type（一對一綁定）
- buyer_id / seller_user_id 冗餘儲存避免反查 shop

## Breaking Changes

- None
