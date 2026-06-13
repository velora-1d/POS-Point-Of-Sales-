<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\DashboardRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DashboardService
{
    protected const FINANCE_METHODS = ['cash', 'qris', 'debit', 'ewallet', 'kasbon'];

    public function __construct(
        protected DashboardRepository $dashboardRepository,
        protected InventoryAlertService $inventoryAlertService,
        protected InventoryExpiryService $inventoryExpiryService,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $scopeOutletId = $this->resolveScopeOutletId($actor, $filters['outlet_id'] ?? null);
        $period = $filters['period'] ?? 'today';
        
        $today = CarbonImmutable::today();
        
        switch ($period) {
            case 'yesterday':
                $startDate = $today->subDay();
                $endDate = $today->subDay();
                
                $compStartDate = $today->subDays(2);
                $compEndDate = $today->subDays(2);
                break;
            case 'last_7_days':
                $startDate = $today->subDays(6);
                $endDate = $today;
                
                $compStartDate = $today->subDays(13);
                $compEndDate = $today->subDays(7);
                break;
            case 'last_30_days':
                $startDate = $today->subDays(29);
                $endDate = $today;
                
                $compStartDate = $today->subDays(59);
                $compEndDate = $today->subDays(30);
                break;
            case 'this_month':
                $startDate = $today->startOfMonth();
                $endDate = $today->endOfMonth();
                
                $compStartDate = $today->subMonth()->startOfMonth();
                $compEndDate = $today->subMonth()->endOfMonth();
                break;
            case 'today':
            default:
                $startDate = $today;
                $endDate = $today;
                
                $compStartDate = $today->subDay();
                $compEndDate = $today->subDay();
                break;
        }

        return [
            'alerts' => [
                'lowStock' => $this->inventoryAlertService->getLowStockSnapshot($actor, 6),
                'expired' => $this->inventoryExpiryService->getReminderSnapshot(
                    $actor,
                    ['days' => 7, 'status' => 'all', 'type' => 'all'],
                    6,
                ),
            ],
            'finance' => $this->canReadFinance($actor)
                ? $this->buildFinanceSummary($scopeOutletId, $startDate, $endDate, $compStartDate, $compEndDate, $actor)
                : null,
            'filters' => [
                'outlet_id' => $scopeOutletId ?? '',
                'period' => $period,
                'as_of_date' => $today->toDateString(),
            ],
            'referenceData' => [
                'outlets' => $this->dashboardRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
            ],
        ];
    }

    protected function buildFinanceSummary(
        ?string $scopeOutletId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        CarbonImmutable $compStartDate,
        CarbonImmutable $compEndDate,
        User $actor
    ): array {
        $orders = $this->dashboardRepository->getOrdersInDateRange($scopeOutletId, $startDate, $endDate);
        $settledOrders = $orders->filter(fn (Order $order) => $this->isSettledOrder($order))->values();
        $pendingOrders = $orders->filter(fn (Order $order) => !$this->isSettledOrder($order))->values();
        $totalRevenue = (float) $settledOrders->sum(fn (Order $order) => (float) $order->total_amount);
        $totalDiscount = (float) $settledOrders->sum(fn (Order $order) => (float) $order->discount_amount);
        $settledCount = $settledOrders->count();

        // Comparison period comparison
        $compOrders = $this->dashboardRepository->getOrdersInDateRange($scopeOutletId, $compStartDate, $compEndDate);
        $compSettled = $compOrders->filter(fn (Order $order) => $this->isSettledOrder($order))->values();
        $compRevenue = (float) $compSettled->sum(fn (Order $order) => (float) $order->total_amount);
        $compCount = $compSettled->count();
        $revenueGrowth = $compRevenue > 0
            ? round((($totalRevenue - $compRevenue) / $compRevenue) * 100, 1)
            : ($totalRevenue > 0 ? 100.0 : 0.0);

        $settledOrderIds = $settledOrders->pluck('id')->all();

        // Top 5 products
        $topProductsRaw = $this->dashboardRepository->getTopProductsForOrders($settledOrderIds, 5);
        $topProducts = $topProductsRaw->map(fn ($item) => [
            'name'     => $item->product?->name ?? 'Produk tidak ditemukan',
            'quantity' => (int) $item->total_quantity,
            'revenue'  => (float) $item->total_revenue,
        ])->values()->all();

        return [
            'can_view' => true,
            'summary'  => [
                'revenue'          => $totalRevenue,
                'orders'           => $orders->count(),
                'settled_orders'   => $settledCount,
                'pending_orders'   => $pendingOrders->count(),
                'avg_order_value'  => $settledCount > 0 ? $totalRevenue / $settledCount : 0,
                'total_discount'   => $totalDiscount,
                'yesterday_revenue' => $compRevenue,
                'yesterday_orders'  => $compCount,
                'revenue_growth'    => $revenueGrowth,
                'top_product'      => $topProducts[0] ?? null,
                'top_products'     => $topProducts,
                'active_shift'     => $this->transformActiveShift(
                    $this->dashboardRepository->findActiveShift($scopeOutletId),
                ),
            ],
            'breakdowns' => [
                'payments' => $this->buildPaymentBreakdown($settledOrders),
                'sources'  => $this->buildSourceBreakdown($settledOrders),
                'hourly'   => $this->dashboardRepository->getHourlyRevenueForOrders($settledOrders),
            ],
            'scope' => [
                'outlet_id'   => $scopeOutletId,
                'viewer_role' => $actor->role?->type,
                'date'        => $startDate->toDateString() === $endDate->toDateString()
                    ? $startDate->toDateString()
                    : $startDate->toDateString() . ' - ' . $endDate->toDateString(),
            ],
        ];
    }


    protected function buildPaymentBreakdown(Collection $orders): array
    {
        return collect(self::FINANCE_METHODS)
            ->map(function (string $method) use ($orders) {
                $filtered = $orders->filter(
                    fn (Order $order) => data_get($order->metadata, 'payment.method') === $method,
                );

                return [
                    'method' => $method,
                    'orders' => $filtered->count(),
                    'amount' => (float) $filtered->sum(fn (Order $order) => (float) $order->total_amount),
                ];
            })
            ->filter(fn (array $row) => $row['orders'] > 0 || $row['amount'] > 0)
            ->values()
            ->all();
    }

    protected function buildSourceBreakdown(Collection $orders): array
    {
        return $orders
            ->groupBy(fn (Order $order) => (string) $order->source)
            ->map(function (Collection $group, string $source) {
                return [
                    'source' => $source,
                    'orders' => $group->count(),
                    'amount' => (float) $group->sum(fn (Order $order) => (float) $order->total_amount),
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    protected function transformActiveShift(?\App\Models\Shift $shift): ?array
    {
        if (!$shift) {
            return null;
        }

        return [
            'id' => $shift->id,
            'cashier' => $shift->user?->name,
            'outlet' => $shift->outlet?->name,
            'opened_at' => $shift->opened_at?->toIso8601String(),
            'opening_cash' => (float) $shift->opening_cash,
            'status' => $shift->status,
        ];
    }

    protected function canReadFinance(User $actor): bool
    {
        return in_array($actor->role?->type, ['owner', 'supervisor'], true);
    }

    protected function resolveScopeOutletId(User $actor, ?string $requestedOutletId): ?string
    {
        if ($actor->role?->type === 'owner') {
            return $requestedOutletId ?: ($actor->outlet_id ?: null);
        }

        return $actor->outlet_id ?: null;
    }

    protected function isSettledOrder(Order $order): bool
    {
        return $order->isPaidInFull();
    }
}
