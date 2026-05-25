<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use App\Models\Table;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductPrice;
use App\Models\Customer;
use App\Models\MembershipTier;
use App\Models\Membership;
use App\Models\Promo;
use App\Models\ShiftTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Bersihkan data lama agar tidak duplikat saat seeder dijalankan ulang
        \Illuminate\Support\Facades\DB::statement('TRUNCATE TABLE users, roles, outlets, tables, categories, products, product_variants, product_prices, membership_tiers, customers, memberships, promos, shift_templates CASCADE;');

        $jsonPath = database_path('seeders/data/pos_seeder_data.json');
        if (!file_exists($jsonPath)) {
            throw new \RuntimeException("Seeder data file not found at: {$jsonPath}");
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (!$data) {
            throw new \RuntimeException("Invalid JSON seeder data.");
        }

        // 1. Seed Outlets
        $outletIds = [];
        foreach ($data['outlets'] as $outletData) {
            $outletId = (string) Str::uuid();
            Outlet::create([
                'id' => $outletId,
                'name' => $outletData['name'],
                'address' => $outletData['address'] ?? null,
                'phone' => $outletData['phone'] ?? null,
                'is_active' => true,
                'settings' => $outletData['settings'] ?? [],
            ]);
            $outletIds[] = $outletId;
        }

        // Assign roles and users to the first outlet.
        $primaryOutletId = $outletIds[0];

        // 2. Seed Roles
        $roleIds = []; // Mapping role_type -> role_id
        foreach ($data['roles'] as $roleData) {
            $roleId = (string) Str::uuid();
            Role::create([
                'id' => $roleId,
                'outlet_id' => $primaryOutletId,
                'name' => $roleData['name'],
                'type' => $roleData['type'],
                'is_active' => true,
            ]);
            $roleIds[$roleData['type']] = $roleId;
        }

        // 3. Seed Users
        foreach ($data['users'] as $userData) {
            User::create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $primaryOutletId,
                'role_id' => $roleIds[$userData['role_type']],
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'] ?? null,
                'password_hash' => Hash::make($userData['password']),
                'approval_pin' => Hash::make($userData['approval_pin']),
                'is_active' => true,
                'join_date' => now(),
            ]);
        }

        // 4. Seed Tables
        foreach ($data['tables'] as $tableData) {
            Table::create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $primaryOutletId,
                'name' => $tableData['name'],
                'capacity' => $tableData['capacity'],
                'qr_code' => 'QR-' . strtoupper(Str::random(10)),
                'status' => 'available',
                'is_active' => true,
            ]);
        }

        // 5. Seed Categories
        $categoryIds = []; // Mapping category_name -> category_id
        foreach ($data['categories'] as $categoryData) {
            $categoryId = (string) Str::uuid();
            Category::create([
                'id' => $categoryId,
                'outlet_id' => $primaryOutletId,
                'name' => $categoryData['name'],
                'description' => $categoryData['description'] ?? null,
                'sort_order' => 0,
                'is_active' => true,
            ]);
            $categoryIds[$categoryData['name']] = $categoryId;
        }

        // 6. Seed Products
        foreach ($data['products'] as $productData) {
            $productId = (string) Str::uuid();
            Product::create([
                'id' => $productId,
                'outlet_id' => $primaryOutletId,
                'category_id' => $categoryIds[$productData['category_name']],
                'name' => $productData['name'],
                'description' => $productData['description'] ?? null,
                'base_price' => $productData['base_price'],
                'hpp' => $productData['hpp'] ?? null,
                'is_available' => true,
                'is_active' => true,
                'track_stock' => true,
            ]);

            // Seed Variants for this product
            if (isset($productData['variants'])) {
                foreach ($productData['variants'] as $variantData) {
                    ProductVariant::create([
                        'id' => (string) Str::uuid(),
                        'product_id' => $productId,
                        'name' => $variantData['name'],
                        'additional_price' => $variantData['additional_price'],
                        'is_active' => true,
                    ]);
                }
            }

            // Seed Prices for this product
            if (isset($productData['prices'])) {
                foreach ($productData['prices'] as $priceData) {
                    ProductPrice::create([
                        'id' => (string) Str::uuid(),
                        'product_id' => $productId,
                        'outlet_id' => $primaryOutletId,
                        'tier' => $priceData['tier'],
                        'price' => $priceData['price'],
                        'is_active' => true,
                    ]);
                }
            }
        }

        // 7. Seed Membership Tiers
        $tierIds = []; // Mapping tier_enum -> tier_id
        foreach ($data['membership_tiers'] as $tierData) {
            $tierId = (string) Str::uuid();
            MembershipTier::create([
                'id' => $tierId,
                'outlet_id' => $primaryOutletId,
                'tier' => $tierData['tier'],
                'name' => $tierData['name'],
                'point_threshold' => $tierData['point_threshold'],
                'point_rate_per_amount' => $tierData['point_rate_per_amount'],
                'discount_percent' => $tierData['discount_percent'],
                'description' => $tierData['description'] ?? null,
                'is_active' => true,
            ]);
            $tierIds[$tierData['tier']] = $tierId;
        }

        // 8. Seed Customers & Memberships
        foreach ($data['customers'] as $customerData) {
            $customerId = (string) Str::uuid();
            Customer::create([
                'id' => $customerId,
                'outlet_id' => $primaryOutletId,
                'name' => $customerData['name'],
                'phone' => $customerData['phone'],
                'email' => $customerData['email'] ?? null,
                'is_active' => true,
                'registered_via' => 'kasir',
            ]);

            // Create membership for the customer
            Membership::create([
                'id' => (string) Str::uuid(),
                'customer_id' => $customerId,
                'tier_id' => $tierIds[$customerData['tier']],
                'total_points' => $customerData['total_points'],
                'lifetime_points' => $customerData['lifetime_points'],
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        // 9. Seed Promos
        // Find owner user to set as creator
        $ownerUser = User::whereHas('role', function($q) { $q->where('type', 'owner'); })->first();
        $creatorId = $ownerUser ? $ownerUser->id : (string) Str::uuid();

        foreach ($data['promos'] as $promoData) {
            Promo::create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $primaryOutletId,
                'name' => $promoData['name'],
                'code' => $promoData['code'],
                'type' => $promoData['type'],
                'apply_method' => $promoData['apply_method'],
                'discount_percent' => $promoData['discount_percent'],
                'discount_amount' => $promoData['discount_amount'],
                'max_discount_amount' => $promoData['max_discount_amount'],
                'min_transaction_amount' => $promoData['min_transaction_amount'],
                'can_stack' => $promoData['can_stack'] ?? false,
                'usage_limit' => $promoData['usage_limit'],
                'usage_count' => 0,
                'start_date' => $promoData['start_date'],
                'end_date' => $promoData['end_date'] ?? null,
                'status' => $promoData['status'],
                'created_by' => $creatorId,
            ]);
        }

        // 10. Seed Shift Templates
        foreach ($data['shift_templates'] as $shiftData) {
            ShiftTemplate::create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $primaryOutletId,
                'name' => $shiftData['name'],
                'start_time' => $shiftData['start_time'],
                'end_time' => $shiftData['end_time'],
                'is_active' => true,
            ]);
        }
    }
}



