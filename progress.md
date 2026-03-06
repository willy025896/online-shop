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

尚未完成

- Database seeder + factories
- Pest tests
