<?php

namespace App\Repositories;

use App\Models\RawMaterial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RawMaterialRepository
{
    public function paginateByOutlet(string $outletId, array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 12);

        return RawMaterial::query()
            ->where('outlet_id', $outletId)
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'ilike', '%' . $search . '%')
                        ->orWhere('unit', 'ilike', '%' . $search . '%');
                });
            })
            ->when($status === 'low', function (Builder $query) {
                $query
                    ->where('is_active', true)
                    ->whereColumn('quantity', '<=', 'minimum_stock')
                    ->where('quantity', '>', 0);
            })
            ->when($status === 'out', function (Builder $query) {
                $query
                    ->where('is_active', true)
                    ->where('quantity', '<=', 0);
            })
            ->when($status === 'healthy', function (Builder $query) {
                $query
                    ->where('is_active', true)
                    ->whereColumn('quantity', '>', 'minimum_stock');
            })
            ->when($status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->latest()
            ->paginate(max(6, min($perPage, 24)))
            ->withQueryString();
    }

    public function getSummary(string $outletId): array
    {
        $baseQuery = RawMaterial::query()->where('outlet_id', $outletId);

        return [
            'total' => (clone $baseQuery)->where('is_active', true)->count(),
            'healthy' => (clone $baseQuery)
                ->where('is_active', true)
                ->whereColumn('quantity', '>', 'minimum_stock')
                ->count(),
            'low' => (clone $baseQuery)
                ->where('is_active', true)
                ->whereColumn('quantity', '<=', 'minimum_stock')
                ->where('quantity', '>', 0)
                ->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
        ];
    }

    public function create(array $payload): RawMaterial
    {
        return RawMaterial::query()->create($payload);
    }

    public function update(RawMaterial $rawMaterial, array $payload): RawMaterial
    {
        $rawMaterial->update($payload);

        return $rawMaterial->fresh();
    }

    public function addStock(RawMaterial $rawMaterial, int $quantity): RawMaterial
    {
        $rawMaterial->update([
            'quantity' => $rawMaterial->quantity + $quantity,
            'last_restocked_at' => now(),
        ]);

        return $rawMaterial->fresh();
    }

    public function adjustStock(RawMaterial $rawMaterial, int $quantity): RawMaterial
    {
        $payload = [
            'quantity' => $quantity,
        ];

        if ($quantity > $rawMaterial->quantity) {
            $payload['last_restocked_at'] = now();
        }

        $rawMaterial->update($payload);

        return $rawMaterial->fresh();
    }
}
