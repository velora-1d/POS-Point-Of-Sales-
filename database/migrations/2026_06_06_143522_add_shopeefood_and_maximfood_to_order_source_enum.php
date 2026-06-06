<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TYPE order_source ADD VALUE IF NOT EXISTS 'shopeefood'");
        DB::statement("ALTER TYPE order_source ADD VALUE IF NOT EXISTS 'maximfood'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // PostgreSQL does not support removing values from an enum type easily.
    }
};
