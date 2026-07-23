<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_access_products_page(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/products')->assertForbidden();
    }

    public function test_manager_can_create_a_product(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();

        Livewire::actingAs($manager)
            ->test('products.index')
            ->set('name', 'Cheeseburger')
            ->set('price', '1500')
            ->set('category_id', (string) $category->id)
            ->call('save');

        $this->assertDatabaseHas('products', [
            'name' => 'Cheeseburger',
            'price' => 1500,
            'category_id' => $category->id,
        ]);
    }

    public function test_manager_can_create_a_product_with_an_emoji(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();

        Livewire::actingAs($manager)
            ->test('products.index')
            ->set('name', 'Cheeseburger')
            ->set('emoji', '🍔')
            ->set('price', '1500')
            ->set('category_id', (string) $category->id)
            ->call('save');

        $this->assertDatabaseHas('products', [
            'name' => 'Cheeseburger',
            'emoji' => '🍔',
        ]);
    }

    public function test_emoji_is_optional(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();

        Livewire::actingAs($manager)
            ->test('products.index')
            ->set('name', 'Sans emoji')
            ->set('price', '1000')
            ->set('category_id', (string) $category->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Sans emoji',
            'emoji' => null,
        ]);
    }
}
