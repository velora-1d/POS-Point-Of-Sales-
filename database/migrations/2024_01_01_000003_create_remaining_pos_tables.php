<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. MEMBERSHIP TIERS
        if (!Schema::hasTable('membership_tiers')) {
            Schema::create('membership_tiers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->string('tier', 20); // bronze, silver, gold
                $table->string('name', 50);
                $table->integer('point_threshold');
                $table->decimal('point_rate_per_amount', 10, 4);
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. MEMBERSHIPS
        if (!Schema::hasTable('memberships')) {
            Schema::create('memberships', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete()->unique();
                $table->foreignUuid('tier_id')->constrained('membership_tiers')->cascadeOnDelete();
                $table->integer('total_points')->default(0);
                $table->integer('lifetime_points')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('joined_at')->useCurrent();
                $table->timestamps();
            });
        }

        // 3. APPROVALS
        if (!Schema::hasTable('approvals')) {
            Schema::create('approvals', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->string('action', 50);
                $table->foreignUuid('requested_by')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 20)->default('pending'); // pending, approved, rejected, escalated
                $table->string('reference_type', 50)->nullable();
                $table->uuid('reference_id')->nullable();
                $table->text('notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('requested_at')->useCurrent();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. SHIFT TEMPLATES
        if (!Schema::hasTable('shift_templates')) {
            Schema::create('shift_templates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->string('name', 50);
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 5. QR SESSIONS
        if (!Schema::hasTable('qr_sessions')) {
            Schema::create('qr_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('table_id')->constrained('tables')->cascadeOnDelete();
                $table->string('token', 100)->unique();
                $table->timestamp('expires_at');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_sessions');
        Schema::dropIfExists('shift_templates');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_tiers');
    }
};
