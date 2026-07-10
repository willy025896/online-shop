# Order Status Transitions & Logging

Seller status changes go through `Seller\OrderController::updateStatus`, guarded by `Order::canTransitionStatusTo()`. The rule is **forward-only** (by the `Order::STATUS_RANK` map) and rejects terminal sources (`completed`/`cancelled`) and orders with a pending cancellation — but it deliberately allows legitimate skips such as `pending → shipped` (cash-on-delivery) and `paid → completed` (virtual goods). Invalid transitions `abort(422)`.

**Every status change is logged** to the `order_status_logs` table. Logging is **event-based**: the `Order` model's `updated` event (registered in `Order::booted()`) writes a row whenever `status` changes, capturing `from_status` / `to_status` / `changed_by`. This auto-captures all paths (payment, seller update, cancellation finalize) without per-call-site code.

Two consequences to keep in mind when changing order status:

- **Atomicity** — the log insert fires inside the `updated` event, so it only commits atomically with the status change if the `$order->update()` runs inside a `DB::transaction`. All current status-mutating paths (`updateStatus`, `PaymentService::markAsPaid`, `OrderService` cancellation methods) are wrapped in a transaction for this reason. Wrap any new one too.
- **Bulk-update caveat** — Eloquent's `updated` event does **not** fire on query-builder bulk updates (`Order::where(...)->update(['status' => ...])`), so those would silently bypass the log. Always change order status via a model instance (`$order->update(...)`), never a bulk query.
