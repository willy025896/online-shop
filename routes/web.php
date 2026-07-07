<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Locale switcher
Route::post('/locale', [LocaleController::class, 'store'])->name('locale.store');

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    return response("User-agent: *\nAllow: /\n\nSitemap: ".route('sitemap')."\n")
        ->header('Content-Type', 'text/plain');
})->name('robots');

// Public routes
Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/shop', [ShopController::class, 'index'])->name('shops.index');
Route::get('/shop/{shop:slug}', [ShopController::class, 'show'])->name('shops.show');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

// Cart (guest + auth)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

// Authenticated routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    Route::get('/members', [App\Http\Controllers\MemberController::class, 'index'])->name('members');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/coupon/preview', [App\Http\Controllers\CouponController::class, 'preview'])->name('checkout.coupon.preview');

    // Customer orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/pay', [OrderController::class, 'simulatePayment'])->name('orders.pay');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/returns', [OrderController::class, 'requestReturn'])->name('orders.returns.store');
    Route::post('/orders/{order}/conversation', [OrderController::class, 'startConversation'])->name('orders.conversation');

    // Conversations
    Route::post('/products/{product:slug}/ask', [ConversationController::class, 'askAboutProduct'])->name('products.ask');
    Route::get('/messages', [ConversationController::class, 'index'])->name('messages.index');
    Route::get('/messages/{conversation}', [ConversationController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}', [ConversationController::class, 'storeMessage'])->name('messages.store');
    Route::post('/messages/{conversation}/read', [ConversationController::class, 'markAsRead'])->name('messages.read');

    // Seller registration (any authenticated user)
    Route::get('/seller/register', [App\Http\Controllers\Seller\RegisterController::class, 'create'])->name('seller.register');
    Route::post('/seller/register', [App\Http\Controllers\Seller\RegisterController::class, 'store'])->name('seller.register.store');

    // Product reviews (buyer)
    Route::get('/orders/{order}/review', [App\Http\Controllers\ProductReviewController::class, 'create'])->name('reviews.create');
    Route::post('/reviews/products', [App\Http\Controllers\ProductReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/reviews/products/{productReview}', [App\Http\Controllers\ProductReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/products/{productReview}', [App\Http\Controllers\ProductReviewController::class, 'destroy'])->name('reviews.destroy');

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('{id}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::post('read-all', [NotificationController::class, 'markAllRead'])->name('read_all');
        Route::delete('{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });
});

// Seller routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:seller',
])->prefix('seller')->name('seller.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Seller\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/products/export', [App\Http\Controllers\Seller\ProductController::class, 'export'])->name('products.export');
    Route::get('/products/import', [App\Http\Controllers\Seller\ProductController::class, 'importForm'])->name('products.import.form');
    Route::post('/products/import', [App\Http\Controllers\Seller\ProductController::class, 'import'])->name('products.import');
    Route::resource('products', App\Http\Controllers\Seller\ProductController::class)->except(['show']);
    Route::post('/products/{product}/images', [App\Http\Controllers\Seller\ProductImageController::class, 'store'])->name('products.images.store');
    Route::delete('/products/images/{image}', [App\Http\Controllers\Seller\ProductImageController::class, 'destroy'])->name('products.images.destroy');
    Route::patch('/products/{product}/variants', [App\Http\Controllers\Seller\ProductVariantController::class, 'update'])->name('products.variants.update');

    Route::get('/orders', [App\Http\Controllers\Seller\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [App\Http\Controllers\Seller\OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [App\Http\Controllers\Seller\OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{order}/cancel', [App\Http\Controllers\Seller\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/cancellation/approve', [App\Http\Controllers\Seller\OrderController::class, 'approveCancellation'])->name('orders.cancellation.approve');
    Route::post('/orders/{order}/cancellation/reject', [App\Http\Controllers\Seller\OrderController::class, 'rejectCancellation'])->name('orders.cancellation.reject');
    Route::post('/orders/{order}/returns/approve', [App\Http\Controllers\Seller\OrderController::class, 'approveReturn'])->name('orders.returns.approve');
    Route::post('/orders/{order}/returns/reject', [App\Http\Controllers\Seller\OrderController::class, 'rejectReturn'])->name('orders.returns.reject');

    Route::get('/shop/edit', [App\Http\Controllers\Seller\ShopController::class, 'edit'])->name('shop.edit');
    Route::put('/shop', [App\Http\Controllers\Seller\ShopController::class, 'update'])->name('shop.update');

    // Coupons (discount codes)
    Route::resource('coupons', App\Http\Controllers\Seller\CouponController::class)->except(['show']);

    // Seller: product reviews list + reply
    Route::get('/reviews', [App\Http\Controllers\Seller\ProductReviewIndexController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{productReview}/reply', [App\Http\Controllers\Seller\ReviewReplyController::class, 'store'])->name('reviews.reply');

    // Seller: buyer reviews (evaluate buyer)
    Route::get('/orders/{order}/review-buyer', [App\Http\Controllers\Seller\BuyerReviewController::class, 'create'])->name('buyer-reviews.create');
    Route::post('/orders/{order}/review-buyer', [App\Http\Controllers\Seller\BuyerReviewController::class, 'store'])->name('buyer-reviews.store');
    Route::patch('/buyer-reviews/{buyerReview}', [App\Http\Controllers\Seller\BuyerReviewController::class, 'update'])->name('buyer-reviews.update');
    Route::delete('/buyer-reviews/{buyerReview}', [App\Http\Controllers\Seller\BuyerReviewController::class, 'destroy'])->name('buyer-reviews.destroy');

    // Seller: view buyer credit
    Route::get('/buyers/{user}', [App\Http\Controllers\Seller\BuyerCreditController::class, 'show'])->name('buyers.show');

    // Seller: payout history (read-only — payouts are admin-triggered)
    Route::get('/payouts', [App\Http\Controllers\Seller\PayoutController::class, 'index'])->name('payouts.index');
});

// Seller-only preference (excludes admin — admins have no seller dashboard)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:seller',
])->prefix('seller')->name('seller.')->group(function () {
    Route::patch('/preferences', [App\Http\Controllers\Seller\PreferenceController::class, 'update'])->name('preferences.update');
});

// Admin routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:admin',
])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [App\Http\Controllers\Admin\UserController::class, 'updateRole'])->name('users.role');

    Route::get('/shops', [App\Http\Controllers\Admin\ShopController::class, 'index'])->name('shops.index');
    Route::patch('/shops/{shop}/status', [App\Http\Controllers\Admin\ShopController::class, 'updateStatus'])->name('shops.status');

    Route::get('/categories', [App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/orders', [App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
    Route::get('/products', [App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');

    // Platform-wide coupons (shop_id null)
    Route::resource('coupons', App\Http\Controllers\Admin\CouponController::class)->except(['show']);

    Route::get('/audit-logs', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

    // Platform commission & seller payouts (ADR-014)
    Route::get('/payouts', [App\Http\Controllers\Admin\PayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/run', [App\Http\Controllers\Admin\PayoutController::class, 'run'])->name('payouts.run');
});
