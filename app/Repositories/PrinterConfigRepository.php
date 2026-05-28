<?php

namespace App\Repositories;

use App\Models\Outlet;
use App\Models\PrinterConfig;
use Illuminate\Support\Collection;

class PrinterConfigRepository
{
    public function getOutlets(): Collection
    {
        return Outlet::query()
            ->with('printerConfig')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active', 'settings']);
    }

    public function findByOutletId(string $outletId): ?PrinterConfig
    {
        return PrinterConfig::query()
            ->where('outlet_id', $outletId)
            ->first();
    }

    public function upsertByOutlet(string $outletId, array $payload): PrinterConfig
    {
        return PrinterConfig::query()->updateOrCreate(
            ['outlet_id' => $outletId],
            $payload,
        );
    }

    public function updateOutletSettings(string $outletId, array $settings): void
    {
        Outlet::query()
            ->whereKey($outletId)
            ->update(['settings' => $settings]);
    }
}
