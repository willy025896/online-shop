---
id: ADR-016
title: Email 通知（mail channel）與使用者語系持久化
date: 2026-07-09
status: Accepted
---

# ADR-016: Email 通知（mail channel）與使用者語系持久化

## Context

14 個 Notification class（`app/Notifications/`）原本全部只有 `via() = ['database', 'broadcast']`，買家/賣家沒有登入網站就完全收不到任何通知，只能靠站內鈴鐺（`NotificationBell`）。這件事在 ADR-004（2026-05-28，通知系統上線時的 ADR）就已經明確預留：「未來想加 mail，只需在個別 Notification 的 `via()` 加 `'mail'` + 寫 `toMail()`，不影響既有實作」。這次要做的就是這個延後的工作，範圍限定在「訂單生命週期關鍵事件、買家/賣家不該因為沒登入而錯過」，不含聊天訊息與評論流程通知。

實作規劃過程中發現一個必須一併處理的既有問題：所有通知都是真的 queued（`QUEUE_CONNECTION=database`），語系卻只存在 session 裡（`app/Http/Middleware/SetLocale.php` 讀 `session('locale', ...)`），`users` 資料表沒有 locale 欄位。Queue worker 處理通知時早已脫離原本觸發通知的 HTTP session，若不處理，寄出的信件內容會固定用系統預設語系（英文），不會跟著收件人原本選的語系走——這不只影響新加的 mail channel，理論上現有的 database/broadcast 通知內容也有同樣的潛在問題，只是使用者停留在同一個 session 瀏覽時不會發現。已與使用者確認一併修正。

## Decision

### 1. 通用 `MailsAsArray` trait，取代逐一手寫 `toMail()`

專案裡每個 Notification 的 `toArray()`早就回傳統一的 `{type, title, body, url, meta}` 形狀（`app/Notifications/Concerns/BroadcastsAsArray.php` 正是靠這個統一形狀讓 `toBroadcast()` 完全通用）。新增 `app/Notifications/Concerns/MailsAsArray.php`，比照同樣的做法：

```php
public function toMail(object $notifiable): MailMessage
{
    $data = $this->toArray($notifiable);

    return (new MailMessage)
        ->subject($data['title'])
        ->line($data['body'])
        ->action(__('notifications.mail.view_details'), $data['url']);
}
```

9 個目標 class 只需要 `use BroadcastsAsArray, MailsAsArray, Queueable;` + `via()` 加上 `'mail'`，零客製化 `toMail()`。

### 2. 只有 9 個「訂單生命週期關鍵事件」加 mail channel

加入：`OrderPaidNotification`、`OrderStatusChangedNotification`、`OrderCancellationRequestedNotification`、`OrderCancellationRespondedNotification`、`OrderCancelledBySellerNotification`、`OrderReturnRequestedNotification`、`OrderReturnRespondedNotification`、`PayoutCompletedNotification`、`ShopStatusChangedNotification`。

排除：`NewMessageNotification`（聊天訊息，加了容易變成信件轟炸）、`ReviewCoolingStartedNotification`／`ReviewCoolingResetNotification`／`ReviewReleasedNotification`／`SellerReplyNotification`（評論流程，非緊急、不需要立即採取行動）。

### 3. 語系持久化：`users.locale` + Laravel 內建的 `HasLocalePreference`

新增 nullable `users.locale` 欄位（migration `2026_07_09_100000_add_locale_to_users_table`），`User` 實作 `Illuminate\Contracts\Translation\HasLocalePreference::preferredLocale()`（直接回傳 `$this->locale`）。`LocaleController::store()` 除了原本寫 session，現在同時把 locale 持久化到已登入使用者的資料列（訪客維持原樣，只寫 session）。

這是 Laravel 內建的掛勾——`NotificationSender::sendNow()` 只要偵測到 notifiable 實作 `HasLocalePreference`，會自動把**整個**通知寄送流程（`toArray()`/`toBroadcast()`/`toMail()` 全部）包在 `withLocale($user->preferredLocale(), ...)` 裡執行。不需要更動任何一個現有 Notification class 的內容邏輯，這一個掛勾同時修好了 mail 跟現有 database/broadcast 通知的語系正確性問題。

### 4. `MAIL_MAILER` 維持 `log`，不寄真實信件

`.env.example`/`config/mail.php` 現有預設值（`MAIL_MAILER=log`）維持不變——這次只把 mail channel 的程式碼路徑接上，正式上線要接真實 SMTP/寄信服務（Mailgun/SES/Postmark 等）是後續獨立的工作，不在這次範圍內。所有測試全程用 `MailMessage`（純資料物件，不碰 mailer/transport）或 `Notification::fake()`，不會真的寄出任何信件。

## Post-Review Fixes（post-change-review 發現並已修正）

實作完成後跑了一輪 code-review（8 個角度）+ security-review，發現並修正了以下問題（都已補上對應測試）：

