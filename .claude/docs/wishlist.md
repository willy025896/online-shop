# Wishlist (收藏 / 願望清單)

`WishlistService` (`app/Services/WishlistService.php`) is the single entry point for wishlist mutations. All operations are scoped to `Auth::id()`.

**Wishlist state** is stored in the `wishlist_items` pivot table with `unique(user_id, product_id)`.

Key design points:
- **Auth-only** — wishlist routes are inside the auth middleware group; guests clicking the heart icon are redirected to login, not silently ignored.
- **`toggle(Product)`** — uses `firstOrCreate` on the add path to avoid a `QueryException` on concurrent/double-clicked requests.
- **`remove(Product)`** — a dedicated remove method used by `WishlistController::destroy()`; never uses `toggle()` to avoid the semantic bug where DELETE on an un-favorited product would add it.
- **`getItemsWithProducts()`** — returns a `LengthAwarePaginator` (12/page) of products via the `User::favoritedProducts()` `BelongsToMany` relation, ordered by `pivot.created_at DESC` (most-recently favorited first). No guest branch — `wishlist.index` is already behind the `auth` middleware group, so `Auth::user()` is never null when this runs.
- **Out-of-range `page` redirect** — `WishlistController::index()` compares `$products->currentPage()` against `$products->lastPage()` and redirects to the last valid page when the request is out of range. This matters because `destroy()` uses `back()` to return to the referring page (e.g. `?page=2`) — removing the last item on a non-first page would otherwise leave the paginator returning an empty `data` array with no way back, since `Pagination.vue` hides its nav once `links.length <= 3`.
- **`wishlistProductIds` shared prop** — shipped on every authenticated request as a lazy closure; `FavoriteButton` reads it to determine fill state. The navbar badge derives count via `.length` — no separate count query.
- **`FavoriteButton.vue`** uses Inertia partial reload (`only: ['wishlistProductIds', 'flash']`) so toggling a heart reloads only those two props, not the full page.
- Adding a wishlist item to cart (`cart.store`) does **not** remove it from the wishlist — the two are independent.
- **Price-drop / back-in-stock notifications** — `Product::booted()`'s `updated` event (mirrors `Order::booted()`'s pattern) detects a price decrease or a 0→positive stock change on an active, non-trashed product, then calls `WishlistService::notifyPriceDrop()`/`notifyBackInStock()`, which fan out via `Product::favoritedByUsers()` (the inverse `BelongsToMany` of `User::favoritedProducts()`, same `wishlist_items` pivot) to every user who favorited that product. No cooldown/dedup — every qualifying `update()` notifies again, by design (kept intentionally simple). Detection is Product-level only, not per-`ProductVariant`. See `.claude/docs/notifications.md` for the trigger map and why these two Notification classes are `ShouldQueue` despite having no mail channel.
