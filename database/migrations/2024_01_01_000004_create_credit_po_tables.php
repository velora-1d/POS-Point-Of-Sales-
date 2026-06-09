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
        // 1. KASBON
        if (!Schema::hasTable('kasbon')) {
            Schema::create('kasbon', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignUuid('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->decimal('total_amount', 15, 2);
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->decimal('remaining_amount', 15, 2);
                $table->timestamp('due_date')->nullable();
                $table->string('status', 20)->default('outstanding'); // outstanding, partial, lunas, written_off
                $table->text('notes')->nullable();
                $table->uuid('created_by'); // user id
                $table->uuid('approval_id')->nullable(); // ref to approvals
                $table->timestamps();
            });
        }

        // 2. KASBON PAYMENTS
        if (!Schema::hasTable('kasbon_payments')) {
            Schema::create('kasbon_payments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('kasbon_id')->constrained('kasbon')->cascadeOnDelete();
                $table->decimal('amount', 15, 2);
                $table->string('payment_method', 30);
                $table->text('notes')->nullable();
                $table->uuid('received_by'); // user id
                $table->timestamp('paid_at')->useCurrent();
            });
        }

        // 3. CICILAN
        if (!Schema::hasTable('cicilan')) {
            Schema::create('cicilan', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('kasbon_id')->constrained('kasbon')->cascadeOnDelete();
                $table->integer('installment_number');
                $table->decimal('amount', 15, 2);
                $table->timestamp('due_date');
                $table->timestamp('paid_at')->nullable();
                $table->string('status', 20)->default('active'); // active, completed, overdue, cancelled
            });
        }

        // 4. PO ORDERS
        if (!Schema::hasTable('po_orders')) {
            Schema::create('po_orders', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignUuid('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->decimal('total_amount', 15, 2);
                $table->decimal('dp_amount', 15, 2)->default(0);
                $table->decimal('remaining_amount', 15, 2);
                $table->timestamp('pickup_date')->nullable();
                $table->string('status', 20)->default('pending'); // pending, dp_paid, completed, cancelled
                $table->text('notes')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->uuid('approval_id')->nullable();
                $table->timestamps();
            });
        }

        // 5. ORDER DISCOUNTS
        if (!Schema::hasTable('order_discounts')) {
            Schema::create('order_discounts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignUuid('promo_id')->nullable()->constrained('promos')->nullOnDelete();
                $table->string('discount_type', 20); // promo, member_tier, manual
                $table->decimal('discount_amount', 15, 2);
                $table->uuid('applied_by')->nullable();
                $table->uuid('approval_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_discounts');
        Schema::dropIfExists('po_orders');
        Schema::dropIfExists('cicilan');
        Schema::dropIfExists('kasbon_payments');
        Schema::dropIfExists('kasbon');
    }
};
