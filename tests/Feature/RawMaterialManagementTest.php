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
use App\Models\RawMaterialStockMovement;
use App\Models\Sale;
use App\Models\User;
use App\Services\ChickenStockService;
use App\Services\RawMaterialStockService;
use Database\Seeders\ChickenStockConfigurationSeeder;
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

    public function test_chicken_batch_creates_twelve_breaded_pieces_and_fifteen_steaks_per_chicken(): void
    {
        $manager = User::factory()->manager()->create();

        app(ChickenStockService::class)->recordBatch([
            'chickens' => 2,
            'total_price' => 10_000,
            'supplier' => 'Marché central',
            'purchase_date' => now()->toDateString(),
        ], $manager);

        $this->assertSame(24.0, (float) RawMaterial::where('name', ChickenStockService::BREADED_PIECES_MATERIAL)->value('current_quantity'));
        $this->assertSame(30.0, (float) RawMaterial::where('name', ChickenStockService::CHICKEN_STEAKS_MATERIAL)->value('current_quantity'));
        $this->assertSame(10_000, (int) RawMaterialPurchase::sum('total_price'));
        $this->assertDatabaseCount('raw_material_purchases', 2);
        $this->assertDatabaseCount('raw_material_stock_movements', 2);
    }

    public function test_one_chicken_creates_exactly_twelve_breaded_pieces_and_fifteen_steaks(): void
    {
        $manager = User::factory()->manager()->create();

        app(ChickenStockService::class)->recordBatch([
            'chickens' => 1,
            'total_price' => 5500,
            'purchase_date' => now()->toDateString(),
        ], $manager);

        $this->assertSame(12.0, (float) RawMaterial::where('name', ChickenStockService::BREADED_PIECES_MATERIAL)->value('current_quantity'));
        $this->assertSame(15.0, (float) RawMaterial::where('name', ChickenStockService::CHICKEN_STEAKS_MATERIAL)->value('current_quantity'));
        $this->assertSame(2, RawMaterialStockMovement::where('type', 'chicken_supply')->count());
    }

    public function test_manager_can_record_a_chicken_batch_from_purchase_page(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test('raw-material-purchases.index')
            ->set('chicken_count', '1')
            ->set('chicken_total_price', '5000')
            ->set('chicken_purchase_date', now()->toDateString())
            ->call('recordChickenBatch')
            ->assertSee('12 morceaux')
            ->assertSee('15 steaks');

        $this->assertSame(12.0, (float) RawMaterial::where('name', ChickenStockService::BREADED_PIECES_MATERIAL)->value('current_quantity'));
        $this->assertSame(15.0, (float) RawMaterial::where('name', ChickenStockService::CHICKEN_STEAKS_MATERIAL)->value('current_quantity'));
    }

    public function test_chicken_configuration_assigns_the_expected_steaks_to_burger_recipes(): void
    {
        $category = Category::factory()->create();
        $oneSteak = Product::factory()->create(['category_id' => $category->id, 'name' => 'Berto Beef Chicken']);
        $twoSteaks = Product::factory()->create(['category_id' => $category->id, 'name' => 'Big Berto Chicken']);
        $doubleCheese = Product::factory()->create(['category_id' => $category->id, 'name' => 'Double Cheese Chicken']);
        $breadedChicken = Product::factory()->create(['category_id' => $category->id, 'name' => 'Poulet Pané (3 pièces)']);

        $this->seed(ChickenStockConfigurationSeeder::class);

        $steaks = RawMaterial::where('name', ChickenStockService::CHICKEN_STEAKS_MATERIAL)->firstOrFail();
        $breadedPieces = RawMaterial::where('name', ChickenStockService::BREADED_PIECES_MATERIAL)->firstOrFail();

        $this->assertDatabaseHas('product_recipes', ['product_id' => $oneSteak->id, 'raw_material_id' => $steaks->id, 'quantity' => 1]);
        $this->assertDatabaseHas('product_recipes', ['product_id' => $twoSteaks->id, 'raw_material_id' => $steaks->id, 'quantity' => 2]);
        $this->assertDatabaseHas('product_recipes', ['product_id' => $doubleCheese->id, 'raw_material_id' => $steaks->id, 'quantity' => 2]);
        $this->assertDatabaseHas('product_recipes', ['product_id' => $breadedChicken->id, 'raw_material_id' => $breadedPieces->id, 'quantity' => 3]);
    }

    public function test_chicken_configuration_is_idempotent_and_supports_both_double_cheese_spellings(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'name' => 'Double Chees Chicken']);
        Product::factory()->create(['category_id' => $category->id, 'name' => 'Menu Poulet Pané']);

        $this->seed(ChickenStockConfigurationSeeder::class);
        $this->seed(ChickenStockConfigurationSeeder::class);

        $this->assertSame(2, RawMaterial::whereIn('name', [
            ChickenStockService::BREADED_PIECES_MATERIAL,
            ChickenStockService::CHICKEN_STEAKS_MATERIAL,
        ])->count());
        $this->assertSame(2, ProductRecipe::count());
    }

    public function test_chicken_products_consume_one_steak_two_steaks_and_three_breaded_pieces(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $oneSteak = Product::factory()->create(['category_id' => $category->id, 'name' => 'Berto Beef Chicken', 'price' => 1500]);
        $twoSteaks = Product::factory()->create(['category_id' => $category->id, 'name' => 'Big Berto Chicken', 'price' => 2000]);
        $breaded = Product::factory()->create(['category_id' => $category->id, 'name' => 'Menu Poulet Pané', 'price' => 2500]);
        $this->seed(ChickenStockConfigurationSeeder::class);
        $manager = User::factory()->manager()->create();
        app(ChickenStockService::class)->recordBatch([
            'chickens' => 1,
            'total_price' => 5000,
            'purchase_date' => now()->toDateString(),
        ], $manager);

        Livewire::actingAs($cashier)->test(Terminal::class)->call('completeClientSale', [
            ['product_id' => $oneSteak->id, 'quantity' => 1],
            ['product_id' => $twoSteaks->id, 'quantity' => 1],
            ['product_id' => $breaded->id, 'quantity' => 1],
        ], 'cash', 6000, 0);

        $this->assertSame(12.0, (float) RawMaterial::where('name', ChickenStockService::CHICKEN_STEAKS_MATERIAL)->value('current_quantity'));
        $this->assertSame(9.0, (float) RawMaterial::where('name', ChickenStockService::BREADED_PIECES_MATERIAL)->value('current_quantity'));
        $this->assertDatabaseHas('raw_material_stock_movements', ['product_id' => $oneSteak->id, 'quantity_out' => 1]);
        $this->assertDatabaseHas('raw_material_stock_movements', ['product_id' => $twoSteaks->id, 'quantity_out' => 2]);
        $this->assertDatabaseHas('raw_material_stock_movements', ['product_id' => $breaded->id, 'quantity_out' => 3]);
    }

    public function test_canceling_a_chicken_product_sale_restores_consumed_stock_once(): void
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create(['name' => 'Big Berto Chicken', 'price' => 2000]);
        $this->seed(ChickenStockConfigurationSeeder::class);
        app(ChickenStockService::class)->recordBatch([
            'chickens' => 1,
            'total_price' => 5000,
            'purchase_date' => now()->toDateString(),
        ], $manager);

        $terminal = Livewire::actingAs($manager)->test(Terminal::class)
            ->call('completeClientSale', [['product_id' => $product->id, 'quantity' => 1]], 'cash', 2000, 0);
        $sale = Sale::latest('id')->firstOrFail();
        $terminal->call('cancelSale', $sale->id, 'Erreur de saisie');

        $steaks = RawMaterial::where('name', ChickenStockService::CHICKEN_STEAKS_MATERIAL)->firstOrFail();
        $this->assertSame(15.0, (float) $steaks->current_quantity);
        $this->assertDatabaseHas('raw_material_stock_movements', [
            'sale_id' => $sale->id,
            'type' => 'sale_cancellation',
            'quantity_in' => 2,
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

    public function test_manager_can_suspend_and_reactivate_a_mounted_product_recipe(): void
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create(['name' => 'Burger recette']);
        $firstMaterial = RawMaterial::create([
            'name' => 'Pain',
            'unit' => 'piece',
            'current_quantity' => 1,
            'low_stock_threshold' => 1,
            'average_unit_cost' => 100,
        ]);
        $secondMaterial = RawMaterial::create([
            'name' => 'Sauce',
            'unit' => 'ml',
            'current_quantity' => 10,
            'low_stock_threshold' => 1,
            'average_unit_cost' => 5,
        ]);
        $firstRecipe = ProductRecipe::create([
            'product_id' => $product->id,
            'raw_material_id' => $firstMaterial->id,
            'quantity' => 1,
        ]);
        $secondRecipe = ProductRecipe::create([
            'product_id' => $product->id,
            'raw_material_id' => $secondMaterial->id,
            'quantity' => 5,
        ]);

        Livewire::actingAs($manager)
            ->test('product-recipes.index')
            ->call('toggleProductRecipe', $product->id)
            ->assertSee('Recette suspendue')
            ->assertSee('Suspendue');

        $this->assertFalse($firstRecipe->fresh()->is_active);
        $this->assertFalse($secondRecipe->fresh()->is_active);

        Livewire::actingAs($manager)
            ->test('product-recipes.index')
            ->call('toggleProductRecipe', $product->id)
            ->assertSee('Recette réactivée')
            ->assertSee('Active');

        $this->assertTrue($firstRecipe->fresh()->is_active);
        $this->assertTrue($secondRecipe->fresh()->is_active);
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
