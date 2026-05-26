<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('raw_materials')) {
            Schema::table('raw_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('raw_materials', 'last_restocked_at')) {
                    $table->timestamp('last_restocked_at')->nullable()->after('is_active');
                }
            });

            return;
        }

        Schema::create('raw_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('unit', 20)->default('gram');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('minimum_stock', 15, 3)->default(0);
            $table->decimal('cost_per_unit', 15, 2)->nullable()->default(0);
            $table->boolean('track_expired')->default(false);
            $table->string('expired_action', 50)->nullable();
            $table->json('expired_reminder_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'is_active'], 'idx_raw_materials_outlet_active');
            $table->index(['outlet_id', 'quantity'], 'idx_raw_materials_outlet_quantity');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('raw_materials') && Schema::hasColumn('raw_materials', 'last_restocked_at')) {
            Schema::table('raw_materials', function (Blueprint $table) {
                $table->dropColumn('last_restocked_at');
            });
        }
    }
};
