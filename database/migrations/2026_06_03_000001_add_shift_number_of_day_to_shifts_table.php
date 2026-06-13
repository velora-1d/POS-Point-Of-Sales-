<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (!Schema::hasColumn('shifts', 'shift_number_of_day')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->unsignedSmallInteger('shift_number_of_day')->default(1)->after('status');
            });
        }

        // Backfill existing records: nomor urut per outlet per tanggal
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE shifts s
                SET shift_number_of_day = sub.rn
                FROM (
                    SELECT id,
                           ROW_NUMBER() OVER (
                               PARTITION BY outlet_id, DATE(opened_at)
                               ORDER BY opened_at ASC
                           ) AS rn
                    FROM shifts
                ) sub
                WHERE s.id = sub.id
            ");
        }
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('shift_number_of_day');
        });
    }
};
