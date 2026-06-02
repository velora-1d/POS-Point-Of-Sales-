<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceShiftReportController;
use App\Http\Controllers\ApprovalRuleController;
use App\Http\Controllers\BackupSecurityController;
use App\Http\Controllers\CashierReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\InventoryAlertController;
use App\Http\Controllers\InventoryExpiryController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\KitchenDisplayController;
use App\Http\Controllers\OnlineOrderController;
use App\Http\Controllers\OutletManagementController;
use App\Http\Controllers\OutletReportController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\OnlineOrderIntegrationController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\PrinterConfigController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductHppController;
use App\Http\Controllers\ProductStockController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrSelfServiceController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\RbacController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\TableReservationController;
use App\Http\Controllers\TableQrConfigController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TopProductReportController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\MembershipTierController;
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
Route::get('/m/{storeSlug}/{tableCode}', [QrSelfServiceController::class, 'showMenuByAlias'])
    ->name('self-service.menu.alias');
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
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::get('/settings/outlets', [OutletManagementController::class, 'index'])->name('settings.outlets.index');
    Route::post('/settings/outlets', [OutletManagementController::class, 'store'])->name('settings.outlets.store');
    Route::patch('/settings/outlets/{outlet}', [OutletManagementController::class, 'update'])->name('settings.outlets.update');
    Route::patch('/settings/outlets/{outlet}/status', [OutletManagementController::class, 'updateStatus'])->name('settings.outlets.update-status');
    Route::get('/settings/rbac', [RbacController::class, 'index'])->name('settings.rbac.index');
    Route::post('/settings/rbac/roles', [RbacController::class, 'store'])->name('settings.rbac.store');
    Route::patch('/settings/rbac/roles/{role}', [RbacController::class, 'update'])->name('settings.rbac.update');
    Route::patch('/settings/rbac/users/{employee}/role', [RbacController::class, 'assignUserRole'])->name('settings.rbac.users.assign-role');
    Route::put('/settings/rbac/matrix', [RbacController::class, 'saveMatrix'])->name('settings.rbac.matrix.save');
    Route::get('/settings/payment-gateway', [PaymentGatewayController::class, 'index'])->name('settings.payment-gateway.index');
    Route::put('/settings/payment-gateway', [PaymentGatewayController::class, 'update'])->name('settings.payment-gateway.update');
    Route::post('/settings/payment-gateway/test', [PaymentGatewayController::class, 'test'])->name('settings.payment-gateway.test');
    Route::get('/settings/printer', [PrinterConfigController::class, 'index'])->name('settings.printer.index');
    Route::put('/settings/printer', [PrinterConfigController::class, 'update'])->name('settings.printer.update');
    Route::get('/settings/printer/preview', [PrinterConfigController::class, 'preview'])->name('settings.printer.preview');
    Route::get('/settings/table-qr', [TableQrConfigController::class, 'index'])->name('settings.table-qr.index');
    Route::put('/settings/table-qr', [TableQrConfigController::class, 'update'])->name('settings.table-qr.update');
    Route::post('/settings/table-qr/regenerate', [TableQrConfigController::class, 'regenerate'])->name('settings.table-qr.regenerate');
    Route::get('/settings/notifications', [NotificationSettingController::class, 'index'])->name('settings.notifications.index');
    Route::put('/settings/notifications', [NotificationSettingController::class, 'update'])->name('settings.notifications.update');
    Route::get('/settings/backup-security', [BackupSecurityController::class, 'index'])->name('settings.backup-security.index');
    Route::put('/settings/backup-security', [BackupSecurityController::class, 'update'])->name('settings.backup-security.update');
    Route::get('/settings/backup-security/download', [BackupSecurityController::class, 'download'])->name('settings.backup-security.download');
    Route::get('/settings/approval-rules', [ApprovalRuleController::class, 'index'])->name('settings.approval-rules.index');
    Route::put('/settings/approval-rules', [ApprovalRuleController::class, 'update'])->name('settings.approval-rules.update');
    Route::get('/settings/online-integrations', [OnlineOrderIntegrationController::class, 'index'])->name('settings.online-integrations.index');
    Route::put('/settings/online-integrations/{platform}', [OnlineOrderIntegrationController::class, 'update'])->name('settings.online-integrations.update');
    Route::get('/schedules', [EmployeeScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [EmployeeScheduleController::class, 'store'])->name('schedules.store');
    Route::post('/schedules/bulk', [EmployeeScheduleController::class, 'bulkStore'])->name('schedules.bulk-store');
    Route::post('/schedules/update-times', [EmployeeScheduleController::class, 'updateShiftTimes'])->name('schedules.update-times');
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::patch('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shifts/open', [ShiftController::class, 'open'])->name('shifts.open');
    Route::post('/shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');
    Route::get('/reports/sales', [SalesReportController::class, 'index'])->name('reports.sales.index');
    Route::get('/reports/outlets', [OutletReportController::class, 'index'])->name('reports.outlets.index');
    Route::get('/reports/cashiers', [CashierReportController::class, 'index'])->name('reports.cashiers.index');
    Route::get('/reports/top-products', [TopProductReportController::class, 'index'])->name('reports.top-products.index');
    Route::get('/reports/inventory', [InventoryReportController::class, 'index'])->name('reports.inventory.index');
    Route::get('/reports/attendance-shifts', [AttendanceShiftReportController::class, 'index'])->name('reports.attendance-shifts.index');
    Route::get('/reports/expenses', [FinanceController::class, 'index'])->name('reports.expenses.index');
    Route::get('/reports/export', [ReportExportController::class, 'index'])->name('reports.exports.index');
    Route::get('/reports/export/download', [ReportExportController::class, 'download'])->name('reports.exports.download');
    Route::post('/reports/expenses', [ExpenseController::class, 'store'])->name('reports.expenses.store');
    Route::patch('/reports/expenses/{expense}', [ExpenseController::class, 'update'])->name('reports.expenses.update');
    Route::delete('/reports/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('reports.expenses.destroy');
    Route::post('/reports/incomes', [IncomeController::class, 'store'])->name('reports.incomes.store');
    Route::patch('/reports/incomes/{income}', [IncomeController::class, 'update'])->name('reports.incomes.update');
    Route::delete('/reports/incomes/{income}', [IncomeController::class, 'destroy'])->name('reports.incomes.destroy');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
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

    Route::get('/settings/tables', [TableController::class, 'index'])->name('settings.tables.index');
    Route::post('/settings/tables', [TableController::class, 'store'])->name('settings.tables.store');
    Route::patch('/settings/tables/{table}', [TableController::class, 'update'])->name('settings.tables.update');
    Route::delete('/settings/tables/{table}', [TableController::class, 'destroy'])->name('settings.tables.destroy');

    Route::get('/settings/membership-tiers', [MembershipTierController::class, 'index'])->name('settings.membership-tiers.index');
    Route::post('/settings/membership-tiers', [MembershipTierController::class, 'store'])->name('settings.membership-tiers.store');
    Route::patch('/settings/membership-tiers/{membershipTier}', [MembershipTierController::class, 'update'])->name('settings.membership-tiers.update');
    Route::delete('/settings/membership-tiers/{membershipTier}', [MembershipTierController::class, 'destroy'])->name('settings.membership-tiers.destroy');

    Route::get('/order', [OrderController::class, 'index'])->name('kasir.order');
    Route::get('/orders/audio-updates', [OrderController::class, 'audioUpdates'])->name('orders.audio-updates');
    Route::post('/order', [OrderController::class, 'store'])->name('order.store');
    Route::post('/order/{order}/pay', [OrderController::class, 'pay'])->name('order.pay');
    Route::patch('/order/{order}', [OrderController::class, 'update'])->name('order.update');
    Route::post('/order/{order}/split-bill', [OrderController::class, 'splitBill'])->name('order.split-bill');
    Route::post('/orders/merge-bills', [OrderController::class, 'mergeBills'])->name('orders.merge-bills');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions/pre-orders', [TransactionController::class, 'storePreOrder'])->name('transactions.pre-orders.store');
    Route::post('/transactions/{order}/close-kasbon', [TransactionController::class, 'closeKasbon'])->name('transactions.kasbon.close');
    Route::post('/transactions/{order}/installments', [TransactionController::class, 'storeInstallment'])->name('transactions.installments.store');
    Route::post('/transactions/{order}/activate-preorder', [TransactionController::class, 'activatePreOrder'])->name('transactions.pre-orders.activate');
    Route::get('/transactions/{order}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt.show');
    Route::post('/transactions/{order}/receipt', [TransactionController::class, 'markReceipt'])->name('transactions.receipt.mark');
});

require __DIR__.'/auth.php';
