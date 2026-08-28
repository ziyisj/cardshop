<?php

use App\Http\Controllers\Admin\AppUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CardKeyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Shop\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 发卡前台
|--------------------------------------------------------------------------
*/
Route::get('/', [ShopController::class, 'index'])->name('shop.index');
Route::get('/query', [ShopController::class, 'query'])->name('shop.query');
Route::get('/product/{slug}', [ShopController::class, 'show'])->name('shop.show');
Route::post('/checkout', [ShopController::class, 'checkout'])->name('shop.checkout');
Route::get('/pay/{orderNo}', [ShopController::class, 'pay'])->name('shop.pay');
Route::post('/pay/{orderNo}/mock', [ShopController::class, 'mockPaid'])->name('shop.mockPaid');
Route::get('/result/{orderNo}', [ShopController::class, 'result'])->name('shop.result');

/*
|--------------------------------------------------------------------------
| 管理后台
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('products', ProductController::class)->except('show');

        Route::get('cards', [CardKeyController::class, 'index'])->name('cards.index');
        Route::post('cards/generate', [CardKeyController::class, 'generate'])->name('cards.generate');
        Route::get('cards/export', [CardKeyController::class, 'export'])->name('cards.export');
        Route::post('cards/{card}/disable', [CardKeyController::class, 'disable'])->name('cards.disable');
        Route::delete('cards/{card}', [CardKeyController::class, 'destroy'])->name('cards.destroy');

        Route::resource('users', AppUserController::class)->except('show');
        Route::post('users/{user}/extend', [AppUserController::class, 'extend'])->name('users.extend');
        Route::post('users/{user}/unbind', [AppUserController::class, 'unbind'])->name('users.unbind');

        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/paid', [AdminOrderController::class, 'markPaid'])->name('orders.paid');
    });
});
