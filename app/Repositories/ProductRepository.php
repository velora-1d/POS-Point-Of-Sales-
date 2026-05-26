<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository
{
    public function paginateByOutlet(string $outletId, array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $categoryId = $filters['category_id'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 12);

        return Product::query()
            ->where('outlet_id', $outletId)
            ->when($categoryId, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'ilike', '%' . $search . '%')
                        ->orWhere('description', 'ilike', '%' . $search . '%');
                });
            })
            ->with([
                'category',
                'variants' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
                'prices' => fn ($query) => $query->where('outlet_id', $outletId)->where('is_active', true)->orderBy('tier'),
            ])
            ->withCount([
                'variants as active_variants_count' => fn ($query) => $query->where('is_active', true),
                'prices as active_prices_count' => fn ($query) => $query->where('outlet_id', $outletId)->where('is_active', true),
            ])
            ->latest()
            ->paginate(max(6, min($perPage, 24)))
            ->withQueryString();
    }

    public function getSummary(string $outletId): array
    {
        $baseQuery = Product::query()->where('outlet_id', $outletId);

        return [
            'total' => (clone $baseQuery)->count(),
            'available' => (clone $baseQuery)->where('is_available', true)->where('is_active', true)->count(),
            'withVariants' => (clone $baseQuery)->whereHas('variants', fn (Builder $query) => $query->where('is_active', true))->count(),
            'withMultiPrice' => (clone $baseQuery)->whereHas('prices', fn (Builder $query) => $query->where('outlet_id', $outletId)->where('is_active', true))->count(),
        ];
    }

    public function getCategories(string $outletId)
    {
        return Category::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);
    }
}
