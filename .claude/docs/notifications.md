# Notifications

Uses Laravel's built-in `Notifiable` pipeline. Each Notification's `via()` returns `['database', 'broadcast']`, plus `'mail'` for the 9 order-lifecycle-critical events listed below (see ADR-016):

- **database** — written to the `notifications` table; the `NotificationBell` dropdown and `/notifications` index page read from it via `$request->user()->notifications()` / `unreadNotifications()`.
- **broadcast** — pushed over Reverb to Laravel's default `private-App.Models.User.{id}` channel (authorization is registered in `routes/channels.php`). The front-end `NotificationBell.vue` subscribes via `Echo.private(...).notification(cb)` and prepends new entries without a page reload.
- **mail** — via `toMail()` from the `MailsAsArray` trait (mirrors `BroadcastsAsArray`, see below). Only added to notifications representing events a recipient shouldn't miss while logged out: `OrderPaidNotification`, `OrderStatusChangedNotification`, `OrderCancellationRequestedNotification`, `OrderCancellationRespondedNotification`, `OrderCancelledBySellerNotification`, `OrderReturnRequestedNotification`, `OrderReturnRespondedNotification`, `PayoutCompletedNotification`, `ShopStatusChangedNotification`. Chat (`NewMessageNotification`) and review-flow notifications deliberately stay database+broadcast only, to avoid mail spam. `MAIL_MAILER=log` by default — no real SMTP is configured out of the box (see ADR-016).

These same 9 classes `implements ShouldQueue` — required so the mail send actually happens asynchronously on the queue rather than blocking the request/webhook thread that triggered it. Several dispatch sites hold a `DB::transaction` + `lockForUpdate()` while calling `->notify()` (`PaymentService::markAsPaid`, `OrderService`'s cancellation/return methods, `Order::booted()`'s `updated` event, `PayoutService::generateForShop`); without `ShouldQueue`, a slow/failed mail send would extend the row lock and could roll back an already-correct business transaction over a transient SMTP hiccup. This means local dev needs a running queue worker for these 9 to actually deliver — `npm run dev:full` starts `php artisan queue:listen` alongside Vite/Reverb for this reason. The other 5 classes (chat, review-flow) are **not** `ShouldQueue` — they're cheap local writes (no external I/O) and, for `NewMessageNotification` specifically, need to land synchronously alongside the immediate chat broadcast.

Notification classes live in `app/Notifications/`. Each `toArray()` returns a uniform payload — `{ type, title, body, url, meta }` — so the bell renders any type from a single template.

**Trigger map:**

| Event | Triggered in | Notification | Recipient |
|-------|--------------|--------------|-----------|
| Payment success | `PaymentService::markAsPaid` (called from `EcpayController::notify` after signature verification) | `OrderPaidNotification` | Seller |
| Status changes to `paid`/`shipped`/`completed` | `Order::booted()` `updated` event (whitelist `BUYER_NOTIFY_STATUSES`) | `OrderStatusChangedNotification` | Buyer |
| Buyer requests cancellation | `OrderService::requestCancellation` | `OrderCancellationRequestedNotification` | Seller |
| Seller approves/rejects cancellation | `OrderService::approveCancellation` / `rejectCancellation` | `OrderCancellationRespondedNotification` | Buyer |
| Seller directly cancels | `OrderService::cancelBySeller` | `OrderCancelledBySellerNotification` | Buyer |
| Shop `approved`/`suspended` | `Admin\ShopController::updateStatus` | `ShopStatusChangedNotification` | Seller |
| New chat message (order chat or product Q&A) | `ConversationService::sendMessage` | `NewMessageNotification` | The other participant |
| Buyer requests a return | `OrderService::requestReturn` | `OrderReturnRequestedNotification` | Seller |
| Seller approves/rejects a return | `OrderService::approveReturn` / `rejectReturn` | `OrderReturnRespondedNotification` | Buyer |
| Payout generated | `PayoutService::generateForShop` | `PayoutCompletedNotification` | Seller |

`cancelled` is intentionally **excluded** from `Order::BUYER_NOTIFY_STATUSES` — every cancellation path already fires a path-specific notification, so including it would double-notify the buyer (or self-notify when they cancel their own order). If you add a new cancellation path, dispatch the relevant notification explicitly inside the same `DB::transaction`.

Channel auth is in `routes/channels.php`: `App.Models.User.{id}` accepts only the channel owner. Don't add unscoped channels.

The `MessageSent` event (`Conversation` chat) is a **separate broadcast channel** (`private-conversation.{id}`) from the notification pipeline's `App.Models.User.{id}` channel — chat keeps its own `unreadMessageCount` badge and real-time bubble rendering via `MessageSent`; don't merge the two channels. They are not mutually exclusive, though: `ConversationService::sendMessage()` fires **both** — `broadcast(new MessageSent($message))->toOthers()` for the open chat thread, **and** `NewMessageNotification` (database + bell) so the recipient is told about a new message even when they aren't on the Messages page.

All Notification classes share the `BroadcastsAsArray` trait (`app/Notifications/Concerns/BroadcastsAsArray.php`), which implements `toBroadcast()` as `new BroadcastMessage($this->toArray($notifiable))`. This enforces the project-wide convention that broadcast payload = database payload. New Notification classes must `use BroadcastsAsArray, Queueable;` and must NOT add a custom `toBroadcast()`. The 9 mail-enabled classes additionally `use MailsAsArray;` (`app/Notifications/Concerns/MailsAsArray.php`), which implements **both** `toMail()` (built entirely from `toArray()`'s `title`/`body`/`url`) **and** `via()` (`['database', 'broadcast', 'mail']` — since none of the 9 classes vary this per-notifiable, it lives once in the trait rather than being copy-pasted into each class). Adding mail to a new event is therefore just `use MailsAsArray;` + `implements ShouldQueue` — no custom `toMail()` or `via()` needed. Any class NOT using `MailsAsArray` must still declare its own `via()` (typically `['database', 'broadcast']`).

**Locale for queued sends** — the 9 mail-enabled notifications are genuinely queued (`QUEUE_CONNECTION=database`, `ShouldQueue`), so by the time a queue worker renders one, the HTTP session that triggered it is long gone. `User implements HasLocalePreference` (`preferredLocale()` returns the `users.locale` column, persisted by `LocaleController::store()` whenever an authenticated user switches language, and seeded at registration by `CreateNewUser`) — Laravel's `NotificationSender::sendNow()` automatically wraps the entire send (`toArray()`/`toBroadcast()`/`toMail()`) in the recipient's preferred locale when this contract is implemented. This is a general fix, not mail-specific: it also corrects the same latent locale bug for the database/broadcast channels on these 9 classes. See ADR-016.

**Review notification trigger map (additions):**

| Event | Triggered in | Notification | Recipient |
|-------|--------------|--------------|-----------|
| Both parties reviewed → cooling starts | `ReviewService::checkAndStartCooling` | `ReviewCoolingStartedNotification` | Buyer + Seller |
| Edit/delete during cooling → cooling reset | `ReviewService::resetCoolingIfActive` | `ReviewCoolingResetNotification` | Counterparty |
| Cooling expires or 14-day timeout → release | `ReviewService::releaseOrder` | `ReviewReleasedNotification` | Buyer + Seller |
| Seller replies to product review | `ReviewService::addSellerReply` | `SellerReplyNotification` | Buyer |
