<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            if (!Schema::hasColumn('tables', 'current_guests')) {
                $table->unsignedInteger('current_guests')->default(0)->after('capacity');
            }
            if (!Schema::hasColumn('tables', 'occupied_at')) {
                $table->timestamp('occupied_at')->nullable()->after('current_guests');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn(['current_guests', 'occupied_at']);
        });
    }
};
