<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('notification_settings')) {
            return;
        }

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->boolean('low_stock_enabled')->default(true);
            $table->json('low_stock_channels')->nullable();
            $table->boolean('kasbon_due_enabled')->default(true);
            $table->json('kasbon_due_channels')->nullable();
            $table->unsignedSmallInteger('kasbon_due_threshold_days')->default(3);
            $table->boolean('online_order_enabled')->default(true);
            $table->json('online_order_channels')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('outlet_id', 'uq_notification_settings_outlet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
