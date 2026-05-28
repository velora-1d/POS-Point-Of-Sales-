<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (!Schema::hasTable('security_activity_logs')) {
            Schema::create('security_activity_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
                $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_name')->nullable();
                $table->string('actor_role')->nullable();
                $table->string('action', 100);
                $table->string('description');
                $table->string('ip_address', 64)->nullable();
                $table->string('status', 20)->default('info');
                $table->json('metadata')->nullable();
                $table->timestampsTz();

                $table->index(['outlet_id', 'created_at'], 'idx_security_logs_outlet_created');
                $table->index(['status', 'created_at'], 'idx_security_logs_status_created');
            });

            return;
        }

        Schema::table('security_activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('security_activity_logs', 'actor_name')) {
                $table->string('actor_name')->nullable();
            }
            if (!Schema::hasColumn('security_activity_logs', 'actor_role')) {
                $table->string('actor_role')->nullable();
            }
            if (!Schema::hasColumn('security_activity_logs', 'action')) {
                $table->string('action', 100)->nullable();
            }
            if (!Schema::hasColumn('security_activity_logs', 'description')) {
                $table->string('description')->nullable();
            }
            if (!Schema::hasColumn('security_activity_logs', 'ip_address')) {
                $table->string('ip_address', 64)->nullable();
            }
            if (!Schema::hasColumn('security_activity_logs', 'status')) {
                $table->string('status', 20)->default('info');
            }
            if (!Schema::hasColumn('security_activity_logs', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (!Schema::hasColumn('security_activity_logs', 'created_at')) {
                $table->timestampTz('created_at')->nullable();
            }
            if (!Schema::hasColumn('security_activity_logs', 'updated_at')) {
                $table->timestampTz('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_activity_logs');
    }
};
