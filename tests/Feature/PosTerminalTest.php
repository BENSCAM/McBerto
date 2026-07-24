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
            ->assertSet('showCheckout', false)
            ->assertSet('lastSaleReceipt.total', 3000)
            ->assertSet('lastSaleReceipt.payment_method', \App\Enums\PaymentMethod::Cash);

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

    public function test_receipt_can_be_dismissed_after_a_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);

        Livewire::actingAs($cashier)
            ->test(\App\Livewire\Pos\Terminal::class)
            ->call('addToCart', $product->id)
            ->call('completeSale', 'cash')
            ->assertSet('lastSaleReceipt.id', fn ($id) => $id !== null)
            ->call('closeReceipt')
            ->assertSet('lastSaleReceipt', null);
    }

    public function test_quantity_can_be_set_directly(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);

        Livewire::actingAs($cashier)
            ->test(\App\Livewire\Pos\Terminal::class)
            ->call('addToCart', $product->id)
            ->call('setQuantity', $product->id, '5')
            ->assertSet("cart.{$product->id}.quantity", 5)
            ->call('setQuantity', $product->id, '0')
            ->assertSet('cart', []);
    }

    public function test_cart_can_be_cleared(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        Livewire::actingAs($cashier)
            ->test(\App\Livewire\Pos\Terminal::class)
            ->call('addToCart', $product->id)
            ->call('clearCart')
            ->assertSet('cart', []);
    }
}
