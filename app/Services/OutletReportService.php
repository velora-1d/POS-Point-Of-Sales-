<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\OutletReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class OutletReportService
{
    public function __construct(
        protected OutletReportRepository $outletReportRepository,
    ) {
    }

    public function getReport(User $actor, array $filters = []): array
    {
        $this->assertCanRead($actor);

        $resolvedFilters = $this->resolveFilters($filters);
        $currentStart = CarbonImmutable::parse($resolvedFilters['start_date']);
        $currentEnd = CarbonImmutable::parse($resolvedFilters['end_date']);
        $previousPeriod = $this->resolvePreviousPeriod($currentStart, $currentEnd);

        $outlets = $this->outletReportRepository->getActiveOutlets();
        $currentMetrics = $this->outletReportRepository->getSettledOutletMetrics($currentStart, $currentEnd);
        $previousMetrics = $this->outletReportRepository->getSettledOutletMetrics(
            $previousPeriod['start'],
            $previousPeriod['end'],
        );

        $rows = $this->buildOutletRows($outlets, $currentMetrics, $previousMetrics);

        return [
            'summary' => $this->buildSummary($rows),
            'outlets' => $rows->values()->all(),
            'filters' => $resolvedFilters,
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

    protected function buildOutletRows(
        Collection $outlets,
        Collection $currentMetrics,
        Collection $previousMetrics,
    ): Collection {
        $rows = $outlets->map(function ($outlet) use ($currentMetrics, $previousMetrics) {
            $current = $currentMetrics->get($outlet->id);
            $previous = $previousMetrics->get($outlet->id);
            $currentRevenue = (float) ($current->total_revenue ?? 0);
            $previousRevenue = (float) ($previous->total_revenue ?? 0);
            $currentOrders = (int) ($current->total_orders ?? 0);
            $previousOrders = (int) ($previous->total_orders ?? 0);

            return [
                'id' => $outlet->id,
                'name' => $outlet->name,
                'current' => [
                    'revenue' => $currentRevenue,
                    'orders' => $currentOrders,
                    'average_ticket' => $currentOrders > 0 ? $currentRevenue / $currentOrders : 0,
                    'discount' => (float) ($current->total_discount ?? 0),
                ],
                'previous' => [
                    'revenue' => $previousRevenue,
                    'orders' => $previousOrders,
                    'average_ticket' => $previousOrders > 0 ? $previousRevenue / $previousOrders : 0,
                    'discount' => (float) ($previous->total_discount ?? 0),
                ],
                'growth' => [
                    'revenue_amount' => $currentRevenue - $previousRevenue,
                    'revenue_percentage' => $this->resolveGrowthPercentage($currentRevenue, $previousRevenue),
                    'order_amount' => $currentOrders - $previousOrders,
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

    protected function buildSummary(Collection $rows): array
    {
        $totalRevenue = (float) $rows->sum(fn (array $row) => $row['current']['revenue']);
        $totalOrders = (int) $rows->sum(fn (array $row) => $row['current']['orders']);
        $activeOutlets = $rows->filter(fn (array $row) => $row['current']['orders'] > 0)->count();
        $topOutlet = $rows->first();
        $bestGrowth = $rows
            ->filter(fn (array $row) => $row['growth']['revenue_percentage'] !== null)
            ->sortByDesc(fn (array $row) => $row['growth']['revenue_percentage'])
            ->first();

        return [
            'total_outlets' => $rows->count(),
            'active_outlets' => $activeOutlets,
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_ticket' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
            'top_outlet' => $topOutlet ? [
                'name' => $topOutlet['name'],
                'revenue' => $topOutlet['current']['revenue'],
            ] : null,
            'best_growth' => $bestGrowth ? [
                'name' => $bestGrowth['name'],
                'percentage' => $bestGrowth['growth']['revenue_percentage'],
            ] : null,
        ];
    }

    protected function resolveFilters(array $filters): array
    {
        return [
            'start_date' => $filters['start_date'] ?? CarbonImmutable::today()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? CarbonImmutable::today()->toDateString(),
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

    protected function assertCanRead(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Laporan per outlet hanya tersedia untuk owner.');
        }
    }
}
