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
        $today = CarbonImmutable::today();

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
                ? $this->buildFinanceSummary($scopeOutletId, $today, $actor)
                : null,
            'filters' => [
                'outlet_id' => $scopeOutletId ?? '',
                'as_of_date' => $today->toDateString(),
            ],
            'referenceData' => [
                'outlets' => $this->dashboardRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
            ],
        ];
    }

    protected function buildFinanceSummary(?string $scopeOutletId, CarbonImmutable $date, User $actor): array
    {
        $orders = $this->dashboardRepository->getTodayOrders($scopeOutletId, $date);
        $settledOrders = $orders->filter(fn (Order $order) => $this->isSettledOrder($order))->values();
        $totalRevenue = (float) $settledOrders->sum(fn (Order $order) => (float) $order->total_amount);
        $totalDiscount = (float) $settledOrders->sum(fn (Order $order) => (float) $order->discount_amount);
        $settledCount = $settledOrders->count();

        return [
            'can_view' => true,
            'summary' => [
                'revenue' => $totalRevenue,
                'orders' => $orders->count(),
                'settled_orders' => $settledCount,
                'avg_order_value' => $settledCount > 0 ? $totalRevenue / $settledCount : 0,
                'total_discount' => $totalDiscount,
                'top_product' => $this->dashboardRepository->getTopProductForOrders(
                    $settledOrders->pluck('id')->all(),
                ),
                'active_shift' => $this->transformActiveShift(
                    $this->dashboardRepository->findActiveShift($scopeOutletId),
                ),
            ],
            'breakdowns' => [
                'payments' => $this->buildPaymentBreakdown($settledOrders),
                'sources' => $this->buildSourceBreakdown($settledOrders),
            ],
            'scope' => [
                'outlet_id' => $scopeOutletId,
                'viewer_role' => $actor->role?->type,
                'date' => $date->toDateString(),
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
        return ($order->status !== 'payment_pending')
            && (
                data_get($order->metadata, 'payment.status') === 'paid'
                || (float) $order->paid_amount >= (float) $order->total_amount
            );
    }
}
