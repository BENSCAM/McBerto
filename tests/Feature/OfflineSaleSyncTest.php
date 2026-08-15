<?php

namespace Tests\Feature;

use App\Models\CashRegisterClosing;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OfflineSaleSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_sync_an_offline_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Cheeseburger',
            'price' => 1500,
            'service_area' => 'standard',
        ]);

        $response = $this->actingAs($cashier)->postJson(route('pos.offline-sales.sync'), [
            'sales' => [[
                'offline_uuid' => 'offline-sale-1',
                'offline_reference' => 'OFF-20260805-0001',
                'created_at' => now()->toISOString(),
                'payment_method' => 'cash',
                'service_area' => 'standard',
                'total_amount' => 3000,
                'amount_given' => 5000,
                'change_due' => 2000,
                'items' => [[
                    'product_id' => $product->id,
                    'product_name' => 'Cheeseburger',
                    'unit_price' => 1500,
                    'quantity' => 2,
                    'subtotal' => 3000,
                ]],
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('synced.0.offline_uuid', 'offline-sale-1')
            ->assertJsonPath('failed', [])
            ->assertJsonStructure([
                'catalog' => [
                    'serviceAreas',
                    'paymentMethods',
                    'categories',
                ],
            ]);

        $this->assertDatabaseHas('sales', [
            'offline_uuid' => 'offline-sale-1',
            'offline_reference' => 'OFF-20260805-0001',
            'user_id' => $cashier->id,
            'payment_method' => 'cash',
            'service_area' => 'standard',
            'total_amount' => 3000,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 3000,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $cashier->id,
            'action' => 'offline_sync',
        ]);
    }

    public function test_offline_sync_keeps_offline_price_and_reports_warning_when_server_price_changed(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create([
            'name' => 'Burger Prix Ancien',
            'price' => 2000,
        ]);

        $response = $this->actingAs($cashier)->postJson(route('pos.offline-sales.sync'), [
            'sales' => [[
                'offline_uuid' => 'offline-sale-price-warning',
                'offline_reference' => 'OFF-20260805-0002',
                'created_at' => now()->toISOString(),
                'payment_method' => 'cash',
                'service_area' => 'standard',
                'total_amount' => 1500,
                'amount_given' => 2000,
                'change_due' => 500,
                'items' => [[
                    'product_id' => $product->id,
                    'product_name' => 'Burger Prix Ancien',
                    'unit_price' => 1500,
                    'quantity' => 1,
                    'subtotal' => 1500,
                ]],
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('synced.0.offline_reference', 'OFF-20260805-0002')
            ->assertJsonPath('warnings.0.offline_uuid', 'offline-sale-price-warning');

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'unit_price' => 1500,
            'subtotal' => 1500,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $cashier->id,
            'action' => 'offline_sync',
        ]);
    }

    public function test_offline_sync_does_not_crash_when_offline_reference_column_is_missing(): void
    {
        Schema::table('sales', function ($table) {
            $table->dropColumn('offline_reference');
        });

        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['price' => 2500]);

        $response = $this->actingAs($cashier)->postJson(route('pos.offline-sales.sync'), [
            'sales' => [[
                'offline_uuid' => 'offline-sale-without-column',
                'offline_reference' => 'OFF-20260814-0002',
                'created_at' => now()->toISOString(),
                'payment_method' => 'cash',
                'service_area' => 'standard',
                'total_amount' => 5000,
                'amount_given' => 5000,
                'change_due' => 0,
                'items' => [[
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => 2500,
                    'quantity' => 2,
                    'subtotal' => 5000,
                ]],
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('synced.0.offline_uuid', 'offline-sale-without-column')
            ->assertJsonPath('synced.0.offline_reference', 'OFF-20260814-0002');

        $this->assertDatabaseHas('sales', [
            'offline_uuid' => 'offline-sale-without-column',
            'total_amount' => 5000,
        ]);
    }

    public function test_offline_sync_is_idempotent(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['price' => 1000]);
        $payload = [
            'sales' => [[
                'offline_uuid' => 'offline-sale-duplicate',
                'created_at' => now()->toISOString(),
                'payment_method' => 'orange_money',
                'service_area' => 'standard',
                'total_amount' => 1000,
                'amount_given' => null,
                'change_due' => null,
                'items' => [[
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => 1000,
                    'quantity' => 1,
                    'subtotal' => 1000,
                ]],
            ]],
        ];

        $this->actingAs($cashier)->postJson(route('pos.offline-sales.sync'), $payload)->assertOk();
        $this->actingAs($cashier)->postJson(route('pos.offline-sales.sync'), $payload)->assertOk();

        $this->assertSame(1, Sale::where('offline_uuid', 'offline-sale-duplicate')->count());
    }

    public function test_offline_sale_is_rejected_when_register_is_closed_for_that_day(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['price' => 1000]);
        CashRegisterClosing::factory()->create(['closing_date' => now()->format('Y-m-d')]);

        $response = $this->actingAs($cashier)->postJson(route('pos.offline-sales.sync'), [
            'sales' => [[
                'offline_uuid' => 'offline-sale-closed-day',
                'created_at' => now()->toISOString(),
                'payment_method' => 'cash',
                'service_area' => 'standard',
                'total_amount' => 1000,
                'amount_given' => 1000,
                'change_due' => 0,
                'items' => [[
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => 1000,
                    'quantity' => 1,
                    'subtotal' => 1000,
                ]],
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('failed.0.offline_uuid', 'offline-sale-closed-day');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $cashier->id,
            'action' => 'offline_sync_partial',
        ]);

        $this->assertDatabaseMissing('sales', [
            'offline_uuid' => 'offline-sale-closed-day',
        ]);
    }

    public function test_offline_sale_is_rejected_when_total_is_incoherent(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['price' => 1000]);

        $response = $this->actingAs($cashier)->postJson(route('pos.offline-sales.sync'), [
            'sales' => [[
                'offline_uuid' => 'offline-sale-bad-total',
                'created_at' => now()->toISOString(),
                'payment_method' => 'cash',
                'service_area' => 'standard',
                'total_amount' => 2000,
                'amount_given' => 2000,
                'change_due' => 0,
                'items' => [[
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => 1000,
                    'quantity' => 1,
                    'subtotal' => 1000,
                ]],
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('failed.0.offline_uuid', 'offline-sale-bad-total');

        $this->assertDatabaseMissing('sales', [
            'offline_uuid' => 'offline-sale-bad-total',
        ]);
    }
}
