<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Support\Collection;

class InventoryAlertRepository
{
    public function getLowProductAlerts(string $outletId): Collection
    {
        return Product::query()
            ->where('outlet_id', $outletId)
            ->where('track_stock', true)
            ->with(['category', 'stock'])
            ->whereHas('stock', fn ($query) => $query->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->get()
            ->map(function (Product $product) {
                $currentStock = (int) ($product->stock?->current_stock ?? 0);

                return [
                    'id' => $product->id,
                    'type' => 'product',
                    'name' => $product->name,
                    'context' => $product->category?->name,
                    'current_stock' => $currentStock,
                    'minimum_stock' => (int) ($product->stock?->minimum_stock ?? 0),
                    'unit' => $product->stock?->unit ?: 'pcs',
                    'route' => 'products.stock',
                    'severity' => $currentStock <= 0 ? 2 : 1,
                ];
            });
    }

    public function getLowRawMaterialAlerts(string $outletId): Collection
    {
        return RawMaterial::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->whereColumn('quantity', '<=', 'minimum_stock')
            ->get()
            ->map(function (RawMaterial $rawMaterial) {
                $currentStock = (int) $rawMaterial->quantity;

                return [
                    'id' => $rawMaterial->id,
                    'type' => 'raw_material',
                    'name' => $rawMaterial->name,
                    'context' => 'Bahan baku',
                    'current_stock' => $currentStock,
                    'minimum_stock' => (int) $rawMaterial->minimum_stock,
                    'unit' => $rawMaterial->unit ?: 'gram',
                    'route' => 'raw-materials.index',
                    'severity' => $currentStock <= 0 ? 2 : 1,
                ];
            });
    }
}
