<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            $duplicatePermissions = DB::table('permissions')
                ->select('module', 'action', DB::raw('count(*) as total'))
                ->groupBy('module', 'action')
                ->havingRaw('count(*) > 1')
                ->exists();

            if ($duplicatePermissions) {
                throw new RuntimeException('Tidak bisa menambahkan unique index permissions(module, action) karena masih ada data duplikat.');
            }

            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_permissions_module_action ON permissions (module, action)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_permissions_module ON permissions (module)');
        }

        if (Schema::hasTable('approval_rules')) {
            $duplicateApprovalRules = DB::table('approval_rules')
                ->select('outlet_id', DB::raw('count(*) as total'))
                ->whereNotNull('outlet_id')
                ->groupBy('outlet_id')
                ->havingRaw('count(*) > 1')
                ->exists();

            if ($duplicateApprovalRules) {
                throw new RuntimeException('Tidak bisa menambahkan unique index approval_rules(outlet_id) karena masih ada lebih dari satu rule per outlet.');
            }

            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_approval_rules_outlet ON approval_rules (outlet_id)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_permissions_module_action');
        DB::statement('DROP INDEX IF EXISTS idx_permissions_module');
        DB::statement('DROP INDEX IF EXISTS uq_approval_rules_outlet');
    }
};
