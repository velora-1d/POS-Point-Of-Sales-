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
use App\Models\RawMaterial;
use App\Models\ProductStock;
use App\Models\ProductIngredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentLog;
use App\Models\Shift;
use App\Models\ShiftCashReport;
use App\Models\Attendance;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Bersihkan data lama agar tidak duplikat saat seeder dijalankan ulang
        DB::statement('TRUNCATE TABLE users, roles, outlets, tables, categories, products, product_variants, product_prices, membership_tiers, customers, memberships, promos, shift_templates, raw_materials, product_stocks, product_ingredients, orders, order_items, order_payment_logs, shifts, shift_cash_reports, attendance, expenses CASCADE;');

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

        $primaryOutletId = $outletIds[0];

        // 2. Seed Roles
        $roleIds = [];
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
        $users = [];
        foreach ($data['users'] as $userData) {
            $user = User::create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $primaryOutletId,
                'role_id' => $roleIds[$userData['role_type']],
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'] ?? null,
                'password_hash' => Hash::make($userData['password']),
                'approval_pin' => Hash::make($userData['approval_pin']),
                'is_active' => true,
                'join_date' => Carbon::now()->subMonths(6),
            ]);
            $users[$userData['role_type']] = $user;
        }

        // 4. Seed Tables
        $tables = [];
        foreach ($data['tables'] as $tableData) {
            $table = Table::create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $primaryOutletId,
                'name' => $tableData['name'],
                'capacity' => $tableData['capacity'],
                'qr_code' => 'QR-' . strtoupper(Str::random(10)),
                'status' => 'available',
                'is_active' => true,
                'category' => $tableData['category'] ?? 'indoor',
            ]);
            $tables[] = $table;
        }

        // 5. Seed Categories
        $categoryIds = [];
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

        // 6. Seed Raw Materials
        $materialMap = [];
        foreach ($data['raw_materials'] as $matData) {
            $material = RawMaterial::create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $primaryOutletId,
                'name' => $matData['name'],
                'unit' => $matData['unit'],
                'quantity' => $matData['quantity'],
                'minimum_stock' => $matData['minimum_stock'],
                'cost_per_unit' => $matData['cost_per_unit'],
                'is_active' => true,
            ]);
            $materialMap[$matData['name']] = $material->id;
        }

        // 7. Seed Products & Ingredients
        $products = [];
        foreach ($data['products'] as $productData) {
            $productId = (string) Str::uuid();
            $product = Product::create([
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
            $products[] = $product;

            // Ingredients
            if (isset($productData['ingredients'])) {
                foreach ($productData['ingredients'] as $ingData) {
                    ProductIngredient::create([
                        'id' => (string) Str::uuid(),
                        'product_id' => $productId,
                        'raw_material_id' => $materialMap[$ingData['material_name']],
                        'quantity' => $ingData['quantity'],
                    ]);
                }
            }

            // Variants
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

            // Prices
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

            // Product Stock
            ProductStock::create([
                'id' => (string) Str::uuid(),
                'product_id' => $productId,
                'outlet_id' => $primaryOutletId,
                'current_stock' => rand(50, 100),
                'minimum_stock' => 10,
                'unit' => 'porsi',
            ]);
        }

        // 8. Seed Membership Tiers & Customers
        $tierIds = [];
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
                'is_active' => true,
            ]);
            $tierIds[$tierData['tier']] = $tierId;
        }

        $customerIds = [];
        foreach ($data['customers'] as $customerData) {
            $customerId = (string) Str::uuid();
            Customer::create([
                'id' => $customerId,
                'outlet_id' => $primaryOutletId,
                'name' => $customerData['name'],
                'phone' => $customerData['phone'],
                'email' => $customerData['email'] ?? null,
                'is_active' => true,
            ]);
            Membership::create([
                'id' => (string) Str::uuid(),
                'customer_id' => $customerId,
                'tier_id' => $tierIds[$customerData['tier']],
                'total_points' => $customerData['total_points'],
                'lifetime_points' => $customerData['lifetime_points'],
                'is_active' => true,
                'joined_at' => Carbon::now()->subMonths(3),
            ]);
            $customerIds[] = $customerId;
        }

        // 9. Promos
        $creatorId = $users['owner']->id;
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
                'start_date' => $promoData['start_date'],
                'end_date' => $promoData['end_date'] ?? null,
                'status' => $promoData['status'],
                'created_by' => $creatorId,
            ]);
        }

        // 10. Shift Templates
        $shiftTemplates = [];
        foreach ($data['shift_templates'] as $shiftData) {
            $template = ShiftTemplate::create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $primaryOutletId,
                'name' => $shiftData['name'],
                'start_time' => $shiftData['start_time'],
                'end_time' => $shiftData['end_time'],
                'is_active' => true,
            ]);
            $shiftTemplates[] = $template;
        }

        // --- PROGRAMMATIC DEMO DATA ---

        echo "Generating historical sales, attendance, and expenses...\n";

        $faker = \Faker\Factory::create('id_ID');
        $kasirUser = $users['kasir'];
        
        // 11. Historical Sales & Shifts (Last 30 Days)
        for ($i = 30; $i >= 1; $i--) {
            $date = Carbon::now()->subDays($i);
            
            // Per day 1 shift
            $shift = Shift::create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $primaryOutletId,
                'user_id' => $kasirUser->id,
                'shift_template_id' => $shiftTemplates[0]->id,
                'opened_by' => $kasirUser->id,
                'closed_by' => $kasirUser->id,
                'opened_at' => $date->copy()->setHour(8),
                'closed_at' => $date->copy()->setHour(16),
                'status' => 'closed',
                'opening_cash' => 500000,
                'actual_cash' => 0, // Will update after orders
            ]);

            $totalRevenue = 0;
            $totalOrders = rand(10, 25);
            $totalCash = 0;
            $totalQris = 0;

            $orderCountForDay = 1;
            for ($j = 0; $j < $totalOrders; $j++) {
                $orderTime = $date->copy()->setHour(rand(9, 15))->setMinute(rand(0, 59));
                
                $subtotal = 0;
                $orderItems = [];
                $numItems = rand(1, 3);
                for ($k = 0; $k < $numItems; $k++) {
                    $prod = $products[array_rand($products)];
                    $qty = rand(1, 2);
                    $price = $prod->base_price;
                    $subtotal += ($price * $qty);
                    $orderItems[] = [
                        'id' => (string) Str::uuid(),
                        'product_id' => $prod->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total_price' => $price * $qty
                    ];
                }

                $orderNumber = 'ORD-' . $date->format('Ymd') . '-' . str_pad((string) $orderCountForDay++, 4, '0', STR_PAD_LEFT);

                $order = Order::create([
                    'id' => (string) Str::uuid(),
                    'order_number' => $orderNumber,
                    'outlet_id' => $primaryOutletId,
                    'shift_id' => $shift->id,
                    'table_id' => $tables[array_rand($tables)]->id,
                    'cashier_id' => $kasirUser->id,
                    'source' => 'kasir',
                    'type' => 'dine_in',
                    'status' => 'completed',
                    'subtotal' => $subtotal,
                    'total_amount' => $subtotal,
                    'paid_amount' => $subtotal,
                    'created_at' => $orderTime,
                    'updated_at' => $orderTime,
                ]);

                foreach ($orderItems as $item) {
                    $order->items()->create($item);
                }

                $payMethod = rand(0, 1) ? 'cash' : 'qris';
                OrderPaymentLog::create([
                    'id' => (string) Str::uuid(),
                    'order_id' => $order->id,
                    'user_id' => $kasirUser->id,
                    'payment_type' => 'full',
                    'payment_method' => $payMethod,
                    'amount' => $subtotal,
                    'after_paid_amount' => $subtotal,
                    'created_at' => $orderTime,
                ]);

                if ($payMethod === 'cash') $totalCash += $subtotal;
                else $totalQris += $subtotal;
                $totalRevenue += $subtotal;
            }

            $shift->update([
                'expected_cash' => 500000 + $totalCash,
                'actual_cash' => 500000 + $totalCash,
            ]);

            ShiftCashReport::create([
                'id' => (string) Str::uuid(),
                'shift_id' => $shift->id,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'total_cash' => $totalCash,
                'total_qris' => $totalQris,
                'created_at' => $date->copy()->setHour(16),
            ]);

            // 12. Attendance for this day
            foreach ($users as $u) {
                Attendance::create([
                    'id' => (string) Str::uuid(),
                    'outlet_id' => $primaryOutletId,
                    'user_id' => $u->id,
                    'status' => 'present',
                    'date' => $date->toDateString(),
                    'clock_in' => $date->copy()->setHour(rand(7, 8))->setMinute(rand(0, 59)),
                    'clock_out' => $date->copy()->setHour(rand(16, 17))->setMinute(rand(0, 59)),
                    'created_at' => $date,
                ]);
            }

            // 13. Random Expenses
            if ($i % 5 === 0) {
                Expense::create([
                    'id' => (string) Str::uuid(),
                    'outlet_id' => $primaryOutletId,
                    'created_by' => $users['owner']->id,
                    'category' => 'Operasional',
                    'amount' => rand(50000, 200000),
                    'description' => $faker->sentence(3),
                    'expense_date' => $date->toDateString(),
                ]);
            }
        }

        // 14. Active Demo Data for Today
        echo "Setting up active shift and orders for today...\n";

        $todayShift = Shift::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $primaryOutletId,
            'user_id' => $kasirUser->id,
            'shift_template_id' => $shiftTemplates[0]->id,
            'opened_by' => $kasirUser->id,
            'opened_at' => Carbon::now()->setHour(8),
            'status' => 'active',
            'opening_cash' => 500000,
        ]);

        $dateNow = Carbon::now();
        $dateStr = $dateNow->format('Ymd');

        // Order 1: Table 02 (Pending Payment)
        $order1 = Order::create([
            'id' => (string) Str::uuid(),
            'order_number' => 'ORD-' . $dateStr . '-0001',
            'outlet_id' => $primaryOutletId,
            'shift_id' => $todayShift->id,
            'table_id' => $tables[1]->id, // Meja 02
            'cashier_id' => $kasirUser->id,
            'source' => 'kasir',
            'type' => 'dine_in',
            'status' => 'ready', // Already cooked, waiting payment
            'subtotal' => 83000,
            'total_amount' => 83000,
            'paid_amount' => 0,
            'created_at' => Carbon::now()->subMinutes(45),
        ]);
        $order1->items()->create(['id' => (string) Str::uuid(), 'product_id' => $products[0]->id, 'quantity' => 1, 'unit_price' => 45000, 'total_price' => 45000]);
        $order1->items()->create(['id' => (string) Str::uuid(), 'product_id' => $products[1]->id, 'quantity' => 1, 'unit_price' => 38000, 'total_price' => 38000]);
        $tables[1]->update(['status' => 'occupied']);

        // Order 2: Table 05 (In Progress / Cooking)
        $order2 = Order::create([
            'id' => (string) Str::uuid(),
            'order_number' => 'ORD-' . $dateStr . '-0002',
            'outlet_id' => $primaryOutletId,
            'shift_id' => $todayShift->id,
            'table_id' => $tables[4]->id, // Meja 05
            'cashier_id' => $kasirUser->id,
            'source' => 'qr_meja',
            'type' => 'dine_in',
            'status' => 'in_progress',
            'subtotal' => 22000,
            'total_amount' => 22000,
            'paid_amount' => 0,
            'created_at' => Carbon::now()->subMinutes(10),
            'cooking_started_at' => Carbon::now()->subMinutes(5),
        ]);
        $order2->items()->create(['id' => (string) Str::uuid(), 'product_id' => $products[4]->id, 'quantity' => 1, 'unit_price' => 22000, 'total_price' => 22000]);
        $tables[4]->update(['status' => 'occupied']);

        // Order 3: Table 07 (Waiting Bar Approval)
        $order3 = Order::create([
            'id' => (string) Str::uuid(),
            'order_number' => 'ORD-' . $dateStr . '-0003',
            'outlet_id' => $primaryOutletId,
            'shift_id' => $todayShift->id,
            'table_id' => $tables[6]->id, // Meja 07
            'cashier_id' => $kasirUser->id,
            'source' => 'kasir',
            'type' => 'dine_in',
            'status' => 'waiting_bar_approval',
            'subtotal' => 15000,
            'total_amount' => 15000,
            'paid_amount' => 15000, // Paid via QRIS before
            'created_at' => Carbon::now()->subMinutes(20),
        ]);
        $order3->items()->create(['id' => (string) Str::uuid(), 'product_id' => $products[7]->id, 'quantity' => 1, 'unit_price' => 15000, 'total_price' => 15000]);
        $tables[6]->update(['status' => 'occupied']);

        echo "Seeding completed successfully!\n";
    }
}
