<?php

namespace App\Repositories;

use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RbacRepository
{
    public function getOutlets(): Collection
    {
        return Outlet::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }

    public function findOutlet(string $outletId): ?Outlet
    {
        return Outlet::query()->find($outletId, ['id', 'name', 'is_active']);
    }

    public function getRolesForOutlet(string $outletId): Collection
    {
        $query = Role::query()
            ->where('outlet_id', $outletId)
            ->withCount('users')
            ->orderByDesc('is_active')
            ->orderBy('type')
            ->orderBy('name');

        if (Schema::hasTable('permissions') && Schema::hasTable('role_permissions')) {
            $query->with(['permissions' => fn ($permissionQuery) => $permissionQuery->orderBy('module')->orderBy('action')]);
        }

        return $query->get();
    }

    public function getUsersForOutlet(string $outletId): Collection
    {
        return User::query()
            ->with('role')
            ->where('outlet_id', $outletId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    public function getPermissionCatalog(): Collection
    {
        if (!Schema::hasTable('permissions')) {
            return collect();
        }

        return Permission::query()
            ->orderBy('module')
            ->orderBy('action')
            ->get();
    }

    public function findRoleInOutlet(string $roleId, string $outletId): ?Role
    {
        $query = Role::query()
            ->whereKey($roleId)
            ->where('outlet_id', $outletId);

        if (Schema::hasTable('permissions') && Schema::hasTable('role_permissions')) {
            $query->with('permissions');
        }

        return $query->first();
    }

    public function findUserInOutlet(string $userId, string $outletId): ?User
    {
        return User::query()
            ->whereKey($userId)
            ->where('outlet_id', $outletId)
            ->first();
    }

    public function roleNameExists(string $outletId, string $name, ?string $ignoreRoleId = null): bool
    {
        return Role::query()
            ->where('outlet_id', $outletId)
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim($name))])
            ->when($ignoreRoleId, fn ($query) => $query->whereKeyNot($ignoreRoleId))
            ->exists();
    }

    public function getPermissionsByNames(array $names): Collection
    {
        if (!Schema::hasTable('permissions') || $names === []) {
            return collect();
        }

        return Permission::query()
            ->where(function ($query) use ($names) {
                foreach ($names as $permissionName) {
                    [$module, $action] = array_pad(explode(':', $permissionName, 2), 2, null);

                    if (!$module || !$action) {
                        continue;
                    }

                    $query->orWhere(function ($innerQuery) use ($module, $action) {
                        $innerQuery
                            ->where('module', $module)
                            ->where('action', $action);
                    });
                }
            })
            ->get();
    }

    public function createRole(array $payload): Role
    {
        return Role::query()->create([
            'id' => (string) Str::uuid(),
            ...$payload,
        ]);
    }

    public function updateRole(Role $role, array $payload): void
    {
        $role->update($payload);
    }

    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_permissions')) {
            return;
        }

        $role->permissions()->sync($permissionIds);
    }

    public function updateUserRole(User $user, string $roleId): void
    {
        $user->update([
            'role_id' => $roleId,
        ]);
    }
}
