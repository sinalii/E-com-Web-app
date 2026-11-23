<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\AdminController;

Route::get('/', [ShopController::class, 'home'])->name('index');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/products/{product}', [ShopController::class, 'show'])->name('products.show');
Route::get('/login', [AuthController::class, 'loginForm'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:5,1'])->name('login.store');
Route::get('/register', [AuthController::class, 'registerForm'])->middleware('guest')->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest')->name('register.store');
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');

foreach (['about', 'contact', 'services'] as $page) {
    Route::get('/'.$page, [ShopController::class, 'page'])->defaults('page', $page)->name($page);
}

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::middleware('role:customer')->group(function () {
        Route::get('/account', [AccountController::class, 'index'])->name('account');
        Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
        Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->whereNumber('product')->name('wishlist.toggle');
        Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store'])->name('products.reviews.store');
        Route::post('/cart/items', [CartController::class, 'add'])->name('cart.add');
        Route::patch('/cart/items/{item}', [CartController::class, 'update'])->whereNumber('item')->name('cart.update');
        Route::delete('/cart/items/{item}', [CartController::class, 'remove'])->whereNumber('item')->name('cart.remove');
        Route::get('/cart', [CartController::class, 'index'])->name('cart');
        Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout');
        Route::post('/checkout', [CartController::class, 'placeOrder'])->name('checkout.place');
        Route::get('/thankyou', [CartController::class, 'thankyou'])->name('thankyou');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/categories/{category}/subcategories', [AdminController::class, 'categorySubcategories'])
            ->whereNumber('category')->name('categories.subcategories');
        Route::get('/customers/{customer}', [AdminController::class, 'customerDetails'])->whereNumber('customer')->name('customer.show');
        Route::patch('/customers/{customer}/status', [AdminController::class, 'updateCustomerStatus'])->whereNumber('customer')->name('customer.status');
        Route::get('/profile', [AdminController::class, 'section'])->defaults('section', 'settings')->name('profile');
        Route::put('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');
        Route::get('/orders/{id}', [AdminController::class, 'orderDetails'])->whereNumber('id')->name('order.show');
        Route::get('/{section}', [AdminController::class, 'section'])->name('section');
        Route::post('/{section}', [AdminController::class, 'store'])->name('resource.store');
        Route::put('/{section}/{id}', [AdminController::class, 'update'])->whereNumber('id')->name('resource.update');
        Route::delete('/{section}/{id}', [AdminController::class, 'destroy'])->whereNumber('id')->name('resource.destroy');
    });
});
