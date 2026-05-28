<?php

use App\Support\RbacPermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('module', 50);
                $table->string('action', 50);
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['module', 'action'], 'uq_permissions_module_action');
                $table->index(['module'], 'idx_permissions_module');
            });
        }

        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('permissions', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }

                if (!Schema::hasColumn('permissions', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['role_id', 'permission_id'], 'uq_role_permissions_role_permission');
                $table->index(['permission_id'], 'idx_role_permissions_permission_id');
            });
        }

        if (Schema::hasTable('role_permissions')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('role_permissions', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }

                if (!Schema::hasColumn('role_permissions', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        $now = now();
        foreach (RbacPermissionMatrix::permissionCatalog() as $permission) {
            $existingPermission = DB::table('permissions')
                ->where('module', $permission['module'])
                ->where('action', $permission['action'])
                ->first(['id']);

            if ($existingPermission) {
                DB::table('permissions')
                    ->where('id', $existingPermission->id)
                    ->update([
                        'description' => $permission['description'] ?? null,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('permissions')->insert([
                'id' => (string) Str::uuid(),
                'module' => $permission['module'],
                'action' => $permission['action'],
                'description' => $permission['description'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIdByName = DB::table('permissions')
            ->get(['id', 'module', 'action'])
            ->mapWithKeys(fn ($permission) => [
                $permission->module . ':' . $permission->action => $permission->id,
            ]);

        $roles = DB::table('roles')->select(['id', 'type'])->get();

        foreach ($roles as $role) {
            $hasAssignments = DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->exists();

            if ($hasAssignments) {
                continue;
            }

            $defaultPermissions = RbacPermissionMatrix::defaultsForRoleType((string) $role->type);

            $rows = collect($defaultPermissions)
                ->map(function (string $permissionName) use ($permissionIdByName, $role, $now) {
                    $permissionId = $permissionIdByName->get($permissionName);

                    if (!$permissionId) {
                        return null;
                    }

                    return [
                        'id' => (string) Str::uuid(),
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            if ($rows !== []) {
                DB::table('role_permissions')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
