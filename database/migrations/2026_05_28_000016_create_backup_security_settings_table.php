<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (!Schema::hasTable('backup_security_settings')) {
            Schema::create('backup_security_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->boolean('auto_backup_enabled')->default(true);
                $table->string('auto_backup_frequency', 20)->default('daily');
                $table->time('auto_backup_time')->default('03:00:00');
                $table->unsignedSmallInteger('retention_days')->default(30);
                $table->string('backup_channel', 30)->default('hybrid');
                $table->boolean('encryption_enabled')->default(true);
                $table->boolean('two_factor_required')->default(false);
                $table->string('last_backup_status', 20)->nullable();
                $table->timestampTz('last_backup_at')->nullable();
                $table->string('last_backup_file_name')->nullable();
                $table->unsignedBigInteger('last_backup_size_bytes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestampsTz();

                $table->unique('outlet_id', 'uq_backup_security_settings_outlet');
                $table->index(['auto_backup_enabled', 'auto_backup_frequency'], 'idx_backup_security_schedule');
            });

            return;
        }

        Schema::table('backup_security_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('backup_security_settings', 'auto_backup_enabled')) {
                $table->boolean('auto_backup_enabled')->default(true);
            }
            if (!Schema::hasColumn('backup_security_settings', 'auto_backup_frequency')) {
                $table->string('auto_backup_frequency', 20)->default('daily');
            }
            if (!Schema::hasColumn('backup_security_settings', 'auto_backup_time')) {
                $table->time('auto_backup_time')->default('03:00:00');
            }
            if (!Schema::hasColumn('backup_security_settings', 'retention_days')) {
                $table->unsignedSmallInteger('retention_days')->default(30);
            }
            if (!Schema::hasColumn('backup_security_settings', 'backup_channel')) {
                $table->string('backup_channel', 30)->default('hybrid');
            }
            if (!Schema::hasColumn('backup_security_settings', 'encryption_enabled')) {
                $table->boolean('encryption_enabled')->default(true);
            }
            if (!Schema::hasColumn('backup_security_settings', 'two_factor_required')) {
                $table->boolean('two_factor_required')->default(false);
            }
            if (!Schema::hasColumn('backup_security_settings', 'last_backup_status')) {
                $table->string('last_backup_status', 20)->nullable();
            }
            if (!Schema::hasColumn('backup_security_settings', 'last_backup_at')) {
                $table->timestampTz('last_backup_at')->nullable();
            }
            if (!Schema::hasColumn('backup_security_settings', 'last_backup_file_name')) {
                $table->string('last_backup_file_name')->nullable();
            }
            if (!Schema::hasColumn('backup_security_settings', 'last_backup_size_bytes')) {
                $table->unsignedBigInteger('last_backup_size_bytes')->nullable();
            }
            if (!Schema::hasColumn('backup_security_settings', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (!Schema::hasColumn('backup_security_settings', 'created_at')) {
                $table->timestampTz('created_at')->nullable();
            }
            if (!Schema::hasColumn('backup_security_settings', 'updated_at')) {
                $table->timestampTz('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_security_settings');
    }
};
