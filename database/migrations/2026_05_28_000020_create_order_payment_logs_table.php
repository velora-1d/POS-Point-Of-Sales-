<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('order_payment_logs')) {
            return;
        }

        Schema::create('order_payment_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_type', 30);
            $table->string('payment_method', 20)->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('before_paid_amount', 12, 2)->default(0);
            $table->decimal('after_paid_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['order_id', 'created_at'], 'idx_order_payment_logs_order_created');
            $table->index(['payment_type', 'payment_method'], 'idx_order_payment_logs_type_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payment_logs');
    }
};
