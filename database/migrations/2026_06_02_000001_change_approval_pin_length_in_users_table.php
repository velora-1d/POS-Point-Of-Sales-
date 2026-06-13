<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN approval_pin TYPE VARCHAR(255)');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('approval_pin', 255)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN approval_pin TYPE VARCHAR(20)');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('approval_pin', 20)->change();
            });
        }
    }
};
