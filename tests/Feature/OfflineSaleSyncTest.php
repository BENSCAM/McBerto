<?php

namespace Tests\Feature;

use App\Models\CashRegisterClosing;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertJsonPath('failed', []);

        $this->assertDatabaseHas('sales', [
            'offline_uuid' => 'offline-sale-1',
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

        $this->assertDatabaseMissing('sales', [
            'offline_uuid' => 'offline-sale-closed-day',
        ]);
    }
}
