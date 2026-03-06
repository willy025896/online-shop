Backend (PHP)

- 9 個 Migrations — users 新增欄位 + 8 張新表 (shops, categories, products, product_images, carts, cart_items, orders, order_items)
- 8 個 Models — Shop, Category, Product, ProductImage, Cart, CartItem, Order, OrderItem + 更新 User model
- EnsureRole Middleware — 已建立並在 bootstrap/app.php 註冊為 role alias
- 3 個 Services — CartService, OrderService, PaymentService
- 3 個 Policies — ProductPolicy, OrderPolicy, ShopPolicy
- 12 個 Controllers — 公開 (Product, Shop, Category, Cart, Checkout, Order) + Seller (Dashboard, Register, Product, ProductImage, Shop, Order) + Admin (Dashboard, User, Shop, Category, Order, Product)
- Routes — 完整的 web.php，含公開/認證/seller/admin 四組路由
- HandleInertiaRequests — 新增 cartCount, userRole, flash 共享資料

Frontend (Vue)

- 2 個新 Layouts — SellerLayout, AdminLayout (含 sidebar 導航)
- 9 個共用 Components — ProductCard, ProductImageGallery, CartItemRow, CartSummary, CategoryTree, OrderStatusBadge, ImageUploader, SearchBar, Pagination
- 9 個 Pages — Products/Index, Products/Show, Shop/Index, Shop/Show, Categories/Show, Cart/Index, Checkout/Index, Orders/Index, Orders/Show

已完成 (Stage 1-3)

- [x] AppLayout 更新 (cart badge, Products/Shops/Orders nav, Seller/Admin Panel links, responsive)
- [x] Seller 頁面 — Dashboard, Register, Products (Index/Create/Edit), Orders (Index/Show), Shop Edit
- [x] Admin 頁面 — Dashboard, Users, Shops, Categories (CRUD), Orders, Products

已完成 (Stage 4)

- [x] Lang 檔案 — en + zh_TW 共 20 檔 (products, shop, categories, cart, checkout, orders, seller, admin, dashboard, components)
- [x] 新增語系切換功能
- [x] 補完選單、導覽列以及後台的功能按鈕等中文與英文語系資料
- [x] 後台也新增語系切換功能

已完成 (Stage 5)

- [x] Database factories — User, Shop, Category, Product, Order (含 state methods)
- [x] DatabaseSeeder — admin + customers + sellers + shops + products + categories + orders

已完成 (Stage 6)

- [x] Pest tests — 6 test files, 41 new tests (Product, Shop, Cart, Seller, Admin, Order)
- [x] Bug fix: Controller 加入 AuthorizesRequests trait
- [x] Bug fix: Migration 排序修正 (parent tables before child tables)
- [x] 測試資料隔離 — 所有測試均透過 Factory 自建資料，不依賴 DatabaseSeeder；RefreshDatabase 在每次測試後 rollback，seeder 資料不影響測試結果

已完成 (Stage 7)
- [x] 移除 Jetstream 預設 Dashboard 頁面

已完成 (Stage 8)

- [x] 商家頁面產品搜尋 — 輸入關鍵字即時過濾（400ms debounce），query param `search`
- [x] 商家頁面類別過濾 — 顯示該商家有商品的類別，點選 pill button 過濾，query param `category`
- [x] 商家頁面排序 — Latest / Price Low-High / Price High-Low / Name A-Z，query param `sort`

已完成 (Stage 9)

- [x] 移除導覽列 Dashboard 連結（桌面 + 手機版）
- [x] Orders 移至右側 account dropdown 選單（桌面）及 settings 區塊（手機版）
- [x] 未登入時購物車旁顯示 Log In 按鈕（桌面 + 手機版）

已完成 (Stage 10 — 頁面內容 i18n)

- [x] Lang 檔案補完（en + zh_TW）：products, shop, checkout, orders, seller, admin, dashboard + 新建 categories.php
- [x] Products/Index.vue
- [x] Products/Show.vue
- [x] Shop/Index.vue
- [x] Shop/Show.vue
- [x] Cart/Index.vue
- [x] Checkout/Index.vue
- [x] Orders/Index.vue
- [x] Orders/Show.vue
- [x] Categories/Show.vue
- [x] Dashboard.vue
- [x] Seller/Dashboard.vue
- [x] Seller/Products/Index.vue
- [x] Seller/Products/Create.vue
- [x] Seller/Products/Edit.vue
- [x] Seller/Orders/Index.vue
- [x] Seller/Orders/Show.vue
- [x] Seller/Register.vue
- [x] Seller/Shop/Edit.vue
- [x] Admin/Dashboard.vue
- [x] Admin/Users/Index.vue
- [x] Admin/Shops/Index.vue
- [x] Admin/Categories/Index.vue
- [x] Admin/Orders/Index.vue
- [x] Admin/Products/Index.vue

已完成 (Stage 10 — 全部)
