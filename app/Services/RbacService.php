<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\RbacRepository;
use App\Support\RbacPermissionMatrix;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RbacService
{
    public function __construct(
        protected RbacRepository $rbacRepository,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanManage($actor);

        $outlets = $this->rbacRepository->getOutlets();
        $selectedOutlet = $this->resolveSelectedOutlet($outlets, $filters['outlet_id'] ?? null);
        $selectedOutletId = $selectedOutlet['id'] ?? null;
        $roles = $selectedOutletId
            ? $this->rbacRepository->getRolesForOutlet($selectedOutletId)
            : collect();
        $employees = $selectedOutletId
            ? $this->rbacRepository->getUsersForOutlet($selectedOutletId)
            : collect();
        $permissionCatalog = $this->resolvePermissionCatalog();

        return [
            'outlets' => $outlets
                ->map(fn ($outlet) => [
                    'id' => $outlet->id,
                    'name' => $outlet->name,
                    'is_active' => (bool) $outlet->is_active,
                ])
                ->values()
                ->all(),
            'selectedOutlet' => $selectedOutlet,
            'summary' => $this->buildSummary($roles, $employees, $permissionCatalog),
            'roles' => $this->transformRoles($roles),
            'employees' => $this->transformEmployees($employees),
            'permissionGroups' => $this->buildPermissionGroups($permissionCatalog),
            'roleTypeOptions' => collect(RbacPermissionMatrix::creatableRoleTypes())
                ->map(fn (string $type) => [
                    'value' => $type,
                    'label' => RbacPermissionMatrix::defaultRoleName($type),
                ])
                ->values()
                ->all(),
            'defaultPermissionMatrix' => collect(RbacPermissionMatrix::roleTypeLabels())
                ->mapWithKeys(fn ($label, $type) => [$type => RbacPermissionMatrix::defaultsForRoleType($type)])
                ->all(),
            'permissionMatrix' => $this->buildPermissionMatrix($roles, $permissionCatalog),
            'filters' => [
                'outlet_id' => $selectedOutletId,
            ],
        ];
    }

    public function createRole(array $payload, User $actor): void
    {
        $this->assertCanManage($actor);
        $this->ensureRbacSchemaReady();
        $outlet = $this->rbacRepository->findOutlet($payload['outlet_id']);

        if (!$outlet) {
            abort(404);
        }

        $this->ensureUniqueRoleName($payload['outlet_id'], $payload['name']);

        DB::transaction(function () use ($payload) {
            $role = $this->rbacRepository->createRole([
                'outlet_id' => $payload['outlet_id'],
                'name' => trim((string) $payload['name']),
                'type' => $payload['type'],
                'is_active' => (bool) ($payload['is_active'] ?? true),
            ]);

            $permissionIds = $this->resolvePermissionIds(
                $payload['permissions'] ?? [],
                $payload['type'],
            );

            $this->rbacRepository->syncRolePermissions($role, $permissionIds);
        });
    }

    public function updateRole(Role $role, array $payload, User $actor): void
    {
        $this->assertCanManage($actor);
        $this->ensureRbacSchemaReady();

        $scopedRole = $this->rbacRepository->findRoleInOutlet($role->id, $payload['outlet_id']);

        if (!$scopedRole) {
            abort(404);
        }

        if ($scopedRole->type === 'owner') {
            throw ValidationException::withMessages([
                'name' => 'Role owner bawaan tidak bisa diubah dari menu RBAC.',
            ]);
        }

        $this->ensureUniqueRoleName($payload['outlet_id'], $payload['name'], $scopedRole->id);

        if (!($payload['is_active'] ?? true) && $scopedRole->users()->where('is_active', true)->count() > 0) {
            throw ValidationException::withMessages([
                'is_active' => 'Role yang masih dipakai user aktif tidak boleh dinonaktifkan.',
            ]);
        }

        DB::transaction(function () use ($scopedRole, $payload) {
            $this->rbacRepository->updateRole($scopedRole, [
                'name' => trim((string) $payload['name']),
                'type' => $payload['type'],
                'is_active' => (bool) ($payload['is_active'] ?? true),
            ]);

            $permissionIds = $this->resolvePermissionIds(
                $payload['permissions'] ?? [],
                $payload['type'],
            );

            $this->rbacRepository->syncRolePermissions($scopedRole, $permissionIds);
        });
    }

    public function assignUserRole(User $employee, array $payload, User $actor): void
    {
        $this->assertCanManage($actor);

        $scopedEmployee = $this->rbacRepository->findUserInOutlet($employee->id, $payload['outlet_id']);
        $targetRole = $this->rbacRepository->findRoleInOutlet($payload['role_id'], $payload['outlet_id']);

        if (!$scopedEmployee || !$targetRole) {
            abort(404);
        }

        if (!$targetRole->is_active) {
            throw ValidationException::withMessages([
                'role_id' => 'Role target sedang nonaktif.',
            ]);
        }

        $this->rbacRepository->updateUserRole($scopedEmployee, $targetRole->id);
    }

    public function saveMatrix(array $payload, User $actor): void
    {
        $this->assertCanManage($actor);
        $this->ensureRbacSchemaReady();

        DB::transaction(function () use ($payload) {
            foreach ($payload['roles'] as $item) {
                $roleId    = $item['role_id'];
                $outletId  = $payload['outlet_id'];

                $role = $this->rbacRepository->findRoleInOutlet($roleId, $outletId);

                if (!$role || $role->type === 'owner') {
                    continue;
                }

                $permissionIds = $this->resolvePermissionIds(
                    $item['permissions'] ?? [],
                    $role->type,
                );

                $this->rbacRepository->syncRolePermissions($role, $permissionIds);
            }
        });
    }

    protected function transformRoles(Collection $roles): array
    {
        return $roles->map(function (Role $role) {
            $permissionNames = $role->relationLoaded('permissions')
                ? $role->permissions->map(fn ($permission) => $permission->name)->values()->all()
                : [];
            $defaultName = RbacPermissionMatrix::defaultRoleName($role->type);

            return [
                'id' => $role->id,
                'name' => $role->name,
                'type' => $role->type,
                'type_label' => RbacPermissionMatrix::defaultRoleName($role->type),
                'is_active' => (bool) $role->is_active,
                'users_count' => (int) ($role->users_count ?? 0),
                'permission_names' => $permissionNames,
                'permission_count' => count($permissionNames),
                'is_locked' => $role->type === 'owner',
                'is_custom' => mb_strtolower(trim($role->name)) !== mb_strtolower($defaultName),
            ];
        })->values()->all();
    }

    protected function transformEmployees(Collection $employees): array
    {
        return $employees->map(fn (User $employee) => [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'is_active' => (bool) $employee->is_active,
            'role_id' => $employee->role_id,
            'role_name' => $employee->role?->name,
            'role_type' => $employee->role?->type,
            'join_date' => $employee->join_date?->toDateString(),
        ])->values()->all();
    }

    protected function buildPermissionGroups(Collection $catalog): array
    {
        $moduleLabels = RbacPermissionMatrix::moduleLabels();

        return $catalog
            ->groupBy('module')
            ->map(function (Collection $permissions, string $module) use ($moduleLabels) {
                return [
                    'key' => $module,
                    'label' => $moduleLabels[$module] ?? ucfirst($module),
                    'permissions' => $permissions->map(fn ($permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'description' => $permission->description,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function buildPermissionMatrix(Collection $roles, Collection $catalog): array
    {
        $matrix = [];
        foreach ($roles as $role) {
            $names = $role->relationLoaded('permissions')
                ? $role->permissions->pluck('name')->values()->all()
                : [];
            $matrix[$role->id] = $names;
        }

        return $matrix;
    }

    protected function buildSummary(Collection $roles, Collection $employees, Collection $permissionCatalog): array
    {
        return [
            'total_roles' => $roles->count(),
            'active_roles' => $roles->where('is_active', true)->count(),
            'custom_roles' => $roles->filter(function (Role $role) {
                return mb_strtolower(trim($role->name)) !== mb_strtolower(
                    RbacPermissionMatrix::defaultRoleName($role->type),
                );
            })->count(),
            'total_users' => $employees->count(),
            'permission_catalog_count' => $permissionCatalog->count(),
        ];
    }

    protected function resolveSelectedOutlet(Collection $outlets, ?string $requestedOutletId): ?array
    {
        if ($outlets->isEmpty()) {
            return null;
        }

        $selected = $requestedOutletId
            ? $outlets->firstWhere('id', $requestedOutletId)
            : $outlets->first();

        $selected ??= $outlets->first();

        return $selected ? [
            'id' => $selected->id,
            'name' => $selected->name,
            'is_active' => (bool) $selected->is_active,
        ] : null;
    }

    protected function resolvePermissionCatalog(): Collection
    {
        $catalog = $this->rbacRepository->getPermissionCatalog();

        if ($catalog->isNotEmpty()) {
            return $catalog;
        }

        return collect(RbacPermissionMatrix::permissionCatalog())->map(function (array $permission) {
            return (object) [
                'id' => $permission['module'] . ':' . $permission['action'],
                'module' => $permission['module'],
                'action' => $permission['action'],
                'description' => $permission['description'],
                'name' => $permission['module'] . ':' . $permission['action'],
            ];
        });
    }

    protected function ensureUniqueRoleName(string $outletId, string $name, ?string $ignoreRoleId = null): void
    {
        if ($this->rbacRepository->roleNameExists($outletId, $name, $ignoreRoleId)) {
            throw ValidationException::withMessages([
                'name' => 'Nama role sudah dipakai di outlet ini.',
            ]);
        }
    }

    protected function resolvePermissionIds(array $selectedPermissionNames, string $roleType): array
    {
        $requestedNames = collect($selectedPermissionNames)
            ->map(fn ($permission) => trim((string) $permission))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($requestedNames === []) {
            $requestedNames = RbacPermissionMatrix::defaultsForRoleType($roleType);
        }

        $permissions = $this->rbacRepository->getPermissionsByNames($requestedNames);
        $resolvedNames = $permissions->map(fn ($permission) => $permission->name)->values()->all();
        $missingNames = array_values(array_diff($requestedNames, $resolvedNames));

        if ($missingNames !== []) {
            throw ValidationException::withMessages([
                'permissions' => 'Permission tidak valid: ' . implode(', ', $missingNames),
            ]);
        }

        return $permissions->pluck('id')->values()->all();
    }

    protected function ensureRbacSchemaReady(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_permissions')) {
            throw ValidationException::withMessages([
                'permissions' => 'Schema RBAC belum tersedia. Jalankan migration terbaru terlebih dahulu.',
            ]);
        }
    }

    protected function assertCanManage(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Menu User & RBAC hanya tersedia untuk owner.');
        }
    }
}
