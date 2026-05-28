<?php

namespace App\Repositories;

use App\Models\ApprovalRule;
use App\Models\Outlet;
use Illuminate\Support\Collection;

class ApprovalRuleRepository
{
    public function getOutlets(): Collection
    {
        return Outlet::query()
            ->with('approvalRule')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }

    public function findByOutletId(string $outletId): ?ApprovalRule
    {
        return ApprovalRule::query()
            ->where('outlet_id', $outletId)
            ->first();
    }

    public function upsertByOutlet(string $outletId, array $payload): ApprovalRule
    {
        return ApprovalRule::query()->updateOrCreate(
            ['outlet_id' => $outletId],
            $payload,
        );
    }
}
