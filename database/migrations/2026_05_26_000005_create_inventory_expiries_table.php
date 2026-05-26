<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_expiries')) {
            return;
        }

        Schema::create('inventory_expiries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('trackable_type', 30);
            $table->uuid('trackable_id');
            $table->unsignedInteger('quantity')->default(0);
            $table->string('batch_code', 80)->nullable();
            $table->date('expired_at');
            $table->json('reminder_days')->nullable();
            $table->string('expired_action', 50)->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->string('resolved_action', 50)->nullable();
            $table->text('resolved_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'trackable_type'], 'idx_inventory_expiries_outlet_type');
            $table->index(['outlet_id', 'expired_at'], 'idx_inventory_expiries_outlet_expired_at');
            $table->index(['outlet_id', 'is_resolved'], 'idx_inventory_expiries_outlet_resolved');
            $table->index(['trackable_type', 'trackable_id'], 'idx_inventory_expiries_trackable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_expiries');
    }
};
