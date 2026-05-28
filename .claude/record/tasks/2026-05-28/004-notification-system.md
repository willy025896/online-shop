---
id: 2026-05-28/004
type: Feature
status: Done
---

# Task: 站內通知系統（database + broadcast）

## Request
使用者要求建置通知系統，盤點現有可接通知的業務事件，並實作即時推播。

確認方向：
- 通道：`database` + `broadcast`（不做 mail）
- 即時推播：沿用既有 Reverb + Laravel Echo
- 新訊息（聊天）**不發 notification**，保留現有 `unreadMessageCount` + `MessageSent` broadcast
- Admin 領域通知（賣家註冊待審、商品檢舉）延後到第二期

## Changes

### 新增（15）

| File | Action | Notes |
|------|--------|-------|
| `database/migrations/2026_05_28_065541_create_notifications_table.php` | Added | `php artisan notifications:table` 產出 |
| `config/broadcasting.php` | Added | `php artisan config:publish broadcasting` 產出 |
| `app/Notifications/OrderPaidNotification.php` | Added | 付款 → 賣家 |
| `app/Notifications/OrderStatusChangedNotification.php` | Added | 狀態變更 → 買家（白名單 paid/shipped/completed/cancelled） |
| `app/Notifications/OrderCancellationRequestedNotification.php` | Added | 買家請求取消 → 賣家 |
| `app/Notifications/OrderCancellationRespondedNotification.php` | Added | 賣家核准/拒絕 → 買家 |
| `app/Notifications/OrderCancelledBySellerNotification.php` | Added | 賣家直接取消 → 買家 |
| `app/Notifications/ShopStatusChangedNotification.php` | Added | 賣場 approved/suspended → 賣家 |
| `app/Http/Controllers/NotificationController.php` | Added | index/markRead/markAllRead/destroy |
| `resources/js/Components/NotificationBell.vue` | Added | 共用鈴鐺 + Echo 即時訂閱 |
| `resources/js/Pages/Notifications/Index.vue` | Added | 通知中心頁（all / unread / read 篩選 + 分頁） |
| `lang/en/notifications.php` | Added | 英文翻譯 |
| `lang/zh_TW/notifications.php` | Added | 中文翻譯 |
| `tests/Feature/NotificationTest.php` | Added | 14 個測試涵蓋觸發、白名單、API、ownership、Inertia share |

### 修改（11）

| File | Action | Notes |
|------|--------|-------|
| `routes/web.php` | Modified | auth 群組內加 4 條 notification 路由 |
| `routes/channels.php` | Modified | 補 `App.Models.User.{id}` 私人頻道授權 |
| `app/Http/Middleware/HandleInertiaRequests.php` | Modified | 加 `unreadNotificationCount`、`recentNotifications`、`notificationBellLang` |
| `app/Services/PaymentService.php` | Modified | 在 transaction 內發 `OrderPaidNotification` 給賣家 |
| `app/Services/OrderService.php` | Modified | requestCancellation/approveCancellation/rejectCancellation/cancelBySeller 四處發通知 |
| `app/Models/Order.php` | Modified | `booted()` updated event 依白名單發 `OrderStatusChangedNotification` 給買家 |
| `app/Http/Controllers/Admin/ShopController.php` | Modified | updateStatus 後發 `ShopStatusChangedNotification` 給賣家 |
| `resources/js/Layouts/AppLayout.vue` | Modified | 插入 `<NotificationBell />` |
| `resources/js/Layouts/SellerLayout.vue` | Modified | 插入 `<NotificationBell />` |
| `resources/js/Layouts/AdminLayout.vue` | Modified | 插入 `<NotificationBell />` |
| `.env.example` | Modified | `BROADCAST_CONNECTION=reverb`、補 Reverb 6 個變數 |
| `phpunit.xml` | Modified | 加 `BROADCAST_CONNECTION=null` 避免測試誤連 Pusher |

## Outcome

- 全部測試通過：**124 passed**（其中本任務 14 個新增測試全綠），無回歸。
- `npm run build` 成功，前端編譯零錯誤。
- 設計重點落實：
  - 訂單狀態變更走 Model `updated` event + 白名單（paid/shipped/completed/cancelled）跳過 processing，避免內部狀態打擾買家
  - 付款的「給賣家通知」由 `PaymentService` 顯式發，與「給買家的狀態變更通知」對象不同
  - 所有 notify 都在原本 transaction 內，DB rollback 時通知不會誤發

## Decision

涉及兩個架構決策，已建立 ADR：

- [ADR-004 通知系統採用 database + broadcast 通道](../../decisions/004-notification-system-channels.md)
- [ADR-005 訂單狀態變更通知走 Model event 而非 Service 顯式呼叫](../../decisions/005-order-status-notification-trigger.md)
