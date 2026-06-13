<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use App\Models\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use Illuminate\Support\Str;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        // Ensure role is owner type
        $this->owner->role->update(['type' => 'owner']);
    }

    public function test_owner_can_create_outlet_with_tax_settings()
    {
        $response = $this->actingAs($this->owner)->post(route('settings.outlets.store'), [
            'name' => 'Outlet Baru',
            'address' => 'Jl. Test No. 123',
            'phone' => '081234567890',
            'tax_percentage' => 11,
            'tax_is_inclusive' => true,
            'workflow_statuses' => "pending\nin_progress\nready\ncompleted",
            'default_receipt_method' => 'print',
        ]);

        $response->assertRedirect(route('settings.outlets.index'));
        $this->assertDatabaseHas('outlets', ['name' => 'Outlet Baru']);
        
        $outlet = Outlet::where('name', 'Outlet Baru')->first();
        $this->assertEquals(11, $outlet->settings['tax_percentage']);
        $this->assertTrue($outlet->settings['tax_is_inclusive']);
    }

    public function test_owner_can_update_outlet_tax_settings()
    {
        $this->withoutExceptionHandling();
        $outlet = $this->owner->outlet;

        $response = $this->actingAs($this->owner)->patch(route('settings.outlets.update', $outlet), [
            'name' => 'Outlet Updated',
            'tax_percentage' => 10,
            'tax_is_inclusive' => false,
            'workflow_statuses' => "pending\nin_progress\nready\ncompleted",
            'default_receipt_method' => 'whatsapp',
        ]);

        $response->assertRedirect(route('settings.outlets.index'));
        $outlet->refresh();
        $this->assertEquals(10, $outlet->settings['tax_percentage']);
        $this->assertFalse($outlet->settings['tax_is_inclusive']);
    }

    public function test_owner_can_create_table()
    {
        $response = $this->actingAs($this->owner)->post(route('settings.tables.store'), [
            'name' => 'Meja T1',
            'capacity' => 4,
            'category' => 'indoor',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tables', [
            'name' => 'Meja T1',
            'capacity' => 4,
            'category' => 'indoor',
            'outlet_id' => $this->owner->outlet_id,
        ]);
    }

    public function test_owner_can_update_table()
    {
        $table = Table::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->owner->outlet_id,
            'name' => 'Meja A1',
            'capacity' => 2,
            'category' => 'indoor',
            'status' => 'available',
            'qr_code' => 'a1-1234',
            'qr_session_token' => (string) Str::ulid(),
        ]);

        $response = $this->actingAs($this->owner)->patch(route('settings.tables.update', $table), [
            'name' => 'Meja A1 Updated',
            'capacity' => 6,
            'category' => 'outdoor',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tables', [
            'id' => $table->id,
            'name' => 'Meja A1 Updated',
            'capacity' => 6,
            'category' => 'outdoor',
        ]);
    }

    public function test_owner_can_delete_table_softly()
    {
        $table = Table::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->owner->outlet_id,
            'name' => 'Meja To Delete',
            'capacity' => 2,
            'category' => 'indoor',
            'status' => 'available',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)->delete(route('settings.tables.destroy', $table));

        $response->assertRedirect();
        $this->assertDatabaseHas('tables', [
            'id' => $table->id,
            'is_active' => false,
        ]);
    }

    public function test_owner_can_create_product_with_variants_and_prices()
    {
        $category = \App\Models\Category::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->owner->outlet_id,
            'name' => 'Makanan',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Nasi Goreng Spesial',
            'base_price' => 25000,
            'is_available' => true,
            'is_active' => true,
            'track_stock' => false,
            'track_expired' => false,
            'variants' => [
                ['name' => 'Pedas', 'additional_price' => 0, 'is_active' => true],
                ['name' => 'Ekstra Telur', 'additional_price' => 5000, 'is_active' => true],
            ],
            'prices' => [
                ['tier' => 'normal', 'price' => 25000, 'is_active' => true],
                ['tier' => 'member', 'price' => 22000, 'is_active' => true],
            ],
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['name' => 'Nasi Goreng Spesial']);
        
        $product = \App\Models\Product::where('name', 'Nasi Goreng Spesial')->first();
        $this->assertCount(2, $product->variants);
        $this->assertCount(2, $product->prices);
    }

    public function test_owner_can_create_promo_template()
    {
        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->owner)->post(route('promos.store'), [
            'name' => 'Diskon Awal Bulan',
            'code' => 'AWALBULAN',
            'type' => 'percent',
            'apply_method' => 'manual',
            'discount_percent' => 10,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('promos.index'));
        $this->assertDatabaseHas('promos', ['name' => 'Diskon Awal Bulan', 'code' => 'AWALBULAN']);
    }

    public function test_owner_can_assign_role_to_employee()
    {
        $employee = User::factory()->create(['outlet_id' => $this->owner->outlet_id]);
        $newRole = Role::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $this->owner->outlet_id,
            'name' => 'Kasir Senior',
            'type' => 'kasir',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)->patch(route('settings.rbac.users.assign-role', $employee), [
            'role_id' => $newRole->id,
            'outlet_id' => $this->owner->outlet_id,
        ]);

        $response->assertRedirect();
        $employee->refresh();
        $this->assertEquals($newRole->id, $employee->role_id);
    }
}
