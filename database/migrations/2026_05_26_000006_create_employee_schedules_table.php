<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_schedules')) {
            return;
        }

        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shift_template_id')->constrained('shift_templates')->cascadeOnDelete();
            $table->date('schedule_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'schedule_date'], 'uniq_employee_schedules_user_date');
            $table->index(['outlet_id', 'schedule_date'], 'idx_employee_schedules_outlet_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
    }
};