- **[CRITICAL] 9 個 mail-enabled Notification 都沒有 `implements ShouldQueue`** — `Queueable` trait 本身不會讓通知排入佇列，只有搭配 `ShouldQueue` 介面才會。這代表 mail 會在觸發通知的當下同步發送，而這些呼叫點大量位於 `DB::transaction` + `lockForUpdate()` 之內（ECPay webhook 的 `PaymentService::markAsPaid`、`OrderService` 的取消/退貨方法、`Order::booted()` 狀態變更事件、`PayoutService::generateForShop`）——一旦正式接上真實 SMTP，寄信逾時/失敗會讓整筆業務交易 rollback，且會拖慢 ECPay webhook 必須快速回應的 `"1|OK"`。修正：9 個 class 都補上 `implements ShouldQueue`；`package.json` 的 `dev:full` 加上 `php artisan queue:listen`，本機開發才會真的處理佇列。其餘 5 個 database+broadcast-only 的 class（聊天、評論流程）刻意不加 `ShouldQueue`——它們是低成本的本地寫入，且 `NewMessageNotification` 需要跟即時聊天 broadcast 同步送達，排隊反而拖慢體驗。
- **[WARNING] `SetLocale` middleware 沒有 fallback 讀取新持久化的 `users.locale`** — 只讀 session，導致使用者換裝置/清 session 後，網頁畫面語系跟寄出的 mail 語系可能不一致。修正：`session('locale') ?? $request->user()?->locale ?? config('app.locale')`，並把解析後的值寫回 session。
- **[WARNING] 新用戶註冊沒有把當下語系寫入 `users.locale`** — `CreateNewUser` 原本沒有設定 `locale`，新用戶會停留在 `null` 直到手動切換一次語系。修正：`User::create()` 加上 `'locale' => session('locale')`，註冊當下就把瀏覽時的語系種進去。
- **[SUGGESTION] `OrderReturnRespondedNotification` 核准退貨的 mail 內文沒有帶退款金額** — `refund_amount` 只存在 `meta`，買家收到信看不到退了多少錢，跟 `PayoutCompletedNotification` 的 body 會帶金額不一致。修正：`return_approved` 的 lang body 加上 `:amount` 佔位符，`toArray()` 只在核准時帶入格式化後的金額。
- **[SUGGESTION] `CLAUDE.md` 兩處文件不同步** — `Concerns/` 目錄樹註解沒提到新增的 `MailsAsArray.php`；i18n 章節沒提到 `LocaleController`/`SetLocale`/`CreateNewUser` 的語系持久化行為。已補齊。
- **已確認非問題** — 安全性審查（授權範圍、mass-assignment、跨收件人資料外洩、mail header injection）沒有發現任何高信心漏洞：`LocaleController::store()` 只寫入 `$request->user()` 自己的欄位（非 IDOR），`locale` 值已通過白名單驗證，這 9 個類別的內容不依賴 `$notifiable` 分支，也沒有使用者可控資料流入 mail 的 Subject 標頭。

## Consequences

- 優點：
  - 買家/賣家不用一直開著網站也能收到關鍵事件通知，降低「已核准/已出貨但當事人不知道」的體驗落差。
  - 因為 9 個目標 class 都已有統一的 `toArray()` 形狀，程式碼增量非常小（一個共用 trait + 逐一兩行改動），沒有引入新的樣板負擔。
  - `users.locale` + `HasLocalePreference` 這個小改動，順便修好了現有 database/broadcast 通知的潛在語系錯亂問題，一次解決兩個問題。
- 缺點：
  - 目前沒有退訂/通知偏好設定機制——買家/賣家無法自行關閉某類 mail 通知，只能全有或全無（跟著 `via()` 的白名單走）。
  - 信件版型是 Laravel `MailMessage` 的預設樣式（純文字風格 line/action button），沒有客製化品牌樣式；`greeting()`/`salutation()` 等內建字串目前不會跟著 zh_TW 走（Laravel 框架本身沒有內建這些字串的中文翻譯），只有 `subject`/`line`/`action` 這三個由本專案 lang 檔控制的部分會正確在地化。
  - 尚未接真實 SMTP，正式上線前需要另外設定寄信服務並處理送達率/退信等問題。

## Alternatives Considered

- **9 個 class 各自手寫 `toMail()`**：因為 `toArray()` 形狀完全一致，逐一手寫是純粹的重複程式碼，沒有帶來額外的彈性，不採用。
- **用 cookie 存語系，不動 `users` 資料表**：非同步的 queue worker 完全沒有 HTTP request/cookie 可讀，這個方案在 queue worker 端根本行不通，不採用。
- **語系直接當參數傳進每個 Notification 的建構子**（呼叫端自己決定），而不是用 `HasLocalePreference`：需要同時修改全部 9 個通知的建構子跟所有呼叫端（`PaymentService`/`OrderService`/`ConversationService`/`Admin\ShopController` 等分散在各處的觸發點），改動面遠大於一個 model 方法；而且這個做法不會順便修好現有 database/broadcast 通知的語系問題，因為那些 class 的 `toArray()` 呼叫路徑完全不會變動。
