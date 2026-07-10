# Review System (雙向盲評)

`ReviewService` (`app/Services/ReviewService.php`) is the single entry point for all review mutations. **All methods wrap in `DB::transaction` with `Order::lockForUpdate()`** to prevent race conditions.

**Review state** is tracked on the `orders` table via three columns:
- `completed_at` — set automatically by `Order::booted() updating` hook when `status → completed`
- `review_cooling_until` — set when both parties submit; cleared if either edits/deletes during cooling
- `review_released_at` — set at release; **NOT NULL = permanently locked, no further writes allowed**

Rule: `isReviewWindowOpen()` = `review_released_at === null`. `isInCoolingPeriod()` = cooling_until set & future & window open.

**Release is handled by `php artisan reviews:release`** (registered in `routes/console.php`, runs every 10 minutes via `Schedule::command(...)->everyTenMinutes()`). It uses `chunkById(100)` to prevent OOM. Two trigger conditions:
1. `review_cooling_until <= now()` — normal path after 24h cooling
2. `completed_at <= now()-14d` — 14-day timeout (fires regardless of whether reviews exist, to prevent long-tail retaliation)

**Aggregate columns** (`reviews_count`, `rating_sum` on `products`/`shops`; `buyer_reviews_count`, `buyer_rating_sum` on `users`) are updated **only at release time** in `updateAggregates()`. Before release, aggregates never change — this preserves the blind-reveal guarantee. `updateAggregates` uses grouped updates (one `UPDATE` per product, one for the shop) rather than N individual UPDATEs.

**Bulk-update caveat (same as order status logging)**: `completed_at` is set by a model `updating` event hook, which does **not** fire on query-builder bulk updates. Any code that bulk-updates `orders.status` to `completed` must also manually set `completed_at`. Use `$order->update(...)` on model instances, never `Order::where(...)->update(...)`.

**`Order::productReviews()` relation** uses `hasManyThrough(ProductReview::class, OrderItem::class)` and is scoped to `STATUS_PUBLISHED`. Do not use this relation when you need all reviews (including hidden) — query `ProductReview` directly.

**Review ownership rules:**
- Buyer can review an `OrderItem` only if `$order->user_id === $user->id && status === COMPLETED && isReviewWindowOpen()`
- Seller can review a Buyer only if `$order->shop_id === $seller->shop->id && status === COMPLETED && isReviewWindowOpen()`
- Both checks are enforced in `ReviewService` (Service layer), not just Policy layer — defense in depth

**PII protection in review responses:**
- Public product page: `->with(['user:id,name,profile_photo_path'])` only — no email/phone/role
- Seller buyer-credit page: `->with(['shop:id,name'])` with explicit `select(...)` — no order shipping data
