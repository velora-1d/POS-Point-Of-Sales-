<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mengubah kolom expired_reminder_days dari native array PostgreSQL ke jsonb
        // agar kompatibel dengan Eloquent 'array' cast.
        DB::statement('ALTER TABLE products ALTER COLUMN expired_reminder_days TYPE jsonb USING to_jsonb(expired_reminder_days)');
        DB::statement('ALTER TABLE raw_materials ALTER COLUMN expired_reminder_days TYPE jsonb USING to_jsonb(expired_reminder_days)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback ke format integer array native PostgreSQL
        DB::statement('ALTER TABLE products ALTER COLUMN expired_reminder_days TYPE integer[] USING ARRAY(SELECT jsonb_array_elements_text(expired_reminder_days)::integer)');
        DB::statement('ALTER TABLE raw_materials ALTER COLUMN expired_reminder_days TYPE integer[] USING ARRAY(SELECT jsonb_array_elements_text(expired_reminder_days)::integer)');
    }
};
