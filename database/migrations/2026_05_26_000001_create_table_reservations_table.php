<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('table_reservations')) {
            return;
        }

        Schema::create('table_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->foreignUuid('table_id')->constrained('tables')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->unsignedInteger('guest_count')->default(1);
            $table->timestamp('reserved_for');
            $table->string('status', 20)->default('booked');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['table_id', 'status'], 'idx_table_reservations_table_status');
            $table->index(['outlet_id', 'reserved_for'], 'idx_table_reservations_outlet_reserved_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_reservations');
    }
};
