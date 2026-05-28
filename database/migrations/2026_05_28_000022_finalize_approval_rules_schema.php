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
        if (!Schema::hasTable('approval_rules')) {
            return;
        }

        $legacyColumns = array_values(array_filter([
            Schema::hasColumn('approval_rules', 'action') ? 'action' : null,
            Schema::hasColumn('approval_rules', 'approver_role_id') ? 'approver_role_id' : null,
            Schema::hasColumn('approval_rules', 'mechanism') ? 'mechanism' : null,
            Schema::hasColumn('approval_rules', 'threshold_amount') ? 'threshold_amount' : null,
            Schema::hasColumn('approval_rules', 'escalation_minutes') ? 'escalation_minutes' : null,
            Schema::hasColumn('approval_rules', 'is_active') ? 'is_active' : null,
        ]));

        if ($legacyColumns !== []) {
            $hasLegacyRows = DB::table('approval_rules')
                ->where(function ($query) use ($legacyColumns) {
                    foreach ($legacyColumns as $column) {
                        $query->orWhereNotNull($column);
                    }
                })
                ->exists();

            if ($hasLegacyRows) {
                throw new RuntimeException('approval_rules masih berisi data legacy. Bersihkan atau migrasikan datanya dulu sebelum finalisasi schema.');
            }
        }

        if (Schema::hasColumn('approval_rules', 'approver_role_id')) {
            DB::statement('ALTER TABLE approval_rules DROP CONSTRAINT IF EXISTS approval_rules_approver_role_id_roles_id_fk');
            DB::statement('ALTER TABLE approval_rules DROP CONSTRAINT IF EXISTS approval_rules_approver_role_id_foreign');

            Schema::table('approval_rules', function (Blueprint $table) {
                $table->dropColumn('approver_role_id');
            });
        }

        Schema::table('approval_rules', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach ([
                'action',
                'mechanism',
                'threshold_amount',
                'escalation_minutes',
                'is_active',
            ] as $column) {
                if (Schema::hasColumn('approval_rules', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('approval_rules')) {
            return;
        }

        DB::statement("DO $$ BEGIN CREATE TYPE approval_action AS ENUM ('manual_discount','order_edit'); EXCEPTION WHEN duplicate_object THEN NULL; END $$;");
        DB::statement("DO $$ BEGIN CREATE TYPE approval_mechanism AS ENUM ('pin','whatsapp','both'); EXCEPTION WHEN duplicate_object THEN NULL; END $$;");

        Schema::table('approval_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_rules', 'action')) {
                $table->enum('action', ['manual_discount', 'order_edit'])->default('manual_discount');
            }
            if (!Schema::hasColumn('approval_rules', 'approver_role_id')) {
                $table->foreignUuid('approver_role_id')->nullable()->constrained('roles');
            }
            if (!Schema::hasColumn('approval_rules', 'mechanism')) {
                $table->enum('mechanism', ['pin', 'whatsapp', 'both'])->default('both');
            }
            if (!Schema::hasColumn('approval_rules', 'threshold_amount')) {
                $table->string('threshold_amount', 20)->nullable();
            }
            if (!Schema::hasColumn('approval_rules', 'escalation_minutes')) {
                $table->string('escalation_minutes', 10)->nullable()->default('10');
            }
            if (!Schema::hasColumn('approval_rules', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }
};
