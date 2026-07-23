<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosTerminalTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_roles_can_access_the_pos_terminal(): void
    {
        foreach (['cashier', 'manager', 'owner'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/pos')->assertOk();
        }
    }

    public function test_cashier_can_complete_a_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1500, 'emoji' => '🍔']);

        Livewire::actingAs($cashier)
            ->test(\App\Livewire\Pos\Terminal::class)
            ->call('addToCart', $product->id)
            ->call('incrementQty', $product->id)
            ->assertSet('cart', [
                $product->id => ['name' => $product->name, 'emoji' => '🍔', 'price' => 1500, 'quantity' => 2],
            ])
            ->call('openCheckout')
            ->assertSet('showCheckout', true)
            ->call('completeSale', 'cash')
            ->assertSet('cart', [])
            ->assertSet('showCheckout', false);

        $this->assertDatabaseHas('sales', [
            'user_id' => $cashier->id,
            'payment_method' => 'cash',
            'total_amount' => 3000,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 3000,
        ]);
    }
}
