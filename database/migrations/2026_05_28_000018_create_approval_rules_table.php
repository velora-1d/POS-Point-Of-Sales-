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
            Schema::create('approval_rules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->boolean('manual_discount_enabled')->default(true);
                $table->decimal('manual_discount_threshold', 12, 2)->default(50000);
                $table->boolean('order_edit_enabled')->default(true);
                $table->decimal('order_edit_threshold', 12, 2)->default(150000);
                $table->json('metadata')->nullable();
                $table->timestampsTz();

                $table->unique('outlet_id', 'uq_approval_rules_outlet');
            });

            return;
        }

        Schema::table('approval_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_rules', 'manual_discount_enabled')) {
                $table->boolean('manual_discount_enabled')->default(true);
            }
            if (!Schema::hasColumn('approval_rules', 'manual_discount_threshold')) {
                $table->decimal('manual_discount_threshold', 12, 2)->default(50000);
            }
            if (!Schema::hasColumn('approval_rules', 'order_edit_enabled')) {
                $table->boolean('order_edit_enabled')->default(true);
            }
            if (!Schema::hasColumn('approval_rules', 'order_edit_threshold')) {
                $table->decimal('order_edit_threshold', 12, 2)->default(150000);
            }
            if (!Schema::hasColumn('approval_rules', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });

        DB::table('approval_rules')
            ->whereNull('manual_discount_threshold')
            ->update(['manual_discount_threshold' => 50000]);

        DB::table('approval_rules')
            ->whereNull('order_edit_threshold')
            ->update(['order_edit_threshold' => 150000]);
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_rules');
    }
};
