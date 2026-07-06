---
id: ADR-011
title: 商品規格/多變體（SKU）— 混合模式，Product 保留 price/stock 作為無規格預設值
date: 2026-07-06
status: Accepted
---

# ADR-011: 商品規格/多變體（SKU）— 混合模式，Product 保留 price/stock 作為無規格預設值

## Context

`Product` 原本是單一 price/stock 的扁平模型，尺寸、顏色等規格無法個別定價與計庫存，賣家只能為每個規格開一個獨立商品，庫存/銷量/評論會被拆散。即使專案仍在開發階段、沒有正式資料需要遷移，仍需決定：要不要讓每個商品都強制至少有一個 variant（全面 variant 化），或是讓「無規格商品」與「有規格商品」並存（混合模式）。

## Decision

- **混合模式**：`Product` 保留既有 `price`/`stock` 欄位作為無規格商品的價格/庫存來源；賣家可選擇性地為商品新增 `ProductOption`/`ProductOptionValue`/`ProductVariant`，一旦商品有 variants（`Product::hasVariants()` 為真），價格/庫存改以 variant 為準。多數商品沒有規格差異，混合模式讓這類商品完全不用碰 Option/Variant，建立流程維持原樣。
- **v1 範圍限定在核心閉環**：賣家後台建立 Option/Variant → 商品頁選規格顯示對應價格庫存 → 購物車/結帳以 variant 鎖庫存與計價 → 訂單留下 variant 快照。CSV 匯入匯出、低庫存 widget、優惠券、搜尋篩選、商品 Q&A 卡片維持商品層級不變——這些既有讀取點若因「全面 variant 化」而必須同時修改，會讓 v1 工作量遠超核心閉環,因此選擇混合模式讓這些既有子系統完全不受影響。
- **`cart_items`/`order_items` 的 nullable FK 選擇不同的刪除策略**：`cart_items.product_variant_id` 用 `cascadeOnDelete`（比照既有 `product_id` 慣例——變體被刪，購物車項目理應一併消失）；`order_items.product_variant_id` 用 `nullOnDelete`（比照 ADR-010 `messages.product_id` 的「歷史記錄不可因來源被刪而遺失」慣例）。`order_items` 另外新增 `variant_label` 字串快照欄位（比照既有 `product_name`/`product_image` 快照做法），使訂單歷史不依賴 variant 是否還存在。
- **`ProductVariant` 使用 SoftDeletes，`ProductOption`/`ProductOptionValue` 不用**：賣家在編輯頁整包送出 Option/Variant 列表時（`ProductVariantService::sync()`），Variant 若被移除只做軟刪除（保留歷史訂單可查、FK 不失聯）；Option/Value 屬於結構性設定、不出現在訂單快照裡，移除時直接硬刪除。
- **App 層防止重複規格組合，不靠 DB 限制**：同一 Product 底下，option_value_ids 的組合必須唯一，於 `ProductVariantService::syncVariants()` 用排序後的 id 字串比對，重複則丟 `ValidationException`。比照 ADR-008 coupon code、ADR-010 Q&A 對話「MySQL 無法表達 partial unique index」的既定處理方式，不引入 generated column 或額外索引來解決。
- **SKU 全域唯一，用查詢而非 wildcard array validation 檢查**：`product_variants.sku` 是 DB 層 unique，但 Laravel 的 wildcard array 驗證規則（`variants.*.sku`）無法表達「忽略自己這筆」的 unique 規則，因此在 `ProductVariantService::syncVariants()` 內用一次查詢（`withTrashed()->where('sku', ...)->whereKeyNot($variant?->id)->exists()`）取代，重複時丟出對應的 `ValidationException`。

## Consequences

- 優點：無規格商品完全不受影響，既有十幾處讀取 `product->price`/`product->stock` 的程式碼（CSV、低庫存、優惠券、搜尋篩選、dashboard、Q&A、seeder/factory、既有測試）都不需要改動。
- 優點：不需要任何既有資料遷移/backfill。
- 缺點：`Product`/`CartService`/`OrderService` 內部同時存在「無 variant」與「有 variant」兩條路徑（例如 `OrderService::createOrdersFromCart` 用 `$variant ?? $product` 判斷鎖庫存的對象），程式碼比全面 variant 化多一層分支判斷。
- 缺點（已知限制，不處理）：`cart_items` 若要在 DB 層擋「同商品同規格重複加入」需要 `unique(cart_id, product_id, product_variant_id)`，但 MySQL 對 NULL 視為互不相等，多筆 `product_variant_id IS NULL` 的列不會被擋；沿用專案既有慣例，交由應用層（`CartService::addItem` 顯式查詢後 update-or-create）防重複。

## Post-Review Fixes

`post-change-review`（code-review + security-review 並行）跑完後修正了以下項目：

