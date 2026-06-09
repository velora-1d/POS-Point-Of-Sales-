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
        // 1. PRODUCTS
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->string('image_url', 255)->nullable();
                $table->decimal('base_price', 15, 2);
                $table->decimal('hpp', 15, 2)->nullable();
                $table->boolean('is_available')->default(true);
                $table->boolean('is_active')->default(true);
                $table->boolean('track_stock')->default(true);
                $table->boolean('track_expired')->default(false);
                $table->string('expired_action', 20)->default('notify_only');
                $table->jsonb('expired_reminder_days')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 2. PRODUCT VARIANTS
        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('name', 100);
                $table->decimal('additional_price', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. PRODUCT PRICES (MULTI HARGA)
        if (!Schema::hasTable('product_prices')) {
            Schema::create('product_prices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignUuid('outlet_id')->nullable()->constrained('outlets')->cascadeOnDelete();
                $table->string('tier', 20); // normal, member, grosir, custom
                $table->string('tier_label', 50)->nullable();
                $table->decimal('price', 15, 2);
                $table->time('happy_hour_start')->nullable();
                $table->time('happy_hour_end')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
