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
        // 1. PROMOS
        if (!Schema::hasTable('promos')) {
            Schema::create('promos', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('code', 20)->nullable()->unique();
                $table->string('type', 20); // percent, amount
                $table->string('apply_method', 20)->default('both'); // automatic, manual, both
                $table->decimal('discount_percent', 5, 2)->nullable();
                $table->decimal('discount_amount', 15, 2)->nullable();
                $table->decimal('max_discount_amount', 15, 2)->nullable();
                $table->decimal('min_transaction_amount', 15, 2)->default(0);
                $table->integer('buy_quantity')->nullable();
                $table->integer('get_quantity')->nullable();
                $table->boolean('can_stack')->default(false);
                $table->integer('usage_limit')->nullable();
                $table->integer('usage_count')->default(0);
                $table->timestamp('start_date')->nullable();
                $table->timestamp('end_date')->nullable();
                $table->time('happy_hour_start')->nullable();
                $table->time('happy_hour_end')->nullable();
                $table->string('status', 20)->default('active');
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 2. ORDERS
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignUuid('shift_id')->nullable(); // ref ke shifts (akan dibuat di migration lain atau sudah ada)
                $table->foreignUuid('table_id')->nullable()->constrained('tables')->nullOnDelete();
                $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignUuid('cashier_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('order_number', 20);
                $table->string('source', 20)->default('kasir');
                $table->string('type', 20)->default('dine_in');
                $table->string('status', 30)->default('pending');
                $table->decimal('subtotal', 15, 2);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2);
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->integer('estimated_time')->nullable();
                $table->timestamp('cooking_started_at')->nullable();
                $table->timestamp('pending_started_at')->nullable();
                $table->string('receipt_method', 20)->nullable();
                $table->string('receipt_phone', 20)->nullable();
                $table->boolean('is_printed')->default(false);
                $table->string('external_order_id', 100)->nullable();
                $table->string('external_platform', 20)->nullable();
                $table->boolean('pay_later')->default(false);
                $table->string('qr_session_token', 100)->nullable();
                $table->jsonb('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 3. ORDER ITEMS
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignUuid('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_price', 15, 2);
                $table->decimal('total_price', 15, 2);
                $table->text('notes')->nullable();
                $table->string('price_tier', 30)->nullable();
                $table->timestamps();
            });
        }

        // 4. PAYMENTS
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('method', 30);
                $table->decimal('amount', 15, 2);
                $table->string('status', 20)->default('pending');
                $table->string('reference_number', 100)->nullable();
                $table->jsonb('gateway_response')->nullable();
                $table->foreignUuid('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('promos');
    }
};
