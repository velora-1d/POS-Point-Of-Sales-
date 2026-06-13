<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\Category;
use App\Models\Table;
use App\Models\User;
use App\Models\Order;
use App\Models\PaymentGatewayConfig;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $kasir;
    protected Outlet $outlet1;
    protected Outlet $outlet2;
    protected Product $product;
    protected Table $table;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->outlet1 = Outlet::create([
            'id' => (string) Str::uuid(),
            'name' => 'Outlet 1',
            'is_active' => true,
            'settings' => [
                'tax_percentage' => 10,
                'tax_is_inclusive' => false,
            ]
        ]);

        $this->outlet2 = Outlet::create([
            'id' => (string) Str::uuid(),
            'name' => 'Outlet 2',
            'is_active' => true,
            'settings' => [
                'tax_percentage' => 10,
                'tax_is_inclusive' => false,
            ]
        ]);

        $this->owner = User::factory()->create([
            'outlet_id' => $this->outlet1->id,
        ]);
        $this->owner->role->update(['type' => 'owner']);

        $this->kasir = User::factory()->create([
            'outlet_id' => $this->outlet1->id,
        ]);
        $this->kasir->role->update(['type' => 'kasir']);

        $category = Category::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet1->id,
            'name' => 'Makanan',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->outlet1->id,
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
            'outlet_id' => $this->outlet1->id,
            'name' => 'Meja 1',
            'capacity' => 4,
            'category' => 'indoor',
            'status' => 'available',
            'qr_code' => 'm1',
            'qr_session_token' => (string) Str::ulid(),
            'is_active' => true,
        ]);

        // Open shift for outlet 1
        $this->actingAs($this->kasir)->post(route('shifts.open'), [
            'opening_cash' => 100000,
        ]);
    }

    public function test_can_save_settings_payment_for_specific_outlet()
    {
        $response = $this->actingAs($this->owner)->put(route('settings.payment-gateway.update'), [
            'outlet_id' => $this->outlet1->id,
            'provider' => 'pakasir',
            'is_active' => true,
            'base_url' => 'https://api-test.pakasir.com',
            'project_slug' => 'test-outlet-1',
            'callback_url' => 'https://outlet1.com/webhook',
            'api_key' => 'key123',
            'api_secret' => 'secret123',
            'active_payment_methods' => ['qris', 'ewallet', 'debit', 'transfer'],
        ]);

        $response->assertRedirect(route('settings.payment-gateway.index', ['outlet_id' => $this->outlet1->id]));

        $this->assertDatabaseHas('payment_gateway_configs', [
            'outlet_id' => $this->outlet1->id,
            'provider' => 'pakasir',
            'is_active' => true,
            'base_url' => 'https://api-test.pakasir.com',
            'project_slug' => 'test-outlet-1',
        ]);
    }

    public function test_can_submit_order_with_ewallet_debit_transfer()
    {
        // Setup config override for outlet 1
        PaymentGatewayConfig::create([
            'outlet_id' => $this->outlet1->id,
            'provider' => 'pakasir',
            'is_active' => true,
            'base_url' => 'https://api.pakasir.com',
            'project_slug' => 'test-outlet-1',
            'api_key_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('key'),
            'api_secret_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('secret'),
            'active_payment_methods' => ['qris', 'ewallet', 'debit', 'transfer'],
        ]);

        foreach (['ewallet', 'debit', 'transfer'] as $method) {
            $response = $this->actingAs($this->kasir)->post(route('order.store'), [
                'order_type' => 'dine_in',
                'table_id' => $this->table->id,
                'payment_option' => 'pay_now',
                'payment_method' => $method,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'unit_price' => 20000,
                    ]
                ],
                'guests_count' => 1,
            ]);

            $response->assertRedirect(route('kasir.order'));
            
            // Check that the database record has the correct payment method in metadata JSON path
            $this->assertDatabaseHas('orders', [
                'outlet_id' => $this->outlet1->id,
                'metadata->payment->method' => $method,
            ]);
        }
    }

    public function test_payment_gateway_resolves_multi_outlet_config()
    {
        // Setup config override for outlet 1
        PaymentGatewayConfig::create([
            'outlet_id' => $this->outlet1->id,
            'provider' => 'pakasir',
            'is_active' => true,
            'base_url' => 'https://api.outlet1.com',
            'project_slug' => 'slug-outlet-1',
            'api_key_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('key1'),
            'api_secret_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('secret1'),
            'active_payment_methods' => ['qris', 'ewallet'],
        ]);

        // Setup config override for outlet 2
        PaymentGatewayConfig::create([
            'outlet_id' => $this->outlet2->id,
            'provider' => 'pakasir',
            'is_active' => true,
            'base_url' => 'https://api.outlet2.com',
            'project_slug' => 'slug-outlet-2',
            'api_key_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('key2'),
            'api_secret_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('secret2'),
            'active_payment_methods' => ['transfer', 'debit'],
        ]);

        $service = app(\App\Services\PaymentGatewayConfigService::class);

        $config1 = $service->resolvePakasirConfig($this->outlet1->id);
        $this->assertEquals('https://api.outlet1.com', $config1['base_url']);
        $this->assertEquals('slug-outlet-1', $config1['project_slug']);
        $this->assertEquals('key1', $config1['api_key']);
        $this->assertEquals(['qris', 'ewallet'], $config1['active_payment_methods']);

        $config2 = $service->resolvePakasirConfig($this->outlet2->id);
        $this->assertEquals('https://api.outlet2.com', $config2['base_url']);
        $this->assertEquals('slug-outlet-2', $config2['project_slug']);
        $this->assertEquals('key2', $config2['api_key']);
        $this->assertEquals(['transfer', 'debit'], $config2['active_payment_methods']);
    }

    public function test_unpaid_order_does_not_appear_on_kitchen_display()
    {
        // Setup config override for outlet 1
        PaymentGatewayConfig::create([
            'outlet_id' => $this->outlet1->id,
            'provider' => 'pakasir',
            'is_active' => true,
            'base_url' => 'https://api.pakasir.com',
            'project_slug' => 'test-outlet-1',
            'api_key_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('key'),
            'api_secret_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('secret'),
            'active_payment_methods' => ['qris'],
        ]);

        // Submit order with pay_now (before_kitchen qris)
        $this->actingAs($this->kasir)->post(route('order.store'), [
            'order_type' => 'dine_in',
            'table_id' => $this->table->id,
            'payment_option' => 'pay_now',
            'payment_method' => 'qris',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 20000,
                ]
            ],
            'guests_count' => 1,
        ]);

        $order = Order::latest()->first();
        $this->assertEquals('pending', $order->status);
        $this->assertEquals('pending', data_get($order->metadata, 'payment.status'));
        $this->assertEquals('before_kitchen', data_get($order->metadata, 'payment.context'));

        $repository = app(\App\Repositories\KitchenDisplayRepository::class);

        // Verify it is NOT on the kitchen display board
        $activeOrders = $repository->getActiveBoardOrders($this->outlet1->id);
        $this->assertFalse($activeOrders->contains('id', $order->id));

        // Simulate webhook payment success
        $webhookPayload = [
            'order_id' => $order->order_number,
            'amount' => 22000, // 20000 + 10% tax
            'payment_method' => 'qris',
            'status' => 'completed',
            'completed_at' => now()->toIso8601String(),
        ];

        // Mock Http for the webhook status check
        \Illuminate\Support\Facades\Http::fake([
            '*/api/transactiondetail*' => \Illuminate\Support\Facades\Http::response([
                'transaction' => [
                    'project' => config('services.pakasir.slug') ?: 'mentai-pos',
                    'amount' => 22000,
                    'status' => 'completed',
                    'payment_method' => 'qris',
                    'completed_at' => now()->toIso8601String(),
                ]
            ])
        ]);

        // Trigger payment process / webhook simulation
        app(\App\Services\OrderPaymentService::class)->handlePakasirWebhook($webhookPayload);

        $order->refresh();
        $this->assertEquals('paid', data_get($order->metadata, 'payment.status'));

        // Verify it is now visible on the kitchen display board!
        $activeOrders = $repository->getActiveBoardOrders($this->outlet1->id);
        $this->assertTrue($activeOrders->contains('id', $order->id));
    }
}
