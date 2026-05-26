<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_ingredients')) {
            return;
        }

        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('raw_material_id')->constrained('raw_materials');
            $table->decimal('quantity', 15, 3);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
    }
};
