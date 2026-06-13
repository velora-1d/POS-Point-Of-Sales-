<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\Category;
use App\Models\Table;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class CoreTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;
    protected Outlet $outlet;
    protected Product $product;
    protected Table $table;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->kasir = User::factory()->create();
        $this->outlet = $this->kasir->outlet;
        
        $this->outlet->update([
            'settings' => [
                'tax_percentage' => 10,
                'tax_is_inclusive' => false,
            ]
        ]);

        $this->kasir->role->update(['type' => 'kasir']);

        $category = Category::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet->id,
            'name' => 'Makanan',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet->id,
            'category_id' => $category->id,
            'name' => 'Ayam Goreng',
            'base_price' => 20000,
            'is_available' => true,
            'is_active' => true,
            'track_stock' => false,
            'track_expired' => false,
        ]);

        $this->table = Table::create([
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

        // Open a shift
        $this->actingAs($this->kasir)->post(route('shifts.open'), [
            'opening_cash' => 100000,
        ]);
    }

    public function test_kasir_can_create_order_dine_in_pay_later()
    {
        $response = $this->actingAs($this->kasir)->post(route('order.store'), [
            'order_type' => 'dine_in',
            'table_id' => $this->table->id,
            'payment_option' => 'pay_later',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 20000,
                ]
            ],
            'guests_count' => 2,
        ]);

        $response->assertRedirect(route('kasir.order'));
        $this->assertDatabaseHas('orders', [
            'outlet_id' => $this->outlet->id,
            'table_id' => $this->table->id,
            'status' => 'pending',
            'total_amount' => 44000, // (20000 * 2) + 10% tax
            'pay_later' => true,
        ]);

        $this->table->refresh();
        $this->assertEquals('occupied', $this->table->status);
    }

    public function test_kasir_can_process_payment_cash()
    {
        $this->withoutExceptionHandling();
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet->id,
            'shift_id' => Shift::first()->id,
            'table_id' => $this->table->id,
            'cashier_id' => $this->kasir->id,
            'subtotal' => 40000,
            'tax_amount' => 4000,
            'total_amount' => 44000,
            'paid_amount' => 0,
            'status' => 'delivered',
            'source' => 'kasir',
            'type' => 'dine_in',
            'pay_later' => true,
            'metadata' => ['payment' => ['status' => 'unpaid', 'context' => 'after_service']]
        ]);

        $order->items()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 20000,
            'total_price' => 40000,
        ]);

        $response = $this->actingAs($this->kasir)->post(route('order.pay', $order), [
            'payment_method' => 'cash',
            'cash_received' => 50000,
        ]);

        $response->assertRedirect(route('kasir.order'));
        $order->refresh();
        $this->assertEquals('completed', $order->status); // Status will be ready or completed depending on if it's already delivered
        // Wait, if it's pending it might go to ready/completed if marked as delivered.
        // In OrderPaymentService: $status = $context === 'before_kitchen' ? 'pending' : 'completed';
        // Our context is 'after_service', so it should be 'completed'.
        
        $this->assertEquals(44000, $order->paid_amount);
        $this->assertEquals('paid', $order->metadata['payment']['status']);
    }

    public function test_kasir_can_create_order_dine_in_pay_now_cash()
    {
        $response = $this->actingAs($this->kasir)->post(route('order.store'), [
            'order_type' => 'dine_in',
            'table_id' => $this->table->id,
            'payment_option' => 'pay_now',
            'payment_method' => 'cash',
            'cash_received' => 100000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 20000,
                ]
            ],
        ]);

        $response->assertRedirect(route('kasir.order'));
        $this->assertDatabaseHas('orders', [
            'total_amount' => 22000,
            'paid_amount' => 22000,
            'pay_later' => false,
            'status' => 'pending', // Paid before kitchen, status remains pending to be processed by kitchen
        ]);
    }

    public function test_kasir_can_split_bill()
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet->id,
            'shift_id' => Shift::first()->id,
            'table_id' => $this->table->id,
            'cashier_id' => $this->kasir->id,
            'subtotal' => 40000,
            'total_amount' => 44000,
            'status' => 'pending',
            'source' => 'kasir',
            'type' => 'dine_in',
            'pay_later' => true,
        ]);

        $item1 = $order->items()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 20000,
            'total_price' => 20000,
        ]);

        $item2 = $order->items()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 20000,
            'total_price' => 20000,
        ]);

        $response = $this->actingAs($this->kasir)->post(route('order.split-bill', $order), [
            'item_splits' => [
                [
                    'order_item_id' => $item2->id,
                    'quantity' => 1,
                ]
            ]
        ]);

        $response->assertRedirect(route('kasir.order'));
        
        $this->assertDatabaseCount('orders', 2);
        
        $order->refresh();
        $this->assertEquals(22000, $order->total_amount);
        $this->assertCount(1, $order->items);

        $newOrder = Order::where('id', '!=', $order->id)->first();
        $this->assertEquals(22000, $newOrder->total_amount);
        $this->assertCount(1, $newOrder->items);
    }

    public function test_kasir_can_merge_bills()
    {
        $order1 = Order::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet->id,
            'shift_id' => Shift::first()->id,
            'table_id' => $this->table->id,
            'cashier_id' => $this->kasir->id,
            'subtotal' => 20000,
            'total_amount' => 22000,
            'status' => 'pending',
            'source' => 'kasir',
            'type' => 'dine_in',
            'pay_later' => true,
        ]);
        $order1->items()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 20000,
            'total_price' => 20000,
        ]);

        $order2 = Order::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet->id,
            'shift_id' => Shift::first()->id,
            'table_id' => $this->table->id,
            'cashier_id' => $this->kasir->id,
            'subtotal' => 20000,
            'total_amount' => 22000,
            'status' => 'pending',
            'source' => 'kasir',
            'type' => 'dine_in',
            'pay_later' => true,
        ]);
        $order2->items()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 20000,
            'total_price' => 20000,
        ]);

        $response = $this->actingAs($this->kasir)->post(route('orders.merge-bills'), [
            'order_ids' => [$order1->id, $order2->id],
        ]);

        $response->assertRedirect(route('kasir.order'));
        
        $order1->refresh();
        $order2->refresh();
        $this->assertEquals('cancelled', $order1->status);
        $this->assertEquals('cancelled', $order2->status);

        $this->assertDatabaseHas('orders', [
            'status' => 'pending',
            'total_amount' => 44000,
            'table_id' => $this->table->id,
        ]);
    }

    public function test_customer_can_order_via_qr()
    {
        $response = $this->post(route('self-service.checkout', $this->table->qr_session_token), [
            'customer_name' => 'Budi QR',
            'customer_phone' => '08999999999',
            'guests_count' => 2,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 20000,
                ]
            ],
        ]);

        $order = Order::where('source', 'qr_meja')->first();
        $response->assertRedirect(route('self-service.orders.status', [
            'tableToken' => $this->table->qr_session_token,
            'orderNumber' => $order->order_number,
        ]));

        $this->assertDatabaseHas('orders', [
            'source' => 'qr_meja',
            'total_amount' => 22000,
            'paid_amount' => 0,
        ]);
        
        $this->assertEquals('pending', $order->metadata['payment']['status']);
        $this->assertEquals('before_kitchen', $order->metadata['payment']['context']);
    }
}
