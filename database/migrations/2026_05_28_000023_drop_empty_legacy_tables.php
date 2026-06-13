<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $legacyTables = ['approvals', 'cicilan', 'kasbon_payments', 'kasbon', 'po_orders', 'notifications'];

        foreach ($legacyTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $count = DB::table($table)->count();

            if ($count > 0) {
                throw new RuntimeException("Tabel legacy {$table} masih berisi {$count} row. Purge dibatalkan.");
            }
        }

        foreach ($legacyTables as $table) {
            Schema::dropIfExists($table);
        }

        if (DB::getDriverName() === 'pgsql') {
            foreach ([
                'approval_status',
                'approval_action',
                'kasbon_status',
                'cicilan_status',
                'po_status',
                'notification_type',
                'notif_channel',
            ] as $type) {
                DB::statement("DROP TYPE IF EXISTS {$type}");
            }
        }
    }

    public function down(): void
    {
        // Legacy tables intentionally not recreated automatically.
    }
};
