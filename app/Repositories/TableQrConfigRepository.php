<?php

namespace App\Repositories;

use App\Models\Outlet;
use App\Models\Table;
use App\Models\TableQrConfig;
use Illuminate\Support\Collection;

class TableQrConfigRepository
{
    public function getOutlets(): Collection
    {
        return Outlet::query()
            ->with([
                'tableQrConfig',
                'tables' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            ])
            ->withCount([
                'tables as active_tables_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }

    public function findByOutletId(string $outletId): ?TableQrConfig
    {
        return TableQrConfig::query()
            ->where('outlet_id', $outletId)
            ->first();
    }

    public function findByStoreSlug(string $storeSlug): ?TableQrConfig
    {
        return TableQrConfig::query()
            ->where('store_slug', $storeSlug)
            ->first();
    }

    public function upsertByOutlet(string $outletId, array $payload): TableQrConfig
    {
        return TableQrConfig::query()->updateOrCreate(
            ['outlet_id' => $outletId],
            $payload,
        );
    }

    public function getActiveTables(string $outletId): Collection
    {
        return Table::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function findActiveTableByCode(string $outletId, string $tableCode): ?Table
    {
        return Table::query()
            ->where('outlet_id', $outletId)
            ->where('qr_code', $tableCode)
            ->where('is_active', true)
            ->with('outlet')
            ->first();
    }
}
