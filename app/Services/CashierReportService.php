<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\CashierReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CashierReportService
{
    protected const PAYMENT_METHODS = ['cash', 'qris', 'debit', 'ewallet', 'kasbon'];

    public function __construct(
        protected CashierReportRepository $cashierReportRepository,
    ) {
    }

    public function getReport(User $actor, array $filters = []): array
    {
        $this->assertCanRead($actor);

        $scopeOutletId = $this->resolveScopeOutletId($actor, $filters['outlet_id'] ?? null);
        $resolvedFilters = $this->resolveFilters($filters, $scopeOutletId);
        $currentStart = CarbonImmutable::parse($resolvedFilters['start_date']);
        $currentEnd = CarbonImmutable::parse($resolvedFilters['end_date']);
        $previousPeriod = $this->resolvePreviousPeriod($currentStart, $currentEnd);

        $currentOrders = $this->cashierReportRepository->getSettledCashierOrders(
            $currentStart,
            $currentEnd,
            $scopeOutletId,
            $resolvedFilters['user_id'] ?: null,
        );
        $previousOrders = $this->cashierReportRepository->getSettledCashierOrders(
            $previousPeriod['start'],
            $previousPeriod['end'],
            $scopeOutletId,
            $resolvedFilters['user_id'] ?: null,
        );
        $rows = $this->buildCashierRows($currentOrders, $previousOrders);

        return [
            'summary' => $this->buildSummary($rows),
            'cashiers' => $rows->values()->all(),
            'filters' => $resolvedFilters,
            'referenceData' => [
                'outlets' => $this->cashierReportRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
                'cashiers' => $this->cashierReportRepository->getCashierUsers($scopeOutletId),
            ],
            'period' => [
                'current' => [
                    'start_date' => $currentStart->toDateString(),
                    'end_date' => $currentEnd->toDateString(),
                ],
                'previous' => [
                    'start_date' => $previousPeriod['start']->toDateString(),
                    'end_date' => $previousPeriod['end']->toDateString(),
                ],
            ],
        ];
    }

    protected function buildCashierRows(Collection $currentOrders, Collection $previousOrders): Collection
    {
        $currentGrouped = $currentOrders->groupBy('cashier_id');
        $previousGrouped = $previousOrders->groupBy('cashier_id');
        $cashierIds = $currentGrouped->keys()->merge($previousGrouped->keys())->unique()->values();

        $rows = $cashierIds->map(function (string $cashierId) use ($currentGrouped, $previousGrouped) {
            /** @var Collection<int, Order> $currentGroup */
            $currentGroup = $currentGrouped->get($cashierId, collect());
            /** @var Collection<int, Order> $previousGroup */
            $previousGroup = $previousGrouped->get($cashierId, collect());
            /** @var Order|null $referenceOrder */
            $referenceOrder = $currentGroup->first() ?? $previousGroup->first();
            $currentRevenue = (float) $currentGroup->sum(fn (Order $order) => (float) $order->total_amount);
            $currentOrdersCount = $currentGroup->count();
            $previousRevenue = (float) $previousGroup->sum(fn (Order $order) => (float) $order->total_amount);
            $previousOrdersCount = $previousGroup->count();

            return [
                'id' => $cashierId,
                'name' => $referenceOrder?->cashier?->name ?? 'Kasir tidak ditemukan',
                'outlet' => $referenceOrder?->outlet ? [
                    'id' => $referenceOrder->outlet->id,
                    'name' => $referenceOrder->outlet->name,
                ] : null,
                'current' => [
                    'revenue' => $currentRevenue,
                    'orders' => $currentOrdersCount,
                    'average_ticket' => $currentOrdersCount > 0 ? $currentRevenue / $currentOrdersCount : 0,
                    'discount' => (float) $currentGroup->sum(fn (Order $order) => (float) $order->discount_amount),
                    'payment_breakdown' => $this->buildPaymentBreakdown($currentGroup),
                ],
                'previous' => [
                    'revenue' => $previousRevenue,
                    'orders' => $previousOrdersCount,
                    'average_ticket' => $previousOrdersCount > 0 ? $previousRevenue / $previousOrdersCount : 0,
                    'discount' => (float) $previousGroup->sum(fn (Order $order) => (float) $order->discount_amount),
                ],
                'growth' => [
                    'revenue_amount' => $currentRevenue - $previousRevenue,
                    'revenue_percentage' => $this->resolveGrowthPercentage($currentRevenue, $previousRevenue),
                    'order_amount' => $currentOrdersCount - $previousOrdersCount,
                ],
            ];
        })->sortByDesc(fn (array $row) => $row['current']['revenue'])->values();

        $maxRevenue = (float) $rows->max(fn (array $row) => $row['current']['revenue']);

        return $rows->map(function (array $row) use ($maxRevenue) {
            $row['current']['revenue_bar_percentage'] = $maxRevenue > 0
                ? round(($row['current']['revenue'] / $maxRevenue) * 100, 1)
                : 0;

            return $row;
        });
    }

    protected function buildPaymentBreakdown(Collection $orders): array
    {
        return collect(self::PAYMENT_METHODS)
            ->map(function (string $method) use ($orders) {
                $filtered = $orders->filter(
                    fn (Order $order) => data_get($order->metadata, 'payment.method') === $method,
                );

                return [
                    'method' => $method,
                    'amount' => (float) $filtered->sum(fn (Order $order) => (float) $order->total_amount),
                ];
            })
            ->filter(fn (array $row) => $row['amount'] > 0)
            ->values()
            ->all();
    }

    protected function buildSummary(Collection $rows): array
    {
        $totalRevenue = (float) $rows->sum(fn (array $row) => $row['current']['revenue']);
        $totalOrders = (int) $rows->sum(fn (array $row) => $row['current']['orders']);
        $topCashier = $rows->first();
        $bestGrowth = $rows
            ->filter(fn (array $row) => $row['growth']['revenue_percentage'] !== null)
            ->sortByDesc(fn (array $row) => $row['growth']['revenue_percentage'])
            ->first();

        return [
            'active_cashiers' => $rows->filter(fn (array $row) => $row['current']['orders'] > 0)->count(),
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_ticket' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
            'top_cashier' => $topCashier ? [
                'name' => $topCashier['name'],
                'revenue' => $topCashier['current']['revenue'],
            ] : null,
            'best_growth' => $bestGrowth ? [
                'name' => $bestGrowth['name'],
                'percentage' => $bestGrowth['growth']['revenue_percentage'],
            ] : null,
        ];
    }

    protected function resolveFilters(array $filters, ?string $scopeOutletId): array
    {
        return [
            'start_date' => $filters['start_date'] ?? CarbonImmutable::today()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? CarbonImmutable::today()->toDateString(),
            'outlet_id' => $scopeOutletId ?? '',
            'user_id' => $filters['user_id'] ?? '',
        ];
    }

    protected function resolvePreviousPeriod(CarbonImmutable $currentStart, CarbonImmutable $currentEnd): array
    {
        $daySpan = $currentStart->diffInDays($currentEnd);
        $previousEnd = $currentStart->subDay();
        $previousStart = $previousEnd->subDays($daySpan);

        return [
            'start' => $previousStart,
            'end' => $previousEnd,
        ];
    }

    protected function resolveGrowthPercentage(float $currentValue, float $previousValue): ?float
    {
        if ($previousValue <= 0) {
            return $currentValue > 0 ? null : 0.0;
        }

        return (($currentValue - $previousValue) / $previousValue) * 100;
    }

    protected function resolveScopeOutletId(User $actor, ?string $requestedOutletId): ?string
    {
        if ($actor->role?->type === 'owner') {
            return $requestedOutletId ?: null;
        }

        if (!$actor->outlet_id) {
            abort(403, 'User belum terhubung ke outlet aktif.');
        }

        return $actor->outlet_id;
    }

    protected function assertCanRead(User $actor): void
    {
        if (!in_array($actor->role?->type, ['owner', 'supervisor'], true)) {
            abort(403, 'Laporan per kasir hanya tersedia untuk owner dan supervisor.');
        }
    }
}
