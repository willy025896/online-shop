# Product / Shop Listing Filters

Both `ProductController::index` and `ShopController::show` apply optional query-string filters to their product listings. Two conventions apply everywhere:

- **`$request->filled()`** — use this to test "present and not empty-string" for optional filters. Never use `!== null && !== ''` manually.
- **Partial reloads** — filter/sort navigations use `only: ['products', 'filters']` so the server skips the category query on every keystroke. The `categories` prop in both controllers is a **lazy closure** (`fn() => ...`) so Inertia's partial-reload mechanism skips evaluating it entirely when it is not in the `only` list.

Filters currently supported on `/products`:
`search` (full-text), `category`, `min_rating`, `min_price`, `max_price`, `sort`

Filters currently supported on `/shops/{shop}`:
`search` (LIKE), `category`, `min_price`, `max_price`, `sort`
