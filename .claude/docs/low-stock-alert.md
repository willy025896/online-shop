# Low Stock Alert

`Product::scopeLowStock($query, ?int $threshold = null)` is the **single source of truth** for "what counts as low stock" — `stock <= threshold` (includes out-of-stock at 0), defaulting to `config('inventory.low_stock_threshold')`. The threshold lives in `config/inventory.php` (overridable via `INVENTORY_LOW_STOCK_THRESHOLD` env), same config-driven pattern as Shipping. Don't hard-code a stock cutoff anywhere — call the scope.

Surfaced in two places, both scoped to the seller's own shop:
- **Seller dashboard** — a toggleable `low_stock` widget (part of `DEFAULT_WIDGETS`; whitelist it in `PreferenceController` when adding widget keys) showing a count badge + the 5 lowest-stock products, rendered by `LowStockAlert.vue`. It is **period-independent** (current inventory, not a time window), so its data is not in the period `only:` partial-reload list — but `low_stock_count` rides inside the `stats` prop which *is* reloaded, and stays correct because the controller recomputes it every request.
- **Products list** — a `low_stock` boolean filter (`ProductController::index`, tested with `$request->boolean()` since it is a flag, not a value filter) plus an amber/red stock badge on each row.
