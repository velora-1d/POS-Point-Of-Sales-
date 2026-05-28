<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('payment_gateway_configs')) {
            return;
        }

        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('provider', 50)->default('pakasir');
            $table->boolean('is_active')->default(false);
            $table->string('base_url', 255)->nullable();
            $table->string('project_slug', 100)->nullable();
            $table->string('callback_url', 255)->nullable();
            $table->text('api_key_encrypted')->nullable();
            $table->text('api_secret_encrypted')->nullable();
            $table->json('active_payment_methods')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('outlet_id', 'uq_payment_gateway_configs_outlet');
            $table->index(['provider', 'is_active'], 'idx_payment_gateway_configs_provider_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_configs');
    }
};
