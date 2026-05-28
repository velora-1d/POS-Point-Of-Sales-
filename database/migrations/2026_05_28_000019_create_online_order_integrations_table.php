<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('online_order_integrations')) {
            return;
        }

        Schema::create('online_order_integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('platform', 20);
            $table->boolean('is_active')->default(false);
            $table->string('environment', 20)->default('production');
            $table->string('merchant_id')->nullable();
            $table->string('external_outlet_id')->nullable();
            $table->text('api_key_encrypted')->nullable();
            $table->text('api_secret_encrypted')->nullable();
            $table->timestampTz('last_synced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->unique(['outlet_id', 'platform'], 'uniq_online_integrations_outlet_platform');
            $table->index(['platform', 'is_active'], 'idx_online_integrations_platform_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_order_integrations');
    }
};
