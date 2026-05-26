<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_stocks')) {
            return;
        }

        Schema::create('product_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->unsignedInteger('current_stock')->default(0);
            $table->unsignedInteger('minimum_stock')->default(5);
            $table->string('unit', 20)->default('pcs');
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'outlet_id'], 'uniq_product_stocks_product_outlet');
            $table->index(['outlet_id', 'current_stock'], 'idx_product_stocks_outlet_stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
