<?php

namespace App\Repositories;

use App\Models\OnlineOrderIntegration;
use App\Models\Outlet;
use Illuminate\Support\Collection;

class OnlineOrderIntegrationRepository
{
    public function getOutlets(): Collection
    {
        return Outlet::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }

    public function getPlatformIntegrations(string $platform): Collection
    {
        return OnlineOrderIntegration::query()
            ->where('platform', $platform)
            ->orderBy('outlet_id')
            ->get();
    }

    public function findByOutletAndPlatform(string $outletId, string $platform): ?OnlineOrderIntegration
    {
        return OnlineOrderIntegration::query()
            ->where('outlet_id', $outletId)
            ->where('platform', $platform)
            ->first();
    }

    public function upsertByOutletAndPlatform(string $outletId, string $platform, array $payload): OnlineOrderIntegration
    {
        return OnlineOrderIntegration::query()->updateOrCreate(
            [
                'outlet_id' => $outletId,
                'platform' => $platform,
            ],
            $payload,
        );
    }

    public function touchLastSyncedAt(string $integrationId): void
    {
        OnlineOrderIntegration::query()
            ->whereKey($integrationId)
            ->update(['last_synced_at' => now()]);
    }
}
