<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\User;
use App\Repositories\InventoryReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class InventoryReportService
{
    public function __construct(
        protected InventoryReportRepository $inventoryReportRepository,
    ) {
    }

    public function getReport(User $actor, array $filters = []): array
    {
        $this->assertCanRead($actor);

        $scopeOutletId = $this->resolveScopeOutletId($actor, $filters['outlet_id'] ?? null);
        if ($scopeOutletId) {
            $this->ensureProductStockRows($scopeOutletId);
        }

        $resolvedFilters = $this->resolveFilters($filters, $scopeOutletId);
        $startDate = CarbonImmutable::parse($resolvedFilters['start_date']);
        $endDate = CarbonImmutable::parse($resolvedFilters['end_date']);

        $inventoryRows = $this->buildInventoryRows(
            $this->inventoryReportRepository->getProductStocks($scopeOutletId),
            $this->inventoryReportRepository->getRawMaterials($scopeOutletId),
        );
        $filteredInventoryRows = $this->filterInventoryRows($inventoryRows, $resolvedFilters);
        $expiryRows = $this->buildExpiryRows(
            $this->inventoryReportRepository->getInventoryExpiries($startDate, $endDate, $scopeOutletId),
        );

        return [
            'summary' => $this->buildSummary($inventoryRows, $expiryRows, $startDate, $endDate),
            'inventory' => $filteredInventoryRows->values()->all(),
            'expiries' => $expiryRows->values()->all(),
            'filters' => $resolvedFilters,
            'referenceData' => [
                'outlets' => $this->inventoryReportRepository->getOutlets(
                    $actor->role?->type === 'owner' ? null : $scopeOutletId,
                ),
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'limitations' => [
                'movement_logs' => 'Belum ada tabel log mutasi stok di schema aktif, jadi laporan ini menampilkan snapshot stok saat ini dan exposure expired pada periode terpilih.',
                'stock_opname' => 'Belum ada pencatatan hasil stock opname historis, jadi selisih sistem vs aktual belum bisa dihitung otomatis.',
            ],
        ];
    }

    protected function buildInventoryRows(Collection $products, Collection $rawMaterials): Collection
    {
        $productRows = $products->map(function (Product $product) {
            $currentStock = (int) ($product->stock?->current_stock ?? 0);
            $minimumStock = (int) ($product->stock?->minimum_stock ?? 0);
            $status = $this->resolveStockStatus($currentStock, $minimumStock, (bool) $product->is_active);
            $unitCost = (float) ($product->hpp ?: $product->base_price ?: 0);

            return [
                'type' => 'product',
                'id' => $product->id,
                'name' => $product->name,
                'outlet' => $product->outlet ? [
                    'id' => $product->outlet->id,
                    'name' => $product->outlet->name,
                ] : null,
                'context' => $product->category?->name ?: 'Produk jadi',
                'current_stock' => $currentStock,
                'minimum_stock' => $minimumStock,
                'unit' => $product->stock?->unit ?: 'pcs',
                'status' => $status,
                'stock_value' => $currentStock * $unitCost,
                'last_restocked_at' => $product->stock?->last_restocked_at?->toIso8601String(),
                'track_expired' => (bool) $product->track_expired,
            ];
        });

        $rawMaterialRows = $rawMaterials->map(function (RawMaterial $rawMaterial) {
            $currentStock = (float) $rawMaterial->quantity;
            $minimumStock = (float) $rawMaterial->minimum_stock;
            $status = $this->resolveStockStatus($currentStock, $minimumStock, (bool) $rawMaterial->is_active);

            return [
                'type' => 'raw_material',
                'id' => $rawMaterial->id,
                'name' => $rawMaterial->name,
                'outlet' => $rawMaterial->outlet ? [
                    'id' => $rawMaterial->outlet->id,
                    'name' => $rawMaterial->outlet->name,
                ] : null,
                'context' => 'Bahan baku',
                'current_stock' => $currentStock,
                'minimum_stock' => $minimumStock,
                'unit' => $rawMaterial->unit ?: 'unit',
                'status' => $status,
                'stock_value' => $currentStock * (float) $rawMaterial->cost_per_unit,
                'last_restocked_at' => $rawMaterial->last_restocked_at?->toIso8601String(),
                'track_expired' => (bool) $rawMaterial->track_expired,
            ];
        });

        return $productRows
            ->merge($rawMaterialRows)
            ->sortByDesc(fn (array $row) => $row['stock_value'])
            ->values();
    }

    protected function buildExpiryRows(Collection $expiries): Collection
    {
        return $expiries->map(function ($expiry) {
            $isProduct = $expiry->trackable_type === 'product';
            $entity = $isProduct ? $expiry->product : $expiry->rawMaterial;
            $unitCost = $isProduct
                ? (float) ($entity?->hpp ?? 0)
                : (float) ($entity?->cost_per_unit ?? 0);

            return [
                'id' => $expiry->id,
                'type' => $expiry->trackable_type,
                'name' => $entity?->name ?? 'Item tidak ditemukan',
                'outlet' => $entity?->outlet ? [
                    'id' => $entity->outlet->id,
                    'name' => $entity->outlet->name,
                ] : null,
                'context' => $isProduct
                    ? ($entity?->category?->name ?: 'Produk jadi')
                    : 'Bahan baku',
                'quantity' => (int) $expiry->quantity,
                'batch_code' => $expiry->batch_code,
                'expired_at' => $expiry->expired_at?->toDateString(),
                'estimated_loss' => (int) $expiry->quantity * $unitCost,
                'is_resolved' => (bool) $expiry->is_resolved,
            ];
        })->values();
    }

    protected function filterInventoryRows(Collection $rows, array $filters): Collection
    {
        $type = $filters['type'] ?? 'all';
        $status = $filters['status'] ?? 'all';

        return $rows->filter(function (array $row) use ($type, $status) {
            if ($type !== 'all' && $row['type'] !== $type) {
                return false;
            }

            if ($status !== 'all' && $row['status'] !== $status) {
                return false;
            }

            return true;
        });
    }

    protected function buildSummary(Collection $inventoryRows, Collection $expiryRows, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return [
            'tracked_products' => $inventoryRows->where('type', 'product')->count(),
            'active_raw_materials' => $inventoryRows
                ->where('type', 'raw_material')
                ->where('status', '!=', 'inactive')
                ->count(),
            'low_or_out_items' => $inventoryRows->filter(
                fn (array $row) => in_array($row['status'], ['low', 'out'], true),
            )->count(),
            'estimated_stock_value' => (float) $inventoryRows->sum(fn (array $row) => $row['stock_value']),
            'restocked_in_period' => $inventoryRows
                ->filter(function (array $row) use ($startDate, $endDate) {
                    if (!$row['last_restocked_at']) {
                        return false;
                    }

                    $lastRestocked = CarbonImmutable::parse($row['last_restocked_at'])->startOfDay();

                    return $lastRestocked->betweenIncluded($startDate, $endDate);
                })
                ->count(),
            'expired_loss_estimate' => (float) $expiryRows->sum(fn (array $row) => $row['estimated_loss']),
        ];
    }

    protected function ensureProductStockRows(string $outletId): void
    {
        $products = $this->inventoryReportRepository->getTrackedProductsWithoutStock($outletId);

        if ($products->isNotEmpty()) {
            $this->inventoryReportRepository->createStockRows($products);
        }
    }

    protected function resolveFilters(array $filters, ?string $scopeOutletId): array
    {
        return [
            'start_date' => $filters['start_date'] ?? CarbonImmutable::today()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? CarbonImmutable::today()->toDateString(),
            'outlet_id' => $scopeOutletId ?? '',
            'type' => $filters['type'] ?? 'all',
            'status' => $filters['status'] ?? 'all',
        ];
    }

    protected function resolveStockStatus(float|int $currentStock, float|int $minimumStock, bool $isActive): string
    {
        if (!$isActive) {
            return 'inactive';
        }

        if ($currentStock <= 0) {
            return 'out';
        }

        if ($currentStock <= $minimumStock) {
            return 'low';
        }

        return 'healthy';
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
            abort(403, 'Laporan stok & inventori hanya tersedia untuk owner dan supervisor.');
        }
    }
}
