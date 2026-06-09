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
        // 1. LOYALTY POINT LOGS
        if (!Schema::hasTable('loyalty_point_logs')) {
            Schema::create('loyalty_point_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('membership_id')->constrained('memberships')->cascadeOnDelete();
                $table->string('action', 30); // earn, redeem_discount, redeem_product, expire, adjustment
                $table->string('source', 30)->nullable(); // transaction, product, manual
                $table->integer('points');
                $table->string('reference_type', 50)->nullable();
                $table->uuid('reference_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 2. LOYALTY REDEEM CATALOG
        if (!Schema::hasTable('loyalty_redeem_catalog')) {
            Schema::create('loyalty_redeem_catalog', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('point_cost');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. EXPIRED TRACKING
        if (!Schema::hasTable('expired_tracking')) {
            Schema::create('expired_tracking', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('reference_type', 20); // product, raw_material
                $table->uuid('reference_id');
                $table->string('batch_code', 50)->nullable();
                $table->decimal('quantity', 15, 3);
                $table->timestamp('expired_date');
                $table->boolean('is_handled')->default(false);
                $table->timestamp('handled_at')->nullable();
                $table->uuid('handled_by')->nullable();
                $table->text('handled_notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 4. PROMO RULES
        if (!Schema::hasTable('promo_rules')) {
            Schema::create('promo_rules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('promo_id')->constrained('promos')->cascadeOnDelete();
                $table->jsonb('rule_config');
                $table->timestamps();
            });
        }

        // 5. PROMO USAGE LOGS
        if (!Schema::hasTable('promo_usage_logs')) {
            Schema::create('promo_usage_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('promo_id')->constrained('promos')->cascadeOnDelete();
                $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->decimal('discount_amount', 15, 2);
                $table->timestamp('used_at')->useCurrent();
            });
        }

        // 6. SPLIT BILLS
        if (!Schema::hasTable('split_bills')) {
            Schema::create('split_bills', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('original_order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignUuid('split_order_id')->constrained('orders')->cascadeOnDelete();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('split_bills');
        Schema::dropIfExists('promo_usage_logs');
        Schema::dropIfExists('promo_rules');
        Schema::dropIfExists('expired_tracking');
        Schema::dropIfExists('loyalty_redeem_catalog');
        Schema::dropIfExists('loyalty_point_logs');
    }
};
