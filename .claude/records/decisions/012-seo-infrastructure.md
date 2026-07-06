---
id: ADR-012
title: SEO 基礎建設（Sitemap、robots.txt、OG Meta）
date: 2026-07-06
status: Accepted
---

# ADR-012: SEO 基礎建設（Sitemap、robots.txt、OG Meta）

## Context

專案目前完全沒有對外 SEO 基礎：沒有 `sitemap.xml`、沒有 `robots.txt`、商品/賣場頁沒有 `meta description` 或 Open Graph 標籤。使用者明確表示要開始對外經營流量，這是前提建設。

專案是純 Inertia.js SPA，**沒有啟用 SSR**（未安裝 `@vue/server-renderer`、無 `resources/js/ssr.js`、`vite.config.js` 無 ssr 設定）。這件事直接決定 OG meta 該放在哪裡：

- Inertia 的 `<Head>` 元件（現有 `AppLayout.vue` 用它設定 `<title>`）是**在瀏覽器端由 Vue 掛載後才寫入 DOM**。
- Facebook/Twitter/LINE/Slack 等社群分享的「連結預覽」爬蟲**不會執行 JavaScript**，只讀取伺服器回傳的原始 HTML。
- 若只用 Inertia `<Head>` 元件塞 `og:title`/`og:description`/`og:image`，這些爬蟲會抓到空的/預設的 meta，分享預覽功能形同沒做。

## Decision

1. **Sitemap**：新增 `SitemapController@index`，路由 `GET /sitemap.xml`。內容涵蓋首頁、賣場列表、所有 `Product::active()`、`Shop::status=approved`、`Category::active()`，`lastmod` 取各自 `updated_at`。整份 XML 用 `Cache::remember('sitemap.xml', 1 hour, ...)` 快取，換取效能，可接受最多 1 小時的資料延遲。自己寫，不引入 `spatie/laravel-sitemap` 套件（見下方 Alternatives）。
2. **robots.txt**：`GET /robots.txt` 回傳純文字，內容指向 `route('sitemap')`。
3. **OG Meta 放在 Blade root view（`resources/views/app.blade.php`），不是 Inertia `<Head>` 元件**：個別 controller（目前為 `ProductController@show`、`ShopController@show`、`CategoryController@show`）額外傳入一個 `seo` prop（`title`/`description`/`image`/`url`），`app.blade.php` 從 `$page['props']['seo'] ?? []` 讀出並直接輸出 `<meta>` 標籤到**初始伺服器回應**裡，沒有 `seo` prop 的頁面 fallback 成全站預設描述。這樣不論是否執行 JS，第一次請求的 HTML 就帶有正確 meta，社群爬蟲與 Google 首次爬取都能拿到。
4. Inertia 既有的 `<Head :title>`（`AppLayout.vue`）維持不動，繼續負責 SPA 導覽時瀏覽器分頁標題的即時更新——這與 OG meta 的用途不同，不衝突。
5. **`seo` 比照可見性/狀態守門**：`ProductController@show` 只在 `$isAvailable`（`status === STATUS_ACTIVE`）為真時才組出 `seo`，草稿/下架商品的 `seo` 為 `null`，落回全站預設描述，不對外廣播未發布商品的名稱/描述/圖片。`ShopController@show` 原本就對未核准賣場直接 404，天然滿足同樣的守門。`SitemapController` 的商品查詢額外用 `whereHas('shop', ...STATUS_APPROVED)` 排除「商品本身 active 但所屬賣場已停權」的情況，與賣場清單的收錄條件保持一致。
6. **Sitemap 快取 key 依請求 host 分開**（`'sitemap.xml.'.$request->getHost()`）：`route()` 產生的網址底層用 `UrlGenerator::formatRoot()`，在沒有呼叫 `URL::forceRootUrl()` 的情況下會用當次請求的 host（見 `vendor/laravel/framework/src/Illuminate/Routing/UrlGenerator.php:634`），而不是固定用 `config('app.url')`。若站台可能同時被多個網域/別名觸達，避免只用同一把 cache key 導致某個網域的請求把另一個網域的 URL 快取進全站唯一的 sitemap。

## Consequences

- 優點：
  - 不需要導入 SSR 就能讓社群分享預覽與搜尋引擎首次索引拿到正確的 meta 內容。
  - Sitemap/robots 都是新增路由與 controller，不動任何既有邏輯，風險低。
  - Cache 換取效能，作法與專案既有的 dashboard 週期查詢等無強一致性需求的場景一致。
- 缺點：
  - `seo` prop 需要每個想自訂 OG 內容的 controller 手動傳入，不是全站自動生效（設計上刻意如此，避免預設值蓋掉頁面專屬內容）。
  - Sitemap 有最長 1 小時的資料延遲（新商品上架後最多 1 小時才會出現在 sitemap）。
  - 商品/賣場沒有獨立的 `meta_description` 欄位，目前用 `Str::limit(strip_tags($description), 155)` 從既有欄位動態產生；未來若要精修文案需另外加欄位。

## Alternatives Considered

- **導入 Inertia SSR 讓 `<Head>` 元件在伺服器端渲染**：能讓所有頁面自動獲得正確 meta（含日後任何用 `<Head>` 加的標籤），但需要額外的 Node SSR process 部署與維運成本，對這個階段的需求（先讓 sitemap/OG 能動）是過度投資，之後流量規模需要更完整 SEO（例如全頁 SSR 首屏渲染）時可重新評估。
- **`spatie/laravel-sitemap` 套件**：功能更完整（支援 sitemap index、圖片 sitemap 等），但目前規模用不到，且專案慣例是自寫 Service/Controller（見 `RecommendationService`）而非引套件堆功能；先以自寫版本滿足需求，規模成長後可替換。
- **只用 Inertia `<Head>` 元件做 OG meta**：最簡單，但如 Context 所述，社群爬蟲不執行 JS 會直接失效，不採用。
