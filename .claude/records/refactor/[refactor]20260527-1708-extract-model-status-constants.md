# [refactor] Extract model status and role constants

**日期：** 2026-05-27 17:08
**類型：** refactor

## 變更內容

將四個 Model 中重複出現的魔術字串（角色、狀態、取消發起方）提取為具名常數，並更新所有參照處使用這些常數。行為完全不變，但消除了 typo 導致靜默失敗的風險。

## 異動檔案

- `app/Models/User.php` — 新增 `ROLE_CUSTOMER/SELLER/ADMIN`，更新 `isAdmin/isSeller/isCustomer`
- `app/Models/Shop.php` — 新增 `STATUS_PENDING/APPROVED/SUSPENDED`，更新 `isApproved`
- `app/Models/Product.php` — 新增 `STATUS_DRAFT/ACTIVE/INACTIVE`，更新 `scopeActive`
- `app/Models/OrderCancellation.php` — 新增 `STATUS_REQUESTED/APPROVED/REJECTED` 和 `INITIATED_BY_BUYER/SELLER`
- `app/Models/Order.php` — `pendingCancellation` 和 `wasCancellationRejected` 改用 `OrderCancellation::STATUS_*`
- `app/Services/OrderService.php` — 所有取消流程中的字串改用 `OrderCancellation::*` 常數
- `app/Http/Controllers/Seller/RegisterController.php` — `'pending'`→`Shop::STATUS_PENDING`，`'seller'`→`User::ROLE_SELLER`
- `app/Http/Controllers/Admin/UserController.php` — 角色驗證改用 `User::ROLE_*` 常數組合
- `app/Http/Controllers/Admin/ShopController.php` — 狀態驗證改用 `Rule::in([Shop::STATUS_*])`
- `app/Http/Controllers/Admin/DashboardController.php` — `'pending'`→`Shop::STATUS_PENDING`
- `app/Http/Controllers/ShopController.php` — `'approved'`/`'active'` 改用對應常數
- `app/Http/Controllers/ProductController.php` — `'active'` 改用 `Product::STATUS_ACTIVE`
- `app/Http/Controllers/Seller/ProductController.php` — 狀態驗證改用 `Product::STATUS_*` 常數組合

## 實作思路

遵循 `Order` model 已有的 `STATUS_*` 常數模式，統一套用至其餘三個 model。驗證規則中的 `'in:...'` 字串無法直接引用常數，因此 `Admin/ShopController` 改用 `Rule::in()`，其餘用 `implode(',', [...])` 保持與現有風格一致。`OrderCancellation` model 本身沒有使用這些常數的方法，但作為定義的唯一來源，讓 `Order` 和 `OrderService` 都指向它。
