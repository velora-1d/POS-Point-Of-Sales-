<?php

namespace App\Repositories;

use App\Models\InventoryExpiry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InventoryExpiryRepository
{
    public function create(array $payload): InventoryExpiry
    {
        return InventoryExpiry::query()->create($payload);
    }

    public function getActiveByOutlet(string $outletId, ?string $type = null): Collection
    {
        return InventoryExpiry::query()
            ->where('outlet_id', $outletId)
            ->where('is_resolved', false)
            ->when($type && $type !== 'all', fn ($query) => $query->where('trackable_type', $type))
            ->with([
                'product.category',
                'rawMaterial',
            ])
            ->orderBy('expired_at')
            ->get();
    }

    public function getAutoDeactivateCandidates(string $outletId): Collection
    {
        return InventoryExpiry::query()
            ->where('outlet_id', $outletId)
            ->where('is_resolved', false)
            ->whereIn('expired_action', ['auto_deactivate', 'deactivate'])
            ->whereDate('expired_at', '<=', Carbon::today())
            ->with([
                'product',
                'rawMaterial',
            ])
            ->get();
    }

    public function markResolved(InventoryExpiry $inventoryExpiry, array $payload): InventoryExpiry
    {
        $inventoryExpiry->update($payload);

        return $inventoryExpiry->load([
            'product.category',
            'rawMaterial',
        ]);
    }
}
