<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSelfServiceOrderRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\Table;
use App\Services\OrderPaymentService;
use App\Services\TableQrConfigService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QrSelfServiceController extends Controller
{
    public function __construct(
        protected OrderPaymentService $orderPaymentService,
        protected TableQrConfigService $tableQrConfigService,
    ) {
    }

    public function showMenu(string $tableToken): Response
    {
        $table = $this->resolveTable($tableToken);
        $table->append('remaining_capacity');
        $categories = $this->getOutletCategories($table->outlet_id);

        return Inertia::render('Public/QrOrderMenu', [
            'table'      => $table,
            'outlet'     => $table->outlet,
            'categories' => $categories,
            'tableFull'  => $table->capacity !== null && $table->remaining_capacity === 0,
        ]);
    }

    public function showMenuByAlias(string $storeSlug, string $tableCode): Response
    {
        $table = $this->tableQrConfigService->resolveTableByAlias($storeSlug, $tableCode);
        $table->append('remaining_capacity');
        $categories = $this->getOutletCategories($table->outlet_id);

        return Inertia::render('Public/QrOrderMenu', [
            'table'      => $table,
            'outlet'     => $table->outlet,
            'categories' => $categories,
            'tableFull'  => $table->capacity !== null && $table->remaining_capacity === 0,
        ]);
    }

    public function checkout(StoreSelfServiceOrderRequest $request, string $tableToken): RedirectResponse
    {
        $result = $this->orderPaymentService->createSelfServiceOrder(
            $tableToken,
            $request->validated(),
        );

        try {
            $table = $this->resolveTable($tableToken);
            $order = $result['order'];
            $formattedTotal = number_format($order->total_amount, 0, ',', '.');
            
            \App\Services\FirebasePushService::sendToActiveCashiers(
                "Order Mandiri Baru!",
                "Meja {$table->name} telah membuat pesanan baru senilai Rp {$formattedTotal}.",
                [
                    'type' => 'qr_order',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send FCM notification for checkout: " . $e->getMessage());
        }

        return redirect()
            ->route('self-service.orders.status', [
                'tableToken' => $tableToken,
                'orderNumber' => $result['order']->order_number,
            ])
            ->with('success', $result['message'])
            ->with('paymentCheckout', $result['paymentCheckout']);
    }

    public function showOrderStatus(string $tableToken, string $orderNumber): Response
    {
        $table = $this->resolveTable($tableToken);
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('source', 'qr_meja')
            ->where('qr_session_token', $tableToken)
            ->with([
                'table',
                'customer.membership.tier',
                'items.product',
                'items.variant',
            ])
            ->firstOrFail();

        $paymentCheckout = session('paymentCheckout');
        if (!$paymentCheckout && ($order->metadata['payment']['checkout_url'] ?? null) && $order->hasPendingBeforeKitchenPayment()) {
            $paymentCheckout = [
                'provider' => 'pakasir',
                'method' => 'qris',
                'order_number' => $order->order_number,
                'amount' => (int) round((float) $order->total_amount),
                'context' => 'before_kitchen',
                'payment_url' => $order->metadata['payment']['checkout_url'],
            ];
        }

        return Inertia::render('Public/QrOrderStatus', [
            'table' => $table,
            'outlet' => $table->outlet,
            'order' => $order,
            'success' => session('success'),
            'paymentCheckout' => $paymentCheckout,
        ]);
    }

    protected function resolveTable(string $tableToken): Table
    {
        return Table::query()
            ->where('qr_session_token', $tableToken)
            ->where('is_active', true)
            ->with('outlet')
            ->firstOrFail();
    }

    protected function getOutletCategories(string $outletId)
    {
        return Category::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->with([
                'products' => function ($query) use ($outletId) {
                    $query->where('outlet_id', $outletId)
                        ->where('is_active', true)
                        ->where('is_available', true)
                        ->orderBy('sort_order');
                },
                'products.variants' => function ($query) {
                    $query->where('is_active', true);
                },
                'products.prices' => function ($query) use ($outletId) {
                    $query->where('outlet_id', $outletId)
                        ->where('is_active', true);
                },
            ])
            ->orderBy('sort_order')
            ->get();
    }
}
