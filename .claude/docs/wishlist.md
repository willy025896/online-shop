# Wishlist (收藏 / 願望清單)

`WishlistService` (`app/Services/WishlistService.php`) is the single entry point for wishlist mutations. All operations are scoped to `Auth::id()`.

**Wishlist state** is stored in the `wishlist_items` pivot table with `unique(user_id, product_id)`.

Key design points:
- **Auth-only** — wishlist routes are inside the auth middleware group; guests clicking the heart icon are redirected to login, not silently ignored.
- **`toggle(Product)`** — uses `firstOrCreate` on the add path to avoid a `QueryException` on concurrent/double-clicked requests.
- **`remove(Product)`** — a dedicated remove method used by `WishlistController::destroy()`; never uses `toggle()` to avoid the semantic bug where DELETE on an un-favorited product would add it.
- **`getItemsWithProducts()`** — returns products via the `User::favoritedProducts()` `BelongsToMany` relation, ordered by `pivot.created_at DESC` (most-recently favorited first).
- **`wishlistProductIds` shared prop** — shipped on every authenticated request as a lazy closure; `FavoriteButton` reads it to determine fill state. The navbar badge derives count via `.length` — no separate count query.
- **`FavoriteButton.vue`** uses Inertia partial reload (`only: ['wishlistProductIds', 'flash']`) so toggling a heart reloads only those two props, not the full page.
- Adding a wishlist item to cart (`cart.store`) does **not** remove it from the wishlist — the two are independent.
