<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Outlet;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CashierReportRepository
{
    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getCashierUsers(?string $scopeOutletId = null): Collection
    {
        return User::query()
            ->with('role')
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->whereHas('role', fn (Builder $query) => $query->whereIn('type', ['kasir', 'supervisor']))
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'outlet_id',
                'role_id',
            ])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role?->name,
            ]);
    }

    public function getSettledCashierOrders(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $scopeOutletId = null,
        ?string $cashierUserId = null,
    ): Collection {
        return Order::query()
            ->with(['cashier:id,name', 'outlet:id,name'])
            ->select([
                'id',
                'outlet_id',
                'cashier_id',
                'source',
                'status',
                'discount_amount',
                'total_amount',
                'paid_amount',
                'metadata',
                'created_at',
            ])
            ->where('source', 'kasir')
            ->where('status', '!=', 'cancelled')
            ->whereColumn('paid_amount', '>=', 'total_amount')
            ->whereNotNull('cashier_id')
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($cashierUserId, fn (Builder $query) => $query->where('cashier_id', $cashierUserId))
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->orderBy('created_at')
            ->get();
    }
}
