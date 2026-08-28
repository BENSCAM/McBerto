<?php

namespace Tests\Feature;

use App\Enums\ServiceArea;
use App\Livewire\Pos\Terminal;
use App\Livewire\Reports\DailyReport;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\User;
use App\Services\RawMaterialStockService;
use Database\Seeders\McBertoInitialRawMaterialsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RawMaterialManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_a_raw_material(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test('raw-materials.index')
            ->set('name', 'Pain burger')
            ->set('unit', 'piece')
            ->set('current_quantity', '10')
            ->set('low_stock_threshold', '3')
            ->set('average_unit_cost', '125')
            ->call('save');

        $this->assertDatabaseHas('raw_materials', [
            'name' => 'Pain burger',
            'unit' => 'piece',
            'is_active' => true,
        ]);
    }

    public function test_purchase_increases_stock_and_recalculates_average_cost(): void
    {
        $manager = User::factory()->manager()->create();
        $material = RawMaterial::create([
            'name' => 'Steak',
            'unit' => 'piece',
            'current_quantity' => 10,
            'low_stock_threshold' => 2,
            'average_unit_cost' => 100,
        ]);

        app(RawMaterialStockService::class)->recordPurchase([
            'raw_material_id' => $material->id,
            'quantity' => 10,
            'total_price' => 3000,
            'purchase_date' => now()->toDateString(),
        ], $manager);

        $material->refresh();

        $this->assertSame(20.0, (float) $material->current_quantity);
        $this->assertSame(200.0, (float) $material->average_unit_cost);
        $this->assertDatabaseHas('raw_material_stock_movements', [
            'raw_material_id' => $material->id,
            'type' => 'purchase',
            'total_cost' => 3000,
        ]);
    }

    public function test_recipe_links_raw_material_to_product(): void
    {
        $product = Product::factory()->create(['price' => 1500]);
        $material = RawMaterial::create([
            'name' => 'Sauce',
            'unit' => 'ml',
            'current_quantity' => 1000,
            'low_stock_threshold' => 100,
            'average_unit_cost' => 2,
        ]);

        ProductRecipe::create([
            'product_id' => $product->id,
            'raw_material_id' => $material->id,
            'quantity' => 20,
        ]);

        $this->assertSame(40, $product->fresh()->load('recipes.rawMaterial')->materialCost());
    }

    public function test_recipe_product_selector_shows_service_area(): void
    {
        $manager = User::factory()->manager()->create();

        Product::factory()->create([
            'name' => 'Burger Standard',
            'service_area' => ServiceArea::Standard,
        ]);

        Product::factory()->create([
            'name' => 'Burger VIP',
            'service_area' => ServiceArea::Vip,
        ]);

        Livewire::actingAs($manager)
            ->test('product-recipes.index')
            ->assertSee('Burger Standard - Standard')
            ->assertSee('Burger VIP - VIP');
    }

    public function test_mounted_recipes_are_visible_on_recipe_page(): void
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create([
            'name' => 'Burger monté',
            'price' => 1500,
            'service_area' => ServiceArea::Vip,
        ]);
        $material = RawMaterial::create([
            'name' => 'Sauce maison',
            'unit' => 'ml',
            'current_quantity' => 1000,
            'low_stock_threshold' => 100,
            'average_unit_cost' => 2,
        ]);

        ProductRecipe::create([
            'product_id' => $product->id,
            'raw_material_id' => $material->id,
            'quantity' => 20,
        ]);

        Livewire::actingAs($manager)
            ->test('product-recipes.index')
            ->assertSee('Recettes déjà montées')
            ->assertSee('Burger monté')
            ->assertSee('VIP')
            ->assertSee('Sauce maison');
    }

    public function test_sale_deducts_raw_material_stock(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1500]);
        $material = RawMaterial::create([
            'name' => 'Pain',
            'unit' => 'piece',
            'current_quantity' => 10,
            'low_stock_threshold' => 2,
            'average_unit_cost' => 100,
        ]);
        ProductRecipe::create([
            'product_id' => $product->id,
            'raw_material_id' => $material->id,
            'quantity' => 1,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('completeClientSale', [
                ['product_id' => $product->id, 'quantity' => 3],
            ], 'cash', 5000, 500)
            ->assertSet('lastSaleReceipt.total', 4500);

        $this->assertSame(7.0, (float) $material->fresh()->current_quantity);
        $this->assertDatabaseHas('raw_material_stock_movements', [
            'raw_material_id' => $material->id,
            'type' => 'sale_consumption',
            'total_cost' => 300,
        ]);
    }

    public function test_report_uses_real_net_profit_formula(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1500]);
        $material = RawMaterial::create([
            'name' => 'Pain',
            'unit' => 'piece',
            'current_quantity' => 10,
            'low_stock_threshold' => 2,
            'average_unit_cost' => 100,
        ]);
        ProductRecipe::create([
            'product_id' => $product->id,
            'raw_material_id' => $material->id,
            'quantity' => 1,
        ]);
        Expense::factory()->create([
            'category' => 'charges',
            'expense_date' => now()->toDateString(),
            'amount' => 500,
        ]);

        Livewire::actingAs($owner)
            ->test(Terminal::class)
            ->call('completeClientSale', [
                ['product_id' => $product->id, 'quantity' => 2],
            ], 'cash', 3000, 0);

        Livewire::actingAs($owner)
            ->test(DailyReport::class)
            ->assertSet('revenue', 3000)
            ->assertSet('materialCost', 200)
            ->assertSet('grossMargin', 2800)
            ->assertSet('generalExpensesTotal', 500)
            ->assertSet('netProfit', 2300);
    }

    public function test_sale_is_rejected_when_raw_material_stock_is_insufficient(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['price' => 1500]);
        $material = RawMaterial::create([
            'name' => 'Emballage',
            'unit' => 'piece',
            'current_quantity' => 1,
            'low_stock_threshold' => 1,
            'average_unit_cost' => 50,
        ]);
        ProductRecipe::create([
            'product_id' => $product->id,
            'raw_material_id' => $material->id,
            'quantity' => 1,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('completeClientSale', [
                ['product_id' => $product->id, 'quantity' => 2],
            ], 'cash', 3000, 0)
            ->assertHasErrors(['stock']);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('raw_material_stock_movements', 0);
    }

    public function test_manager_can_suspend_and_reactivate_a_product_recipe(): void
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create(['name' => 'Burger recette']);
        $material = RawMaterial::create([
            'name' => 'Pain',
            'unit' => 'piece',
            'current_quantity' => 1,
            'low_stock_threshold' => 1,
            'average_unit_cost' => 100,
        ]);
        $recipe = ProductRecipe::create([
            'product_id' => $product->id,
            'raw_material_id' => $material->id,
            'quantity' => 1,
        ]);

        Livewire::actingAs($manager)
            ->test('product-recipes.index')
            ->call('toggleRecipe', $recipe->id)
            ->assertSee('Recette suspendue')
            ->assertSee('Suspendue');

        $this->assertFalse($recipe->fresh()->is_active);

        Livewire::actingAs($manager)
            ->test('product-recipes.index')
            ->call('toggleRecipe', $recipe->id)
            ->assertSee('Recette réactivée')
            ->assertSee('Active');

        $this->assertTrue($recipe->fresh()->is_active);
    }

    public function test_suspended_recipe_does_not_block_sale_when_raw_material_stock_is_insufficient(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['price' => 1500]);
        $material = RawMaterial::create([
            'name' => 'Emballage',
            'unit' => 'piece',
            'current_quantity' => 1,
            'low_stock_threshold' => 1,
            'average_unit_cost' => 50,
        ]);
        ProductRecipe::create([
            'product_id' => $product->id,
            'raw_material_id' => $material->id,
            'quantity' => 1,
            'is_active' => false,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('completeClientSale', [
                ['product_id' => $product->id, 'quantity' => 2],
            ], 'cash', 3000, 0)
            ->assertSet('lastSaleReceipt.total', 3000);

        $this->assertDatabaseHas('sales', [
            'total_amount' => 3000,
        ]);
        $this->assertSame(1.0, (float) $material->fresh()->current_quantity);
        $this->assertDatabaseCount('raw_material_stock_movements', 0);
    }

    public function test_manager_can_record_manual_stock_loss(): void
    {
        $manager = User::factory()->manager()->create();
        $material = RawMaterial::create([
            'name' => 'Emballage',
            'unit' => 'piece',
            'current_quantity' => 10,
            'low_stock_threshold' => 2,
            'average_unit_cost' => 50,
        ]);

        Livewire::actingAs($manager)
            ->test('stock-movements.index')
            ->set('adjust_raw_material_id', (string) $material->id)
            ->set('adjust_type', 'loss')
            ->set('adjust_quantity', '2')
            ->set('adjust_reason', 'Casse')
            ->call('recordAdjustment');

        $this->assertSame(8.0, (float) $material->fresh()->current_quantity);
        $this->assertDatabaseHas('raw_material_stock_movements', [
            'raw_material_id' => $material->id,
            'type' => 'loss',
            'reason' => 'Casse',
            'total_cost' => 100,
        ]);
    }

    public function test_initial_raw_materials_seeder_is_idempotent_and_separates_general_expenses(): void
    {
        User::factory()->owner()->create();

        $this->seed(McBertoInitialRawMaterialsSeeder::class);
        $this->seed(McBertoInitialRawMaterialsSeeder::class);

        $this->assertSame(17, RawMaterial::count());
        $this->assertSame(41400, (int) RawMaterialPurchase::sum('total_price'));
        $this->assertSame(5000, (int) Expense::whereIn('description', [
            'Insecticides',
            'Pelles à ordures x 2',
            'Détergent',
            'Transport achat matières premières',
        ])->sum('amount'));
    }
}
