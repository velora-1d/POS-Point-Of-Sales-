<?php

use App\Http\Controllers\KitchenDisplayController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrSelfServiceController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/api/v1/callback/pakasir', [PaymentWebhookController::class, 'handlePakasir'])
    ->name('payments.webhook.pakasir');

Route::get('/m/{tableToken}', [QrSelfServiceController::class, 'showMenu'])
    ->name('self-service.menu');
Route::post('/m/{tableToken}/checkout', [QrSelfServiceController::class, 'checkout'])
    ->name('self-service.checkout');
Route::get('/m/{tableToken}/orders/{orderNumber}', [QrSelfServiceController::class, 'showOrderStatus'])
    ->name('self-service.orders.status');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/kitchen', [KitchenDisplayController::class, 'index'])->name('kitchen.display');
    Route::post('/kitchen/orders/{order}/status', [KitchenDisplayController::class, 'updateStatus'])
        ->name('kitchen.orders.update-status');

    Route::get('/bar', function () {
        return Inertia::render('Bar/Display');
    })->name('bar.display');

    Route::get('/order', [OrderController::class, 'index'])->name('kasir.order');
    Route::post('/order', [OrderController::class, 'store'])->name('order.store');
    Route::post('/order/{order}/pay', [OrderController::class, 'pay'])->name('order.pay');
    Route::patch('/order/{order}', [OrderController::class, 'update'])->name('order.update');
    Route::post('/order/{order}/split-bill', [OrderController::class, 'splitBill'])->name('order.split-bill');
    Route::post('/orders/merge-bills', [OrderController::class, 'mergeBills'])->name('orders.merge-bills');
});

require __DIR__.'/auth.php';
