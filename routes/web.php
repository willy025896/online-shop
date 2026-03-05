<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
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

    Route::get('/advertisement', function () {
        return Inertia::render('Advertisement');
    })->name('advertisement');

    Route::get('/members', function () {
        return Inertia::render('Members');
    })->name('members');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    // Customer orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/pay', [OrderController::class, 'simulatePayment'])->name('orders.pay');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Seller registration (any authenticated user)
    Route::get('/seller/register', [App\Http\Controllers\Seller\RegisterController::class, 'create'])->name('seller.register');
    Route::post('/seller/register', [App\Http\Controllers\Seller\RegisterController::class, 'store'])->name('seller.register.store');
});

// Seller routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:seller,admin',
])->prefix('seller')->name('seller.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Seller\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', App\Http\Controllers\Seller\ProductController::class)->except(['show']);
    Route::post('/products/{product}/images', [App\Http\Controllers\Seller\ProductImageController::class, 'store'])->name('products.images.store');
    Route::delete('/products/images/{image}', [App\Http\Controllers\Seller\ProductImageController::class, 'destroy'])->name('products.images.destroy');

    Route::get('/orders', [App\Http\Controllers\Seller\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [App\Http\Controllers\Seller\OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [App\Http\Controllers\Seller\OrderController::class, 'updateStatus'])->name('orders.status');

    Route::get('/shop/edit', [App\Http\Controllers\Seller\ShopController::class, 'edit'])->name('shop.edit');
    Route::put('/shop', [App\Http\Controllers\Seller\ShopController::class, 'update'])->name('shop.update');
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
    Route::get('/products', [App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');
});
