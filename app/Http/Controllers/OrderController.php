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
use App\Models\TableReservation;
use App\Models\User;
use App\Services\OrderBillService;
use App\Services\OrderEditService;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
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
        $outletId = $this->resolveOutletId($user);

        if (!$outletId) {
            return Inertia::render('Kasir/Order', [
                'tables' => [],
                'categories' => [],
                'activeOrders' => [],
                'error' => 'Belum ada Outlet terdaftar di sistem.'
            ]);
        }

        $tables = $this->loadOutletTables($outletId);

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

    public function tableLayout()
    {
        $user = auth()->user();
        $outletId = $this->resolveOutletId($user);

        if (!$outletId) {
            return Inertia::render('Tables/Layout', [
                'tables' => [],
                'reservations' => [],
                'summary' => [
                    'total' => 0,
                    'available' => 0,
                    'occupied' => 0,
                    'reserved' => 0,
                ],
                'error' => 'Belum ada Outlet terdaftar di sistem.',
            ]);
        }

        $tables = $this->loadOutletTables($outletId);
        $reservations = $this->loadOutletReservations($outletId);
        $summary = [
            'total' => $tables->count(),
            'available' => $tables->where('status', 'available')->count(),
            'occupied' => $tables->where('status', 'occupied')->count(),
            'reserved' => $tables->where('status', 'reserved')->count(),
        ];

        return Inertia::render('Tables/Layout', [
            'tables' => $tables,
            'reservations' => $reservations,
            'summary' => $summary,
            'success' => session('success'),
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

    protected function resolveOutletId(?User $user): ?string
    {
        return $user?->outlet_id ?? Outlet::first()?->id;
    }

    protected function loadOutletTables(string $outletId)
    {
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
                'activeReservation.customer',
            ])
            ->orderBy('name')
            ->get();

        $tables->each(function (Table $table): void {
            if ($table->qr_session_token && $table->qr_code) {
                return;
            }

            $table->forceFill([
                'qr_session_token' => $table->qr_session_token ?: (string) Str::ulid(),
                'qr_code' => $table->qr_code ?: 'QR-' . strtoupper(Str::random(10)),
            ])->save();
        });

        return $tables;
    }

    protected function loadOutletReservations(string $outletId)
    {
        return TableReservation::query()
            ->where('outlet_id', $outletId)
            ->where('status', 'booked')
            ->with(['table', 'customer', 'creator'])
            ->orderBy('reserved_for')
            ->get();
    }
}
