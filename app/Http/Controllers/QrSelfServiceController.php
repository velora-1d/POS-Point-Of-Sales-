<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSelfServiceOrderRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\Table;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QrSelfServiceController extends Controller
{
    public function __construct(
        protected OrderPaymentService $orderPaymentService,
    ) {
    }

    public function showMenu(string $tableToken): Response
    {
        $table = $this->resolveTable($tableToken);
        $categories = $this->getOutletCategories($table->outlet_id);

        return Inertia::render('Public/QrOrderMenu', [
            'table' => $table,
            'outlet' => $table->outlet,
            'categories' => $categories,
        ]);
    }

    public function checkout(StoreSelfServiceOrderRequest $request, string $tableToken): RedirectResponse
    {
        $result = $this->orderPaymentService->createSelfServiceOrder(
            $tableToken,
            $request->validated(),
        );

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
        if (!$paymentCheckout && ($order->metadata['payment']['checkout_url'] ?? null) && $order->status === 'payment_pending') {
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
