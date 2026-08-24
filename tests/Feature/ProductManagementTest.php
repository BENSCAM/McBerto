<?php

namespace Tests\Feature;

use App\Enums\ServiceArea;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
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
            'service_area' => 'standard',
        ]);
    }

    public function test_manager_can_create_a_vip_product(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();

        Livewire::actingAs($manager)
            ->test('products.index')
            ->set('name', 'Burger Signature VIP')
            ->set('price', '4200')
            ->set('service_area', 'vip')
            ->set('category_id', (string) $category->id)
            ->call('save');

        $this->assertDatabaseHas('products', [
            'name' => 'Burger Signature VIP',
            'price' => 4200,
            'category_id' => $category->id,
            'service_area' => 'vip',
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

    public function test_manager_can_update_a_product_including_vip_zone(): void
    {
        $manager = User::factory()->manager()->create();
        $oldCategory = Category::factory()->create();
        $newCategory = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $oldCategory->id,
            'name' => 'Ancien burger',
            'price' => 1500,
            'service_area' => 'standard',
        ]);

        Livewire::actingAs($manager)
            ->test('products.index')
            ->call('edit', $product->id)
            ->assertSet('editingId', $product->id)
            ->assertSet('service_area', 'standard')
            ->set('name', 'Burger Signature VIP')
            ->set('emoji', '🍔')
            ->set('price', '4200')
            ->set('service_area', 'vip')
            ->set('category_id', (string) $newCategory->id)
            ->call('save')
            ->assertSet('editingId', null);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Burger Signature VIP',
            'emoji' => '🍔',
            'price' => 4200,
            'category_id' => $newCategory->id,
            'service_area' => 'vip',
        ]);
    }

    public function test_manager_can_toggle_product_active_status(): void
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create(['is_active' => true]);

        Livewire::actingAs($manager)
            ->test('products.index')
            ->call('toggleActive', $product->id);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);

        Livewire::actingAs($manager)
            ->test('products.index')
            ->call('toggleActive', $product->id);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => true,
        ]);
    }

    public function test_manager_can_deactivate_all_products(): void
    {
        $manager = User::factory()->manager()->create();
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        Livewire::actingAs($manager)
            ->test('products.index')
            ->call('deactivateAll')
            ->assertSet('notice', '3 produit(s) désactivé(s).');

        $this->assertSame(0, Product::where('is_active', true)->count());
        $this->assertSame(4, Product::count());
    }

    public function test_manager_can_soft_delete_a_product(): void
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create();

        Livewire::actingAs($manager)
            ->test('products.index')
            ->call('delete', $product->id);

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_product_used_in_sales_is_deactivated_instead_of_deleted(): void
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create(['is_active' => true]);
        $sale = Sale::factory()->create(['user_id' => $manager->id]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        Livewire::actingAs($manager)
            ->test('products.index')
            ->call('delete', $product->id)
            ->assertSet('notice', 'Ce produit a déjà été vendu. Il a été désactivé pour conserver l’historique des ventes.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
            'deleted_at' => null,
        ]);
    }

    public function test_products_list_is_paginated(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();
        Product::factory()->count(15)->create(['category_id' => $category->id]);

        $paginator = Livewire::actingAs($manager)
            ->test('products.index')
            ->instance()
            ->products();

        $this->assertEquals(10, $paginator->count());
        $this->assertEquals(15, $paginator->total());
    }

    public function test_manager_can_filter_products_with_advanced_filters(): void
    {
        $manager = User::factory()->manager()->create();
        $burgers = Category::factory()->create(['name' => 'Burgers']);
        $drinks = Category::factory()->create(['name' => 'Boissons']);

        Product::factory()->create([
            'name' => 'Berto Beef',
            'category_id' => $burgers->id,
            'service_area' => ServiceArea::Standard,
            'price' => 2500,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Jus VIP',
            'category_id' => $drinks->id,
            'service_area' => ServiceArea::Vip,
            'price' => 1800,
            'is_active' => false,
        ]);

        Product::factory()->create([
            'name' => 'Mini Burger',
            'category_id' => $burgers->id,
            'service_area' => ServiceArea::Vip,
            'price' => 900,
            'is_active' => true,
        ]);

        Livewire::actingAs($manager)
            ->test('products.index')
            ->assertSee('Berto Beef')
            ->assertSee('Mini Burger')
            ->assertDontSee('Jus VIP')
            ->set('search', 'Berto')
            ->assertSee('Berto Beef')
            ->assertDontSee('Mini Burger')
            ->set('search', '')
            ->set('filterCategoryId', (string) $burgers->id)
            ->set('filterServiceArea', ServiceArea::Vip->value)
            ->assertSee('Mini Burger')
            ->assertDontSee('Berto Beef')
            ->set('filterServiceArea', '')
            ->set('minPrice', '1000')
            ->set('maxPrice', '2600')
            ->assertSee('Berto Beef')
            ->assertDontSee('Mini Burger')
            ->set('filterStatus', 'inactive')
            ->set('filterCategoryId', '')
            ->set('minPrice', '')
            ->set('maxPrice', '')
            ->assertSee('Jus VIP')
            ->assertDontSee('Berto Beef');
    }

    public function test_manager_can_reset_product_filters(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();

        Product::factory()->create([
            'name' => 'Produit actif',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Produit inactif',
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        Livewire::actingAs($manager)
            ->test('products.index')
            ->set('search', 'inactif')
            ->set('filterStatus', 'all')
            ->assertSee('Produit inactif')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('filterStatus', 'active')
            ->assertSet('sortBy', 'name_asc')
            ->assertSee('Produit actif')
            ->assertDontSee('Produit inactif');
    }
}
