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
        // 1. OUTLETS
        if (!Schema::hasTable('outlets')) {
            Schema::create('outlets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 100);
                $table->text('address')->nullable();
                $table->string('phone', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->jsonb('settings')->nullable(); // JSONB for PostgreSQL
                $table->timestamps();
            });
        }

        // 2. ROLES
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->nullable()->constrained('outlets')->cascadeOnDelete();
                $table->string('name', 50);
                $table->string('type', 20); // Enum substitute: owner, supervisor, kasir, bar, kitchen
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. CATEGORIES
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. TABLES (MEJA)
        if (!Schema::hasTable('tables')) {
            Schema::create('tables', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->string('name', 50);
                $table->integer('capacity')->nullable();
                $table->string('category', 30)->default('indoor');
                $table->string('qr_code', 255)->nullable();
                $table->string('barcode_tracking', 100)->nullable();
                $table->string('qr_session_token', 100)->nullable()->unique();
                $table->integer('position_x')->nullable();
                $table->integer('position_y')->nullable();
                $table->string('status', 20)->default('available'); // available, occupied, reserved
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 5. CUSTOMERS
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->string('name', 100)->nullable();
                $table->string('phone', 20);
                $table->string('email', 100)->nullable();
                $table->timestamp('birthdate')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('registered_via', 20)->default('kasir');
                $table->timestamps();
            });
        }
        // 6. USERS (Fix existing or create new)
        if (Schema::hasTable('users')) {
            // Check if primary key exists
            $pkExists = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'users'::regclass AND contype = 'p'"))->isNotEmpty();
            if (!$pkExists) {
                Schema::table('users', function (Blueprint $table) {
                    $table->primary('id');
                });
            }
        } else {
            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('email')->unique();
                $table->string('phone', 30)->nullable();
                $table->string('password_hash');
                $table->string('approval_pin', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('join_date')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('tables');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('outlets');
    }
};
