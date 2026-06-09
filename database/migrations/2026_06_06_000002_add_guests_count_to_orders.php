<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'guests_count')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedInteger('guests_count')->default(1)->after('type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('guests_count');
        });
    }
};
