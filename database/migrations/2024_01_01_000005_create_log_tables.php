<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. ORDER STATUS LOGS
        if (!Schema::hasTable('order_status_logs')) {
            Schema::create('order_status_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30);
                $table->uuid('changed_by')->nullable();
                $table->string('changed_by_type', 20)->nullable(); // user, customer, system
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 2. STOCKS
        if (!Schema::hasTable('stocks')) {
            Schema::create('stocks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete()->unique();
                $table->decimal('quantity', 15, 3)->default(0);
                $table->decimal('reserved_quantity', 15, 3)->default(0);
                $table->decimal('minimum_stock', 15, 3)->default(0);
                $table->string('unit', 20)->default('pcs');
                $table->timestamp('updated_at')->useCurrent();
            });
        }

        // 3. STOCK LOGS
        if (!Schema::hasTable('stock_logs')) {
            Schema::create('stock_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('type', 20); // in, out, adjustment, write_off, reserved, unreserved
                $table->decimal('quantity', 15, 3);
                $table->decimal('quantity_before', 15, 3);
                $table->decimal('quantity_after', 15, 3);
                $table->string('reference_type', 50)->nullable();
                $table->uuid('reference_id')->nullable();
                $table->text('notes')->nullable();
                $table->uuid('created_by');
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_logs');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('order_status_logs');
    }
};
