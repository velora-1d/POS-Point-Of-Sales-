<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('table_qr_configs')) {
            return;
        }

        Schema::create('table_qr_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('store_slug', 100)->unique('uq_table_qr_configs_store_slug');
            $table->string('qr_template', 50)->default('classic_sharp');
            $table->string('primary_color', 20)->default('#111827');
            $table->timestampTz('bulk_regenerated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('outlet_id', 'uq_table_qr_configs_outlet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_qr_configs');
    }
};
