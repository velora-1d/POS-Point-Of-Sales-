<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\TopProductReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class TopProductReportService
{
    public function __construct(
        protected TopProductReportRepository $topProductReportRepository,
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

        $currentProducts = $this->topProductReportRepository->getTopProducts(
            $currentStart,
            $currentEnd,
            $scopeOutletId,
            $resolvedFilters['category_id'] ?: null,
        );
        $previousProducts = $this->topProductReportRepository->getTopProducts(
            $previousPeriod['start'],
            $previousPeriod['end'],
            $scopeOutletId,
            $resolvedFilters['category_id'] ?: null,
        );
        $rows = $this->buildProductRows($currentProducts, $previousProducts);

        return [
            'summary' => $this->buildSummary($rows),
            'products' => $rows->values()->all(),
            'filters' => $resolvedFilters,
            'referenceData' => [
                'outlets' => $this->topProductReportRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
                'categories' => $this->topProductReportRepository->getCategories($scopeOutletId),
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

    protected function buildProductRows(Collection $currentProducts, Collection $previousProducts): Collection
    {
        $currentById = $currentProducts->keyBy('product_id');
        $previousById = $previousProducts->keyBy('product_id');
        $productIds = $currentById->keys()->merge($previousById->keys())->unique()->values();

        $rows = $productIds->map(function (string $productId) use ($currentById, $previousById) {
            $current = $currentById->get($productId);
            $previous = $previousById->get($productId);
            $reference = $current ?? $previous;
            $currentRevenue = (float) ($current->total_revenue ?? 0);
            $currentQuantity = (int) ($current->total_quantity ?? 0);
            $currentOrders = (int) ($current->total_orders ?? 0);
            $previousRevenue = (float) ($previous->total_revenue ?? 0);
            $previousQuantity = (int) ($previous->total_quantity ?? 0);
            $previousOrders = (int) ($previous->total_orders ?? 0);
            $hpp = (float) ($reference?->product?->hpp ?? 0);

            return [
                'id' => $productId,
                'name' => $reference?->product?->name ?? 'Produk tidak ditemukan',
                'outlet' => $reference?->product?->outlet ? [
                    'id' => $reference->product->outlet->id,
                    'name' => $reference->product->outlet->name,
                ] : null,
                'category' => $reference?->product?->category ? [
                    'id' => $reference->product->category->id,
                    'name' => $reference->product->category->name,
                ] : null,
                'current' => [
                    'quantity' => $currentQuantity,
                    'orders' => $currentOrders,
                    'revenue' => $currentRevenue,
                    'average_quantity_per_order' => $currentOrders > 0 ? $currentQuantity / $currentOrders : 0,
                    'estimated_margin' => ($currentRevenue - ($hpp * $currentQuantity)),
                ],
                'previous' => [
                    'quantity' => $previousQuantity,
                    'orders' => $previousOrders,
                    'revenue' => $previousRevenue,
                ],
                'growth' => [
                    'quantity_amount' => $currentQuantity - $previousQuantity,
                    'quantity_percentage' => $this->resolveGrowthPercentage($currentQuantity, $previousQuantity),
                    'revenue_amount' => $currentRevenue - $previousRevenue,
                ],
            ];
        })->sortByDesc(fn (array $row) => $row['current']['quantity'])->values();

        $maxQuantity = (float) $rows->max(fn (array $row) => $row['current']['quantity']);

        return $rows->map(function (array $row) use ($maxQuantity) {
            $row['current']['quantity_bar_percentage'] = $maxQuantity > 0
                ? round(($row['current']['quantity'] / $maxQuantity) * 100, 1)
                : 0;

            return $row;
        });
    }

    protected function buildSummary(Collection $rows): array
    {
        $totalQuantity = (int) $rows->sum(fn (array $row) => $row['current']['quantity']);
        $totalRevenue = (float) $rows->sum(fn (array $row) => $row['current']['revenue']);
        $topProduct = $rows->first();
        $bestGrowth = $rows
            ->filter(fn (array $row) => $row['growth']['quantity_percentage'] !== null)
            ->sortByDesc(fn (array $row) => $row['growth']['quantity_percentage'])
            ->first();

        return [
            'active_products' => $rows->filter(fn (array $row) => $row['current']['quantity'] > 0)->count(),
            'total_quantity' => $totalQuantity,
            'total_revenue' => $totalRevenue,
            'average_revenue_per_product' => $rows->count() > 0 ? $totalRevenue / $rows->count() : 0,
            'top_product' => $topProduct ? [
                'name' => $topProduct['name'],
                'quantity' => $topProduct['current']['quantity'],
            ] : null,
            'best_growth' => $bestGrowth ? [
                'name' => $bestGrowth['name'],
                'percentage' => $bestGrowth['growth']['quantity_percentage'],
            ] : null,
        ];
    }

    protected function resolveFilters(array $filters, ?string $scopeOutletId): array
    {
        return [
            'start_date' => $filters['start_date'] ?? CarbonImmutable::today()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? CarbonImmutable::today()->toDateString(),
            'outlet_id' => $scopeOutletId ?? '',
            'category_id' => $filters['category_id'] ?? '',
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

    protected function resolveGrowthPercentage(float|int $currentValue, float|int $previousValue): ?float
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
            abort(403, 'Laporan produk terlaris hanya tersedia untuk owner dan supervisor.');
        }
    }
}
