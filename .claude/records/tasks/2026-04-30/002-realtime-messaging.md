---
id: 2026-04-30/002
type: Feature
status: Done
---

# Task: 買家賣家即時通訊（Reverb WebSocket）

## Request

新增買家與賣家的即時通訊功能：每筆訂單一個對話，訂單頁可直接「詢問賣家」/「回覆客戶」進入對話，對話頂部固定訂單卡片可回到訂單頁，並支援 Reverb WebSocket 即時推送。

## Changes

| File | Action | Notes |
|------|--------|-------|
| app/Models/Conversation.php | Added | 對話 Model |
| app/Models/Message.php | Added | 訊息 Model |
| app/Services/ConversationService.php | Added | getOrCreateForOrder / sendMessage / markAsRead |
| app/Http/Controllers/ConversationController.php | Added | index / show / storeMessage / markAsRead |
| app/Policies/ConversationPolicy.php | Added | 限買賣雙方存取 |
| app/Events/MessageSent.php | Added | broadcast 到 PrivateChannel |
| routes/channels.php | Added | conversation.{id} channel auth |
| database/migrations/*_create_conversations_table.php | Added | |
| database/migrations/*_create_messages_table.php | Added | |
| database/factories/ConversationFactory.php | Added | |
| database/factories/MessageFactory.php | Added | |
| resources/js/Pages/Messages/Index.vue | Added | 左列表 + 右訊息雙欄佈局 |
| resources/js/Pages/Messages/Show.vue | Added | 訊息對話頁 |
| resources/js/Components/Messages/ConversationList.vue | Added | |
| resources/js/Components/Messages/MessageThread.vue | Added | 含 Echo 訂閱 |
| resources/js/Components/Messages/MessageBubble.vue | Added | |
| resources/js/Components/Messages/MessageComposer.vue | Added | 含圖片上傳 |
| resources/js/Components/Messages/OrderCardBanner.vue | Added | 依角色切換訂單頁連結 |
| lang/en/messages.php | Added | |
| lang/zh_TW/messages.php | Added | |
| tests/Feature/ConversationTest.php | Added | 10 個測試 |
| app/Http/Controllers/OrderController.php | Modified | startConversation |
| routes/web.php | Modified | 5 條新路由 |
| resources/js/Pages/Orders/Show.vue | Modified | 詢問賣家按鈕 |
| resources/js/Pages/Seller/Orders/Show.vue | Modified | 回覆客戶按鈕 |
| resources/js/Layouts/AppLayout.vue | Modified | 訊息圖示 + 未讀紅點 |
| resources/js/bootstrap.js | Modified | Echo 配置 |
| app/Http/Middleware/HandleInertiaRequests.php | Modified | 共享 unreadMessageCount |
| bootstrap/app.php | Modified | channels 路由註冊 |
| app/Models/User.php | Modified | 新增關聯 |
| app/Models/Order.php | Modified | 新增關聯 |
| lang/en/orders.php | Modified | 補翻譯鍵 |
| lang/zh_TW/orders.php | Modified | 補翻譯鍵 |
| lang/en/navigation.php | Modified | 補翻譯鍵 |
| lang/zh_TW/navigation.php | Modified | 補翻譯鍵 |
| composer.json | Modified | 加入 reverb |
| package.json | Modified | 加入 echo / pusher-js |
| .env | Modified | REVERB_* 環境變數 |
| .env.example | Modified | REVERB_* 環境變數 |

## Outcome

Reverb WebSocket 即時通訊系統完成：每筆訂單對應一個對話，訂單卡片固定在對話頂端，支援純文字 + 圖片附件 + 已讀/未讀。`php artisan test` 81 passed, 1 skipped。

## Decision

- 訂單卡片固定為 banner（不作為 message type），一對一綁定對話
- buyer_id / seller_user_id 冗餘儲存，避免反查 shop
