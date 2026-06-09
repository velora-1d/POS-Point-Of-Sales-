<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('notification_settings', 'table_duration_alert_enabled')) {
                $table->boolean('table_duration_alert_enabled')->default(true)->after('metadata');
            }
            if (!Schema::hasColumn('notification_settings', 'table_duration_warning_minutes')) {
                $table->unsignedInteger('table_duration_warning_minutes')->default(90)->after('table_duration_alert_enabled');
            }
            if (!Schema::hasColumn('notification_settings', 'table_duration_danger_minutes')) {
                $table->unsignedInteger('table_duration_danger_minutes')->default(180)->after('table_duration_warning_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn([
                'table_duration_alert_enabled',
                'table_duration_warning_minutes',
                'table_duration_danger_minutes',
            ]);
        });
    }
};
