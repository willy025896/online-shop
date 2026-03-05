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

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
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

});
