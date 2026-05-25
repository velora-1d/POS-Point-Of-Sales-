<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessOrderPaymentRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\SplitOrderBillRequest;
use App\Http\Requests\MergeOrdersRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\Table;
use App\Services\OrderBillService;
use App\Services\OrderEditService;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        protected OrderEditService $orderEditService,
        protected OrderBillService $orderBillService,
        protected OrderPaymentService $orderPaymentService,
    ) {
    }

    public function index()
    {
        $user = auth()->user();
        $outletId = $user->outlet_id ?? Outlet::first()?->id;

        if (!$outletId) {
            return Inertia::render('Kasir/Order', [
                'tables' => [],
                'categories' => [],
                'activeOrders' => [],
                'error' => 'Belum ada Outlet terdaftar di sistem.'
            ]);
        }

        // Load active tables scoped by outlet
        $tables = Table::where('outlet_id', $outletId)
            ->where('is_active', true)
            ->with([
                'activeOrder.items.product',
                'activeOrder.items.variant',
                'activeOrder.cashier',
                'activeOrder.customer.membership.tier',
                'activeOrders.items.product',
                'activeOrders.items.variant',
                'activeOrders.cashier',
                'activeOrders.customer.membership.tier',
            ])
            ->orderBy('name')
            ->get();

        // Load active categories and their products scoped by outlet
        $categories = Category::with(['products' => function ($q) use ($outletId) {
            $q->where('outlet_id', $outletId)
                ->where('is_active', true)
                ->where('is_available', true)
                ->orderBy('sort_order');
        }, 'products.variants' => function ($q) {
            $q->where('is_active', true);
        }, 'products.prices' => function ($q) use ($outletId) {
            $q->where('outlet_id', $outletId)
                ->where('is_active', true);
        }])->get();

        // Load active orders scoped by outlet
        $activeOrders = Order::where('outlet_id', $outletId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($query) {
                $query->where('status', '!=', 'payment_pending')
                    ->orWhere('source', 'kasir');
            })
            ->with(['table', 'cashier', 'customer.membership.tier', 'items.product', 'items.variant'])
            ->latest()
            ->get();

        $customers = Customer::where('outlet_id', $outletId)
            ->where('is_active', true)
            ->with(['membership.tier'])
            ->latest()
            ->limit(50)
            ->get();

        return Inertia::render('Kasir/Order', [
            'tables' => $tables,
            'categories' => $categories,
            'activeOrders' => $activeOrders,
            'customers' => $customers,
            'success' => session('success'),
            'paymentCheckout' => session('paymentCheckout'),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $result = $this->orderPaymentService->createKasirOrder(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('kasir.order')
            ->with('success', $result['message'])
            ->with('paymentCheckout', $result['paymentCheckout']);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->orderEditService->updateOrder(
            $order->load('items'),
            $request->validated(),
            auth()->user(),
        );

        return redirect()
            ->route('kasir.order')
            ->with('success', 'Perubahan order berhasil disimpan dan status kembali ke pending.');
    }

    public function splitBill(SplitOrderBillRequest $request, Order $order): RedirectResponse
    {
        $this->orderBillService->splitOrder(
            $order,
            $request->validated(),
            auth()->user(),
        );

        return redirect()
            ->route('kasir.order')
            ->with('success', 'Split bill berhasil dibuat. Order asal dan bill baru kembali ke pending.');
    }

    public function mergeBills(MergeOrdersRequest $request): RedirectResponse
    {
        $this->orderBillService->mergeOrders(
            $request->validated()['order_ids'],
            $request->validated()['approval_pin'] ?? null,
            auth()->user(),
        );

        return redirect()
            ->route('kasir.order')
            ->with('success', 'Gabung bill berhasil dibuat dan order asal diarsipkan.');
    }

    public function pay(ProcessOrderPaymentRequest $request, Order $order): RedirectResponse
    {
        $result = $this->orderPaymentService->processExistingOrderPayment(
            $order,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('kasir.order')
            ->with('success', $result['message'])
            ->with('paymentCheckout', $result['paymentCheckout']);
    }
}
