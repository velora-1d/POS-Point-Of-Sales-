<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductStockRepository
{
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
            ProductStock::query()->create([
                'product_id' => $product->id,
                'outlet_id' => $product->outlet_id,
                'current_stock' => 0,
                'minimum_stock' => 5,
                'unit' => 'pcs',
                'last_restocked_at' => null,
            ]);
        }
    }

    public function paginateByOutlet(string $outletId, array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 12);

        return Product::query()
            ->where('outlet_id', $outletId)
            ->where('track_stock', true)
            ->with([
                'category',
                'stock',
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'ilike', '%' . $search . '%')
                        ->orWhere('description', 'ilike', '%' . $search . '%');
                });
            })
            ->when($status === 'low', function (Builder $query) {
                $query->whereHas('stock', function (Builder $stockQuery) {
                    $stockQuery
                        ->whereColumn('current_stock', '<=', 'minimum_stock')
                        ->where('current_stock', '>', 0);
                });
            })
            ->when($status === 'out', function (Builder $query) {
                $query->whereHas('stock', fn (Builder $stockQuery) => $stockQuery->where('current_stock', '<=', 0));
            })
            ->when($status === 'healthy', function (Builder $query) {
                $query->whereHas('stock', fn (Builder $stockQuery) => $stockQuery->whereColumn('current_stock', '>', 'minimum_stock'));
            })
            ->latest()
            ->paginate(max(6, min($perPage, 24)))
            ->withQueryString();
    }

    public function getSummary(string $outletId): array
    {
        $trackedProducts = Product::query()
            ->where('outlet_id', $outletId)
            ->where('track_stock', true);

        $stockQuery = ProductStock::query()->where('outlet_id', $outletId);

        return [
            'tracked' => (clone $trackedProducts)->count(),
            'healthy' => (clone $stockQuery)->whereColumn('current_stock', '>', 'minimum_stock')->count(),
            'low' => (clone $stockQuery)
                ->whereColumn('current_stock', '<=', 'minimum_stock')
                ->where('current_stock', '>', 0)
                ->count(),
            'out' => (clone $stockQuery)->where('current_stock', '<=', 0)->count(),
        ];
    }

    public function findStockForProduct(Product $product): ?ProductStock
    {
        return ProductStock::query()
            ->where('product_id', $product->id)
            ->where('outlet_id', $product->outlet_id)
            ->first();
    }

    public function updateStock(ProductStock $stock, array $payload): ProductStock
    {
        $lastRestockedAt = $stock->last_restocked_at;

        if ((int) $payload['current_stock'] > (int) $stock->current_stock) {
            $lastRestockedAt = now();
        }

        $stock->fill([
            'current_stock' => $payload['current_stock'],
            'minimum_stock' => $payload['minimum_stock'],
            'unit' => $payload['unit'] ?: ($stock->unit ?: 'pcs'),
            'last_restocked_at' => $lastRestockedAt,
        ]);

        $stock->save();

        return $stock;
    }
}
