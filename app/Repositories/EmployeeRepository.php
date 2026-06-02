<?php

namespace App\Repositories;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmployeeRepository
{
    public function paginate(array $filters = [], ?string $scopeOutletId = null): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $roleType = $filters['role_type'] ?? null;
        $outletId = $filters['outlet_id'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 12);

        return User::query()
            ->with(['role', 'outlet'])
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'ilike', '%' . $search . '%')
                        ->orWhere('email', 'ilike', '%' . $search . '%')
                        ->orWhere('phone', 'ilike', '%' . $search . '%');
                });
            })
            ->when($status === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($roleType, function (Builder $query) use ($roleType) {
                $query->whereHas('role', fn (Builder $roleQuery) => $roleQuery->where('type', $roleType));
            })
            ->when($outletId, fn (Builder $query) => $query->where('outlet_id', $outletId))
            ->orderByDesc('is_active')
            ->orderBy('join_date')
            ->orderBy('name')
            ->paginate(max(6, min($perPage, 24)))
            ->withQueryString();
    }

    public function getSummary(?string $scopeOutletId = null): array
    {
        $baseQuery = User::query()
            ->when($scopeOutletId, fn (Builder $query) => $query->where('outlet_id', $scopeOutletId));

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'supervisor' => (clone $baseQuery)->whereHas('role', fn (Builder $query) => $query->where('type', 'supervisor'))->count(),
            'kasir' => (clone $baseQuery)->whereHas('role', fn (Builder $query) => $query->where('type', 'kasir'))->count(),
        ];
    }

    public function getRoleBreakdown(?string $scopeOutletId = null): Collection
    {
        return Role::query()
            ->select(['roles.type', 'roles.name'])
            ->selectRaw('COUNT(users.id) as total_users')
            ->leftJoin('users', 'users.role_id', '=', 'roles.id')
            ->when($scopeOutletId, function (Builder $query) use ($scopeOutletId) {
                $query->where('roles.outlet_id', $scopeOutletId);
            })
            ->groupBy('roles.id', 'roles.type', 'roles.name')
            ->orderBy('roles.name')
            ->get();
    }

    public function getOutlets(?string $scopeOutletId = null): Collection
    {
        return Outlet::query()
            ->where('is_active', true)
            ->when($scopeOutletId, fn (Builder $query) => $query->whereKey($scopeOutletId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getRoles(?string $outletId = null): Collection
    {
        return Role::query()
            ->when($outletId, fn (Builder $query) => $query->where('outlet_id', $outletId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'outlet_id', 'name', 'type']);
    }

    public function create(array $payload): User
    {
        return User::query()->create($payload);
    }

    public function update(User $user, array $payload): void
    {
        $user->update($payload);
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function findRoleForOutlet(string $roleId, string $outletId): ?Role
    {
        return Role::query()
            ->whereKey($roleId)
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->first();
    }
}