- `OrderService::createOrdersFromCart` 鎖定 variant 時補上 `withTrashed()`（原本沒有，與 `finalizeCancellation` 不一致）——否則 variant 在買家加入購物車後被賣家軟刪除，結帳會靜默 fallback 成扣減父商品庫存，且訂單遺失 `product_variant_id`/`variant_label`。
- `ProductVariantManager.vue` 存檔成功後，改用 `onSuccess(page)` 把 `options`/`variants` 從伺服器回傳的最新 `product` 重新同步，避免連續存檔兩次時本地端仍帶著 `id: null` 導致重複建立、撞到 SKU 唯一性檢查。
- `CartController::store` 對 `variant_id` 改用 `find()`（而非 `findOrFail()`），變體不存在或已被軟刪除時回傳一致的 `back()->withErrors(...)`，不再讓 `ModelNotFoundException` 洩漏成裸的 404。
- `Products/Show.vue` 新增 `watch(displayStock, ...)` 收斂 `quantity`，切換到庫存較低的規格時不會殘留超過庫存上限的數量。
- `ProductVariantService::syncOptions`/`syncVariants` 改為先用 `whereIn` 批次撈出既有 options/values/variants 與同名 SKU，迴圈內只做記憶體比對，避免每筆 option/value/variant 各自下一次查詢。

## Simplify Pass

`/simplify`（reuse + simplification + efficiency + altitude 四個角度並行）跑完後套用了以下清理：

- `Product`/`ProductVariant` 的 `inStock()` 抽成共用 trait `App\Models\Concerns\HasStock`，兩個 model 都 `use` 它，不再各自複製一份相同的一行邏輯。
- `CartService::addItem`/`mergeGuestCart` 重複的「依 product_id + variant_id 找現有購物車列」`when()` 比對邏輯抽成私有方法 `matchVariant()`。
- `OrderService::createOrdersFromCart` 結帳時原本對每個購物車項目各自下一次 `ProductVariant::lockForUpdate()->find()`，改成先用 `whereIn` 一次鎖定該賣場所有需要的 variant（checkout 是熱路徑，值得批次化）；`finalizeCancellation` 的 increment 分支也改成跟 decrement 路徑一致的 `?:` query 寫法，兩處不再是兩種不同風格。
- `ProductController::show` 拿掉多餘的 `hasVariants()` exists 查詢，改成無條件 eager load `variants.optionValues.option`（沒有 variant 時 Eloquent 會自動跳過巢狀查詢，成本不變），再用記憶體判斷 `$product->variants->isNotEmpty()` 決定要不要載入 `options.values`。
- `CartController::store` 調整判斷順序，客端已帶 `variant_id` 時完全跳過 `hasVariants()` 查詢（短路求值）。
- 前端新增共用工具 `resources/js/Utils/variantCombination.js` 的 `combinationKey()`，取代 `ProductVariantManager.vue`／`Products/Show.vue` 各自手刻一份「排序後 join」的規格組合比對邏輯。
- `ProductVariantManager.vue`：`combinationLabel` 改成從 `options` 變動時才重算一次的 `labelByKey` Map 查表（原本每次呼叫都重新掃描 options/values）；`generateCombinations` 改成先建一次既有組合的 `Set` 再迴圈比對（原本是 O(組合數 × variants 數）；拿掉恆為 `'—'` 的無意義 `combinationLabel([])` 呼叫。
- `Products/Show.vue`：`displayPrice`/`displayComparePrice`/`displayStock`/`canAddToCart` 四個各自判斷 `hasVariants` 的 computed，合併成單一 `activeSource` computed 後各自衍生。

**修正時發現的迴歸**：批次鎖定 variant 後，原本想用已 eager load 的 `$cartItem->variant->optionLabel()` 省一次查詢，但 `CartItem::variant()` 走 `ProductVariant` 的 SoftDeletes 全域 scope，已軟刪除的 variant 會是 `null`，與批次查詢特意用 `withTrashed()` 保留的語意不一致，导致 variant 被軟刪後結帳的 `variant_label` 又變回 `null`（被 `tests/Feature/ProductVariantTest.php` 的迴歸測試抓到）。改為直接在批次鎖定查詢上 `with('optionValues.option')`，用鎖定後（含 trashed）的 `$variant` 取 label，維持零額外查詢又保留正確性。

**刻意跳過**：`ProductVariantService::syncVariants` 內每個 variant 仍各自呼叫一次 `optionValues()->sync()`（pivot 表 N+1）。這是賣家低頻的「儲存規格」操作，不像結帳是熱路徑，批次 diff pivot insert/delete 的複雜度不值得在此投入，故保留原樣。

## Alternatives Considered

- **全面 Variant 化**（每個商品至少一個「預設 variant」，`Product` 拿掉 `price`/`stock`）：概念更統一、只有一種讀取路徑，但即使專案仍在開發階段沒有資料遷移成本，仍會強迫十幾處既有讀取點（CSV 匯入匯出、低庫存 widget、優惠券、搜尋篩選、賣家/管理後台 dashboard、Q&A 商品卡片、seeder/factory、既有測試）在 v1 就一次全部跟著改，且對「本來就沒有規格」的多數商品增加不必要的建立儀式感；不採用。
