---
id: ADR-010
title: 商品問答（購買前 Q&A）— 沿用聊天系統、conversations 脫離訂單綁定
date: 2026-07-06
status: Accepted
---

# ADR-010: 商品問答（購買前 Q&A）— 沿用聊天系統、conversations 脫離訂單綁定

## Context

買家在下單前常對商品有疑問（庫存、規格、到貨時間等），但既有聊天系統的 `conversations.order_id` 是 **NOT NULL + UNIQUE**，對話必然掛在一張訂單下（`ConversationService::getOrCreateForOrder`）。這使得「無訂單也能詢問商品」無法在既有資料模型下表達，需要調整 schema。

## Decision

- **`conversations.order_id` 改為 nullable**（保留原本的 `unique` 索引與外鍵）。MySQL 的 unique 索引允許多筆 `NULL`，因此「一買家對一賣家的多筆商品詢問」可以共用**同一條 `order_id IS NULL` 的對話串**，不需要另開資料表或另建 `product_id` 唯一鍵。
- **一買家對一賣家只有一條「商品詢問」對話**：`ConversationService::getOrCreateForProduct()` 用 `firstOrCreate(['buyer_id', 'seller_user_id', 'order_id' => null])` 找到/建立這條串，之後不論詢問賣場下哪個商品都寫入同一條對話，商品差異靠**訊息層級的商品卡片**表達，而非開新對話——比照真實購物平台「一買家一賣家一個聊天室」的體感，也避免對話列表被同一賣家的多次詢問洗版。
- **商品卡片是訊息的附加內容，不是新概念**：`messages` 新增 nullable `product_id`（`nullOnDelete`，因為 `Product` 本身是 SoftDeletes，實務上不會真的觸發)。`ConversationService::sendMessage()` 新增可選的 `?Product $product` 參數，`body`/`image`/`product` 三者至少一個非空即可成立訊息（原本只有 `body`/`image`）。前端點擊商品頁的「詢問賣家」會送出一則 `product_id` 有值、`body` 為空的訊息，渲染成商品卡片（縮圖 + 名稱 + 價格，可點擊前往商品頁）。
- **賣場名稱不再依賴 `order.shop`**：既有程式碼透過 `$conversation->order->shop->name` 取得賣場名稱，商品詢問對話沒有訂單可以這樣取。改為一律從 `$conversation->seller->shop->name`（`seller_user_id` 恆存在）取得，訂單與非訂單對話共用同一套邏輯，`order` 物件本身只保留訂單特定欄位（`order_number`/`status`/`total`）。
- **新訊息一律觸發通知**：新增 `NewMessageNotification`（沿用 `BroadcastsAsArray`，`database` + `broadcast` 雙通道，跟其他 10 個 Notification 類別同款式）。在 `ConversationService::sendMessage()` 對 `$conversation->otherParticipant($sender)` 呼叫 `notify()`，涵蓋所有聊天（訂單聊天 + 商品詢問），不只商品 Q&A——因為在此之前聊天訊息完全没有走過 Notification pipeline（只有畫面內即時更新與 navbar 未讀數字），離開聊天頁面就無從得知有新訊息。

## Consequences

- 優點：不新增資料表，複用既有 `Conversation`/`Message`/`ConversationService`/`ConversationPolicy`/前端聊天元件；商品詢問與訂單聊天走同一套 UI 與已讀/未讀邏輯。
- 優點：`NewMessageNotification` 補上了聊天訊息原本缺的鈴鐺通知，買賣雙方離開聊天頁也會被告知。
- 缺點：`getOrCreateForProduct` 沒有 DB 層唯一鍵擋重複（`(buyer_id, seller_user_id) WHERE order_id IS NULL` 這種 partial unique index MySQL 無法表達，同 ADR-008 coupon code 的限制），僅靠 `firstOrCreate` 在應用層防重複；理論上极端併發雙擊可能建立兩條商品詢問對話，但影響輕微（買家只是看到重複的對話串），並非資料錯誤。
- 缺點：商品被軟刪除後，訊息裡的商品卡片會顯示「此商品已下架」（`messages.product_id` 保留，但 `belongsTo` 關聯因 `SoftDeletes` 全域 scope 撈不到），需前端與後端都對 null 商品做保護（已處理）。

## Alternatives Considered

- **每次詢問都開一條新對話**：實作最簡單，但賣家收件匣會被同一買家的多次詢問洗版，且不符合一般聊天室的體感；不採用。
- **獨立的 `product_inquiries` 資料表**：與聊天系統平行存在兩套已讀/通知/UI，違反「沿用現有訊息系統」的需求本意；不採用。
- **`conversations` 新增 `product_id` 欄位取代「訊息層級商品卡片」**：這樣一個商品只能對應一條對話，買家問完 A 商品再問 B 商品會被迫開新對話，體驗更差；不採用。
