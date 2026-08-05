<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ServiceArea;
use App\Livewire\Pos\Terminal;
use App\Models\CashRegisterClosing;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
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
            ->test(Terminal::class)
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
            ->assertSet('lastSaleReceipt.payment_method', PaymentMethod::Cash);

        $this->assertDatabaseHas('sales', [
            'user_id' => $cashier->id,
            'payment_method' => 'cash',
            'sale_status' => 'completed',
            'total_amount' => 3000,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 3000,
        ]);
    }

    public function test_cashier_can_complete_a_client_side_cart_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1500]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('completeClientSale', [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1],
            ], 'cash', 5000, 500)
            ->assertSet('lastSaleReceipt.total', 4500)
            ->assertSet('lastSaleReceipt.amount_given', 5000)
            ->assertSet('lastSaleReceipt.change_due', 500);

        $this->assertDatabaseHas('sales', [
            'user_id' => $cashier->id,
            'payment_method' => 'cash',
            'total_amount' => 4500,
            'amount_given' => 5000,
            'change_due' => 500,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'unit_price' => 1500,
            'quantity' => 3,
            'subtotal' => 4500,
        ]);
    }

    public function test_cashier_can_complete_a_client_side_vip_cart_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 4200,
            'service_area' => ServiceArea::Vip,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('completeClientSale', [
                ['product_id' => $product->id, 'quantity' => 2],
            ], 'cash', 10000, 1600, 'vip')
            ->assertSet('lastSaleReceipt.total', 8400)
            ->assertSet('lastSaleReceipt.service_area', ServiceArea::Vip);

        $this->assertDatabaseHas('sales', [
            'user_id' => $cashier->id,
            'service_area' => 'vip',
            'total_amount' => 8400,
            'amount_given' => 10000,
            'change_due' => 1600,
        ]);
    }

    public function test_receipt_can_be_dismissed_after_a_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('completeSale', 'cash')
            ->assertSet('lastSaleReceipt.id', fn ($id) => $id !== null)
            ->call('closeReceipt')
            ->assertSet('lastSaleReceipt', null);
    }

    public function test_sale_receipt_number_is_generated(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('completeSale', 'cash')
            ->assertSet('lastSaleReceipt.receipt_number', 'MCB-'.now()->format('Ymd').'-0001');

        $this->assertDatabaseHas('sales', [
            'receipt_number' => 'MCB-'.now()->format('Ymd').'-0001',
        ]);
    }

    public function test_quantity_can_be_set_directly(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
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
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('clearCart')
            ->assertSet('cart', []);
    }

    public function test_search_finds_products_across_categories(): void
    {
        $cashier = User::factory()->cashier()->create();
        $burgers = Category::factory()->create(['name' => 'Burgers']);
        $drinks = Category::factory()->create(['name' => 'Boissons']);
        Product::factory()->create(['category_id' => $burgers->id, 'name' => 'Cheeseburger']);
        $cola = Product::factory()->create(['category_id' => $drinks->id, 'name' => 'Coca-Cola']);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('search', 'cola');

        $visible = $component->instance()->visibleProducts();

        $this->assertCount(1, $visible);
        $this->assertSame($cola->id, $visible->first()->id);
    }

    public function test_pos_filters_products_by_selected_service_area(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $standardProduct = Product::factory()->create(['category_id' => $category->id, 'name' => 'Cheeseburger', 'service_area' => 'standard']);
        $vipProduct = Product::factory()->create(['category_id' => $category->id, 'name' => 'Burger Signature VIP', 'service_area' => 'vip']);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class);

        $standardVisible = $component->instance()->visibleProducts();
        $this->assertTrue($standardVisible->contains('id', $standardProduct->id));
        $this->assertFalse($standardVisible->contains('id', $vipProduct->id));

        $component->call('selectServiceArea', 'vip');

        $vipVisible = $component->instance()->visibleProducts();
        $this->assertFalse($vipVisible->contains('id', $standardProduct->id));
        $this->assertTrue($vipVisible->contains('id', $vipProduct->id));
    }

    public function test_offline_catalog_excludes_inactive_products(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $activeProduct = Product::factory()->create(['category_id' => $category->id, 'name' => 'Produit actif', 'is_active' => true]);
        $inactiveProduct = Product::factory()->create(['category_id' => $category->id, 'name' => 'Produit inactif', 'is_active' => false]);

        $catalog = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->instance()
            ->offlineCatalog();

        $productIds = collect($catalog['categories'])
            ->flatMap(fn (array $category) => $category['products'])
            ->pluck('id');

        $this->assertTrue($productIds->contains($activeProduct->id));
        $this->assertFalse($productIds->contains($inactiveProduct->id));
    }

    public function test_cashier_can_complete_a_vip_sale_with_vip_product_price(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Burger Signature VIP',
            'price' => 4200,
            'service_area' => 'vip',
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('selectServiceArea', 'vip')
            ->call('addToCart', $product->id)
            ->call('completeSale', 'cash')
            ->assertSet('lastSaleReceipt.total', 4200)
            ->assertSet('lastSaleReceipt.service_area', ServiceArea::Vip);

        $this->assertDatabaseHas('sales', [
            'user_id' => $cashier->id,
            'service_area' => 'vip',
            'total_amount' => 4200,
        ]);
    }

    public function test_switching_service_area_clears_the_cart(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'service_area' => 'standard']);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('selectServiceArea', 'vip')
            ->assertSet('cart', []);
    }

    public function test_clearing_search_restores_category_browsing(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('search', 'xyz')
            ->assertSet('activeCategoryId', null)
            ->set('search', '')
            ->assertSet('activeCategoryId', $category->id);
    }

    public function test_selecting_cash_shows_change_due_screen_instead_of_completing_immediately(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1500]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('openCheckout')
            ->call('selectPaymentMethod', 'cash')
            ->assertSet('checkoutMethod', 'cash')
            ->assertSet('cart', [
                $product->id => ['name' => $product->name, 'emoji' => null, 'price' => 1500, 'quantity' => 1],
            ]);

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_change_due_is_computed_and_stored_on_the_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1500]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('openCheckout')
            ->call('selectPaymentMethod', 'cash')
            ->set('amountGiven', '2000')
            ->assertSet('changeDue', 500)
            ->call('confirmCashSale')
            ->assertSet('lastSaleReceipt.amount_given', 2000)
            ->assertSet('lastSaleReceipt.change_due', 500);

        $this->assertDatabaseHas('sales', [
            'total_amount' => 1500,
            'amount_given' => 2000,
            'change_due' => 500,
        ]);
    }

    public function test_cash_sale_cannot_be_confirmed_with_insufficient_amount(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1500]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('openCheckout')
            ->call('selectPaymentMethod', 'cash')
            ->set('amountGiven', '1000')
            ->assertSet('changeDue', -500)
            ->call('confirmCashSale')
            ->assertSet('lastSaleReceipt', null);

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_quick_note_buttons_set_the_amount_given(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1500]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('openCheckout')
            ->call('selectPaymentMethod', 'cash')
            ->call('setAmountGiven', 2000)
            ->assertSet('amountGiven', '2000')
            ->assertSet('changeDue', 500);
    }

    public function test_non_cash_payment_methods_skip_the_change_due_screen(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1500]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('openCheckout')
            ->call('selectPaymentMethod', 'orange_money')
            ->assertSet('cart', [])
            ->assertSet('lastSaleReceipt.amount_given', null);

        $this->assertDatabaseHas('sales', [
            'payment_method' => 'orange_money',
            'amount_given' => null,
        ]);
    }

    public function test_recent_sales_shows_todays_sales(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 2000]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('completeSale', 'cash');

        $recent = $component->instance()->recentSales();

        $this->assertCount(1, $recent);
        $this->assertEquals(2000, $recent->first()->total_amount);
    }

    public function test_recent_sales_prioritizes_latest_recorded_sale_even_with_future_demo_times(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 2000]);

        Sale::factory()->count(8)->create([
            'user_id' => $cashier->id,
            'created_at' => now()->setTime(21, 0),
            'total_amount' => 999,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('completeSale', 'cash');

        $recent = $component->instance()->recentSales();

        $this->assertEquals(2000, $recent->first()->total_amount);
        $this->assertSame($component->get('lastSaleReceipt.receipt_number'), $recent->first()->receipt_number);
    }

    public function test_cashier_can_cancel_own_unclosed_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $sale = Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => Sale::nextReceiptNumber(),
            'total_amount' => 2000,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('cancelSale', $sale->id);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'sale_status' => SaleStatus::Canceled->value,
            'canceled_by' => $cashier->id,
            'cancellation_reason' => 'Annulation depuis la caisse',
        ]);
    }

    public function test_cashier_cannot_cancel_another_cashiers_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $otherCashier = User::factory()->cashier()->create();
        $sale = Sale::factory()->create([
            'user_id' => $otherCashier->id,
            'receipt_number' => Sale::nextReceiptNumber(),
            'total_amount' => 2000,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('cancelSale', $sale->id);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'sale_status' => SaleStatus::Completed->value,
            'canceled_by' => null,
        ]);
    }

    public function test_manager_can_cancel_any_unclosed_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $manager = User::factory()->manager()->create();
        $sale = Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => Sale::nextReceiptNumber(),
            'total_amount' => 2000,
        ]);

        Livewire::actingAs($manager)
            ->test(Terminal::class)
            ->call('cancelSale', $sale->id);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'sale_status' => SaleStatus::Canceled->value,
            'canceled_by' => $manager->id,
        ]);
    }

    public function test_cashier_cannot_add_to_cart_when_register_is_closed_for_today(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);
        CashRegisterClosing::factory()->create(['closing_date' => now()->format('Y-m-d')]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->assertSet('cart', []);
    }

    public function test_cashier_cannot_complete_sale_when_register_is_closed_for_today(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id);

        CashRegisterClosing::factory()->create(['closing_date' => now()->format('Y-m-d')]);

        $component->call('completeSale', 'cash');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_cashier_cannot_reopen_the_register(): void
    {
        $cashier = User::factory()->cashier()->create();
        CashRegisterClosing::factory()->create(['closing_date' => now()->format('Y-m-d')]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('reopenRegister')
            ->assertSet('todayClosing.id', fn ($id) => $id !== null);

        $this->assertDatabaseCount('cash_register_closings', 1);
    }

    public function test_manager_can_reopen_the_register_from_the_pos_screen(): void
    {
        $manager = User::factory()->manager()->create();
        $closing = CashRegisterClosing::factory()->create(['closing_date' => now()->format('Y-m-d')]);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);

        Livewire::actingAs($manager)
            ->test(Terminal::class)
            ->assertSet('todayClosing.id', $closing->id)
            ->call('reopenRegister')
            ->assertSet('todayClosing', null)
            ->call('addToCart', $product->id)
            ->assertSet('cart', [
                $product->id => ['name' => $product->name, 'emoji' => null, 'price' => 1000, 'quantity' => 1],
            ]);

        $this->assertDatabaseCount('cash_register_closings', 0);
    }
}
