<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\InventoryExpiry;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\RawMaterial;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InventoryReportRepository
{
    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getTrackedProductsWithoutStock(string $outletId): Collection
    {
        return Product::query()
            ->where('outlet_id', $outletId)
            ->where('track_stock', true)
            ->whereDoesntHave('stock')
            ->get(['id', 'outlet_id']);
    }

    public function createStockRows(Collection $products): void
    {
        foreach ($products as $product) {
            \App\Models\ProductStock::query()->create([
                'product_id' => $product->id,
                'outlet_id' => $product->outlet_id,
                'current_stock' => 0,
                'minimum_stock' => 5,
                'unit' => 'pcs',
                'last_restocked_at' => null,
            ]);
        }
    }

    public function getProductStocks(?string $scopeOutletId = null): Collection
    {
        return Product::query()
            ->where('track_stock', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->with(['stock', 'outlet:id,name', 'category:id,name'])
            ->orderBy('name')
            ->get([
                'id',
                'outlet_id',
                'category_id',
                'name',
                'base_price',
                'hpp',
                'is_active',
                'is_available',
                'track_stock',
                'track_expired',
            ]);
    }

    public function getRawMaterials(?string $scopeOutletId = null): Collection
    {
        return RawMaterial::query()
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->with('outlet:id,name')
            ->orderBy('name')
            ->get([
                'id',
                'outlet_id',
                'name',
                'unit',
                'quantity',
                'minimum_stock',
                'cost_per_unit',
                'track_expired',
                'is_active',
                'last_restocked_at',
            ]);
    }

    public function getInventoryExpiries(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $scopeOutletId = null,
    ): Collection {
        return InventoryExpiry::query()
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->whereDate('expired_at', '>=', $startDate->toDateString())
            ->whereDate('expired_at', '<=', $endDate->toDateString())
            ->with([
                'product:id,outlet_id,category_id,name,hpp',
                'product.outlet:id,name',
                'product.category:id,name',
                'rawMaterial:id,outlet_id,name,cost_per_unit,unit',
                'rawMaterial.outlet:id,name',
            ])
            ->orderBy('expired_at')
            ->get();
    }
}
