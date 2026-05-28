<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (!Schema::hasTable('attendance')) {
            Schema::create('attendance', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('schedule_id')->nullable()->constrained('employee_schedules')->nullOnDelete();
                $table->timestamp('clock_in')->nullable();
                $table->timestamp('clock_out')->nullable();
                $table->string('status', 20)->default('present');
                $table->text('notes')->nullable();
                $table->timestamp('date');
                $table->foreignUuid('corrected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('corrected_at')->nullable();
                $table->text('correction_reason')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'date'], 'uniq_attendance_user_date');
                $table->index(['outlet_id', 'date'], 'idx_attendance_outlet_date');
                $table->index('status', 'idx_attendance_status');
            });

            return;
        }

        Schema::table('attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance', 'corrected_by')) {
                $table->foreignUuid('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('attendance', 'corrected_at')) {
                $table->timestamp('corrected_at')->nullable();
            }

            if (!Schema::hasColumn('attendance', 'correction_reason')) {
                $table->text('correction_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendance')) {
            return;
        }

        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasColumn('attendance', 'corrected_by')) {
                $table->dropConstrainedForeignId('corrected_by');
            }

            if (Schema::hasColumn('attendance', 'corrected_at')) {
                $table->dropColumn('corrected_at');
            }

            if (Schema::hasColumn('attendance', 'correction_reason')) {
                $table->dropColumn('correction_reason');
            }
        });
    }
};
