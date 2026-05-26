<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\InventoryAlertController;
use App\Http\Controllers\InventoryExpiryController;
use App\Http\Controllers\KitchenDisplayController;
use App\Http\Controllers\OnlineOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductHppController;
use App\Http\Controllers\ProductStockController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrSelfServiceController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TableReservationController;
use App\Http\Controllers\CustomerController;
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

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::post('/api/v1/callback/pakasir', [PaymentWebhookController::class, 'handlePakasir'])
    ->name('payments.webhook.pakasir');
Route::post('/api/v1/online-orders/webhook/gofood', [OnlineOrderController::class, 'storeGofoodWebhook'])
    ->name('online-orders.webhook.gofood');
Route::post('/api/v1/online-orders/webhook/grabfood', [OnlineOrderController::class, 'storeGrabfoodWebhook'])
    ->name('online-orders.webhook.grabfood');

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

    Route::get('/bar', [KitchenDisplayController::class, 'barIndex'])->name('bar.display');
    Route::post('/bar/orders/{order}/approve', [KitchenDisplayController::class, 'approveBar'])
        ->name('bar.orders.approve');
    Route::get('/online-orders', [OnlineOrderController::class, 'index'])->name('online-orders.index');

    Route::get('/tables/layout', [OrderController::class, 'tableLayout'])->name('tables.layout');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::get('/schedules', [EmployeeScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [EmployeeScheduleController::class, 'store'])->name('schedules.store');
    Route::post('/schedules/bulk', [EmployeeScheduleController::class, 'bulkStore'])->name('schedules.bulk-store');
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::patch('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shifts/open', [ShiftController::class, 'open'])->name('shifts.open');
    Route::post('/shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');
    Route::get('/reports/sales', [SalesReportController::class, 'index'])->name('reports.sales.index');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/hpp', [ProductHppController::class, 'index'])->name('products.hpp');
    Route::get('/products/stocks', [ProductStockController::class, 'index'])->name('products.stock');
    Route::patch('/products/stocks/{product}', [ProductStockController::class, 'update'])->name('products.stock.update');
    Route::put('/products/{product}/ingredients', [ProductHppController::class, 'updateIngredients'])->name('products.ingredients.update');
    Route::get('/promos', [PromoController::class, 'index'])->name('promos.index');
    Route::post('/promos', [PromoController::class, 'store'])->name('promos.store');
    Route::patch('/promos/{promo}', [PromoController::class, 'update'])->name('promos.update');
    Route::get('/raw-materials', [RawMaterialController::class, 'index'])->name('raw-materials.index');
    Route::post('/raw-materials', [RawMaterialController::class, 'store'])->name('raw-materials.store');
    Route::patch('/raw-materials/{rawMaterial}', [RawMaterialController::class, 'update'])->name('raw-materials.update');
    Route::post('/raw-materials/{rawMaterial}/add-stock', [RawMaterialController::class, 'addStock'])->name('raw-materials.add-stock');
    Route::post('/raw-materials/{rawMaterial}/adjust-stock', [RawMaterialController::class, 'adjustStock'])->name('raw-materials.adjust-stock');
    Route::get('/stock-alerts', [InventoryAlertController::class, 'index'])->name('stock-alerts.index');
    Route::get('/expired-tracking', [InventoryExpiryController::class, 'index'])->name('expired-tracking.index');
    Route::post('/expired-tracking/{inventoryExpiry}/handle', [InventoryExpiryController::class, 'handle'])->name('expired-tracking.handle');
    Route::post('/table-reservations', [TableReservationController::class, 'store'])
        ->name('table-reservations.store');
    Route::patch('/table-reservations/{tableReservation}/status', [TableReservationController::class, 'updateStatus'])
        ->name('table-reservations.update-status');
    Route::get('/order', [OrderController::class, 'index'])->name('kasir.order');
    Route::post('/order', [OrderController::class, 'store'])->name('order.store');
    Route::post('/order/{order}/pay', [OrderController::class, 'pay'])->name('order.pay');
    Route::patch('/order/{order}', [OrderController::class, 'update'])->name('order.update');
    Route::post('/order/{order}/split-bill', [OrderController::class, 'splitBill'])->name('order.split-bill');
    Route::post('/orders/merge-bills', [OrderController::class, 'mergeBills'])->name('orders.merge-bills');
});

require __DIR__.'/auth.php';
