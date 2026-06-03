<?php

namespace App\Repositories;

use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ShiftFinancialReportRepository
{
    public function getShiftsForReport(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $outletId = null,
        ?string $userId = null,
    ): Collection {
        return Shift::query()
            ->with([
                'user:id,name,outlet_id,role_id',
                'user.role:id,name,type',
                'outlet:id,name',
                'shiftTemplate:id,name,start_time,end_time',
                'opener:id,name',
                'closer:id,name',
                'cashReport',
            ])
            ->whereDate('opened_at', '>=', $startDate->toDateString())
            ->whereDate('opened_at', '<=', $endDate->toDateString())
            ->when($outletId, fn (Builder $q) => $q->where('outlet_id', $outletId))
            ->when($userId, fn (Builder $q) => $q->where('user_id', $userId))
            ->orderBy('opened_at', 'asc')
            ->get();
    }

    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $q) => $q->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getCashierUsers(?string $scopeOutletId = null): Collection
    {
        return User::query()
            ->with('role')
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $q) => $q->where('outlet_id', $scopeOutletId))
            ->whereHas('role', fn (Builder $q) => $q->whereIn('type', ['kasir', 'supervisor']))
            ->orderBy('name')
            ->get(['id', 'name', 'outlet_id', 'role_id'])
            ->map(fn (User $user) => [
                'id'   => $user->id,
                'name' => $user->name,
                'role' => $user->role?->name,
            ]);
    }
}
