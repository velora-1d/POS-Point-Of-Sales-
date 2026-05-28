<?php

namespace App\Repositories;

use App\Models\SecurityActivityLog;
use Illuminate\Support\Collection;

class SecurityActivityLogRepository
{
    public function create(array $payload): SecurityActivityLog
    {
        return SecurityActivityLog::query()->create($payload);
    }

    public function getRecentByOutlet(string $outletId, int $limit = 10): Collection
    {
        return SecurityActivityLog::query()
            ->where('outlet_id', $outletId)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function countWarnings(?string $outletId = null, int $days = 7): int
    {
        return SecurityActivityLog::query()
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId))
            ->whereIn('status', ['warning', 'error'])
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }
}
