<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\Category;
use App\Models\RawMaterial;
use App\Models\ProductStock;
use App\Models\ProductIngredient;
use App\Models\User;
use App\Models\Order;
use App\Models\Table;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class InventoryHppTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $kasir;
    protected Outlet $outlet;
    protected Product $product;
    protected RawMaterial $rawMaterial1;
    protected RawMaterial $rawMaterial2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->owner = User::factory()->create();
        $this->outlet = $this->owner->outlet;

        $this->owner->role->update(['type' => 'owner']);

        $this->kasir = User::factory()->create([
            'outlet_id' => $this->outlet->id,
        ]);
        $this->kasir->role->update(['type' => 'kasir']);

        $category = Category::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet->id,
            'name' => 'Minuman',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet->id,
            'category_id' => $category->id,
            'name' => 'Kopi Susu',
            'base_price' => 15000,
            'is_available' => true,
            'is_active' => true,
            'track_stock' => false,
            'track_expired' => false,
        ]);

        $this->rawMaterial1 = RawMaterial::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet->id,
            'name' => 'Biji Kopi',
            'unit' => 'gram',
            'cost_per_unit' => 200,
            'quantity' => 1000,
            'minimum_stock' => 100,
        ]);

        $this->rawMaterial2 = RawMaterial::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet->id,
            'name' => 'Susu Segar',
            'unit' => 'ml',
            'cost_per_unit' => 30,
            'quantity' => 2000,
            'minimum_stock' => 200,
        ]);
    }

    public function test_owner_can_configure_product_hpp_recipe()
    {
        $response = $this->actingAs($this->owner)->put(route('products.ingredients.update', $this->product), [
            'ingredients' => [
                [
                    'raw_material_id' => $this->rawMaterial1->id,
                    'quantity' => 15, // 15 gram * 200 = 3000
                ],
                [
                    'raw_material_id' => $this->rawMaterial2->id,
                    'quantity' => 150, // 150 ml * 30 = 4500
                ]
            ],
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('product_ingredients', [
            'product_id' => $this->product->id,
            'raw_material_id' => $this->rawMaterial1->id,
            'quantity' => 15,
        ]);

        $this->assertDatabaseHas('product_ingredients', [
            'product_id' => $this->product->id,
            'raw_material_id' => $this->rawMaterial2->id,
            'quantity' => 150,
        ]);

        $this->product->refresh();
        // HPP = 3000 + 4500 = 7500
        $this->assertEquals(7500, $this->product->hpp);
    }

    public function test_transaction_deducts_raw_material_stock()
    {
        // Configure recipe
        ProductIngredient::create([
            'id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'raw_material_id' => $this->rawMaterial1->id,
            'quantity' => 15,
        ]);
        ProductIngredient::create([
            'id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'raw_material_id' => $this->rawMaterial2->id,
            'quantity' => 150,
        ]);

        $table = Table::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet->id,
            'name' => 'Meja 1',
            'capacity' => 4,
            'category' => 'indoor',
            'status' => 'available',
            'qr_code' => 'm1',
            'qr_session_token' => (string) Str::ulid(),
            'is_active' => true,
        ]);

        $this->actingAs($this->kasir)->post(route('shifts.open'), [
            'opening_cash' => 100000,
        ]);

        $response = $this->actingAs($this->kasir)->post(route('order.store'), [
            'order_type' => 'dine_in',
            'table_id' => $table->id,
            'payment_option' => 'pay_now',
            'payment_method' => 'cash',
            'cash_received' => 50000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 15000,
                ]
            ],
            'guests_count' => 1,
        ]);

        $response->assertRedirect(route('kasir.order'));

        // Move order through the actual kitchen flow until it is served.
        $order = Order::first();
        $this->actingAs($this->kasir)->post(route('kitchen.orders.update-status', $order), [
            'action' => 'start_cooking',
        ])->assertRedirect();

        $order->refresh();
        $this->assertEquals('in_progress', $order->status);

        $this->actingAs($this->kasir)->post(route('kitchen.orders.update-status', $order), [
            'action' => 'finish_cooking',
        ])->assertRedirect();

        $order->refresh();
        $this->assertEquals('waiting_bar_approval', $order->status);

        $this->actingAs($this->kasir)->post(route('bar.orders.approve', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertEquals('ready', $order->status);

        $this->actingAs($this->kasir)->post(route('order.deliver', $order))
            ->assertRedirect(route('kasir.order'));

        $this->rawMaterial1->refresh();
        $this->rawMaterial2->refresh();

        // Used: 2 * 15 = 30. Original: 1000. Expected: 970
        $this->assertEquals(970, $this->rawMaterial1->quantity);
        // Used: 2 * 150 = 300. Original: 2000. Expected: 1700
        $this->assertEquals(1700, $this->rawMaterial2->quantity);
    }

    public function test_owner_can_add_product_stock_directly()
    {
        $this->withoutExceptionHandling();
        $this->product->update(['track_stock' => true]);

        ProductStock::create([
            'id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'outlet_id' => $this->outlet->id,
            'current_stock' => 10,
        ]);

        $response = $this->actingAs($this->owner)->patch(route('products.stock.update', $this->product), [
            'current_stock' => 15,
            'minimum_stock' => 5,
            'unit' => 'pcs',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'current_stock' => 15,
        ]);
    }

    public function test_owner_can_update_product_stock_without_unit_field()
    {
        $this->withoutExceptionHandling();
        $this->product->update(['track_stock' => true]);

        ProductStock::create([
            'id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'outlet_id' => $this->outlet->id,
            'current_stock' => 10,
            'minimum_stock' => 3,
            'unit' => 'botol',
        ]);

        $response = $this->actingAs($this->owner)->patch(route('products.stock.update', $this->product), [
            'current_stock' => 15,
            'minimum_stock' => 5,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'current_stock' => 15,
            'minimum_stock' => 5,
            'unit' => 'botol',
        ]);
    }

    public function test_owner_can_restock_expired_product_without_batch_code()
    {
        $this->withoutExceptionHandling();
        $this->product->update([
            'track_stock' => true,
            'track_expired' => true,
        ]);

        ProductStock::create([
            'id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'outlet_id' => $this->outlet->id,
            'current_stock' => 10,
            'minimum_stock' => 3,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($this->owner)->patch(route('products.stock.update', $this->product), [
            'current_stock' => 15,
            'minimum_stock' => 5,
            'unit' => 'pcs',
            'expired_date' => '2026-12-31',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('inventory_expiries', [
            'outlet_id' => $this->outlet->id,
            'trackable_type' => 'product',
            'trackable_id' => $this->product->id,
            'quantity' => 5,
            'batch_code' => null,
        ]);
    }
}
