# Product / Shop / Category Listing Filters

`ProductController::index`, `ShopController::show`, and `CategoryController::show` all apply optional query-string filters to their product listings. Conventions that apply everywhere:

- **`$request->filled()`** — use this to test "present and not empty-string" for optional filters (search/category). Never use `!== null && !== ''` manually.
- **`min_rating`/price-range/sort are shared** — all three controllers call `applyProductSortAndFilters($query, $request)` from the `App\Http\Controllers\Concerns\FiltersProductListings` trait, which owns the `min_rating` filter (`Product::reviews_count`/`rating_sum`), `Product::scopePriceRange()`, and the `sort` switch (`latest`/`price_asc`/`price_desc`/`rating_desc` via `Product::scopeOrderByRating()`/`name`). Change sort/rating/price-filter behavior in the trait, not per-controller — the three pages are meant to stay in feature parity.
- The trait's `$query` param is **deliberately untyped** (not `Illuminate\Database\Eloquent\Builder`): `ShopController` passes `$shop->products()...`, and Eloquent's `Relation::__call()` forwards scope/where calls to the underlying builder but returns the *relation* instance when the forwarded call returns the same builder — a strict `Builder` type hint throws a `TypeError` for that caller.
- **Partial reloads** — filter/sort navigations use `only: ['products', 'filters']` so the server skips the category query on every keystroke. The `categories` prop (Product/Shop controllers) is a **lazy closure** (`fn() => ...`) so Inertia's partial-reload mechanism skips evaluating it entirely when it is not in the `only` list.
- Search/category filtering stays controller-specific (full-text on Products, `LIKE` on Shop, N/A on Category since it's already scoped to one category + its children) and is applied *before* calling the shared trait method.

Filters currently supported on `/products` (and `/`, which shares `ProductController::index`):
`search` (full-text), `category`, `min_rating`, `min_price`, `max_price`, `sort`

Filters currently supported on `/shops/{shop}`:
`search` (LIKE), `category`, `min_rating`, `min_price`, `max_price`, `sort`

Filters currently supported on `/categories/{category}`:
`min_rating`, `min_price`, `max_price`, `sort` (no `search`/`category` — the page is already scoped to one category tree)

## Frontend

- `Products/Index.vue` and `Categories/Show.vue` share `resources/js/Composables/useListingFilters.js` (per-click `router.get` navigation + apply-gated price inputs + `useInFlightLoading`). `Shop/Show.vue` uses a different reactive pattern (local refs + debounced `watch()`-triggered `applyFilters()`) and is not a fit for that composable.
- The min-rating chip UI (`4★+`/`3★+`) is shared via `resources/js/Components/MinRatingFilter.vue` (`v-model` of the star threshold, `null` when unset) — used identically by all three pages.
- Any prop/ref that resyncs from the server's `filters` response (e.g. after browser back/forward restores a cached Inertia page) must exclude apply-gated draft inputs (search text, price range) — resyncing those clobbers whatever the user is mid-typing whenever an unrelated filter changes. See `Shop/Show.vue`'s `watch(() => props.filters, ...)` for the pattern: it resyncs `sort`/`category`/`min_rating` (instant-apply, no draft state) but not `search`/price.
