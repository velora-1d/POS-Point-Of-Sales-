<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('printer_configs')) {
            return;
        }

        Schema::create('printer_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('printer_type', 50)->default('thermal');
            $table->string('connection_type', 50)->default('usb');
            $table->string('device_name', 255)->nullable();
            $table->string('ip_address', 100)->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('default_receipt_method', 20)->default('print');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('outlet_id', 'uq_printer_configs_outlet');
            $table->index(['printer_type', 'connection_type'], 'idx_printer_configs_type_connection');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_configs');
    }
};
