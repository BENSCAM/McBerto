<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Livewire\System\ActivityHistory;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_access_activity_history(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get('/system/history')
            ->assertForbidden();
    }

    public function test_manager_can_view_activity_history(): void
    {
        $manager = User::factory()->manager()->create();
        ActivityLog::create([
            'user_id' => $manager->id,
            'action' => 'created',
            'description' => 'Produit Burger créé(e)',
        ]);

        $this->actingAs($manager)
            ->get('/system/history')
            ->assertOk()
            ->assertSee('Historique système')
            ->assertSee('Produit Burger');
    }

    public function test_authenticated_model_changes_are_logged(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();

        $this->actingAs($manager);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Burger Journalisé',
            'price' => 2500,
            'service_area' => 'standard',
            'is_active' => true,
        ]);

        $product->update(['price' => 3000]);
        $product->delete();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $manager->id,
            'action' => 'created',
            'subject_type' => Product::class,
            'subject_id' => $product->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $manager->id,
            'action' => 'updated',
            'subject_type' => Product::class,
            'subject_id' => $product->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $manager->id,
            'action' => 'deleted',
            'subject_type' => Product::class,
            'subject_id' => $product->id,
        ]);
    }

    public function test_activity_history_can_render_nested_array_values(): void
    {
        $manager = User::factory()->manager()->create();

        ActivityLog::create([
            'user_id' => $manager->id,
            'action' => 'offline_sync',
            'description' => '1 vente offline synchronisée',
            'new_values' => [
                'synced' => [[
                    'offline_uuid' => 'offline-sale-1',
                    'receipt_number' => 'MCB-20260815-0001',
                ]],
                'failed' => [],
            ],
        ]);

        Livewire::actingAs($manager)
            ->test(ActivityHistory::class)
            ->assertSee('1 vente offline synchronisée')
            ->assertSee('offline-sale-1')
            ->assertSee('MCB-20260815-0001');
    }

    public function test_activity_history_shows_cash_register_tickets(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create(['name' => 'Caissier Test']);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Croissant chocolat']);

        $sale = Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => 'MCB-20260814-0001',
            'payment_method' => PaymentMethod::Cash,
            'total_amount' => 3000,
            'amount_given' => 5000,
            'change_due' => 2000,
            'created_at' => now(),
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => 'Croissant chocolat',
            'unit_price' => 1500,
            'quantity' => 2,
            'subtotal' => 3000,
        ]);

        Livewire::actingAs($manager)
            ->test(ActivityHistory::class)
            ->assertSee('Tickets de caisse')
            ->assertSee('MCB-20260814-0001')
            ->assertSee('Caissier Test')
            ->assertSee('3 000 FCFA')
            ->assertSee('Voir');
    }

    public function test_activity_history_shows_sale_date_for_sale_item_logs(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create(['name' => 'Caissier Historique']);
        $product = Product::factory()->create(['name' => 'Berto Pilons']);
        $saleDate = now()->subDay()->setTime(15, 45);

        $this->actingAs($cashier);

        $sale = Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => 'MCB-'.$saleDate->format('Ymd').'-0001',
            'created_at' => $saleDate,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => 'Berto Pilons',
            'unit_price' => 1950,
            'quantity' => 1,
            'subtotal' => 1950,
        ]);

        Livewire::actingAs($manager)
            ->test(ActivityHistory::class)
            ->assertSee($saleDate->format('d/m/Y H:i'))
            ->assertSee('Action : '.now()->format('d/m/Y H:i'))
            ->assertSee('Ligne de vente Berto Pilons créé(e)');
    }

    public function test_manager_can_open_order_ticket_from_activity_history(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create(['name' => 'Caissier Ticket']);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Menu Berto']);

        $sale = Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => 'MCB-20260815-0007',
            'payment_method' => PaymentMethod::Cash,
            'total_amount' => 7000,
            'amount_given' => 10000,
            'change_due' => 3000,
            'created_at' => now(),
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => 'Menu Berto',
            'unit_price' => 3500,
            'quantity' => 2,
            'subtotal' => 7000,
        ]);

        Livewire::actingAs($manager)
            ->test(ActivityHistory::class)
            ->call('viewOrderTicket', $sale->id)
            ->assertSet('selectedOrderId', $sale->id)
            ->assertSee('Ticket détaillé')
            ->assertSee('MCB-20260815-0007')
            ->assertSee('2 x Menu Berto')
            ->assertSee('10 000 FCFA')
            ->assertSee('3 000 FCFA')
            ->call('closeOrderTicket')
            ->assertSet('selectedOrderId', null)
            ->assertDontSee('Ticket détaillé');
    }

    public function test_manager_can_filter_order_tickets_by_date_and_view_contents(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create(['name' => 'Caissier Date']);
        $category = Category::factory()->create();
        $burger = Product::factory()->create(['category_id' => $category->id, 'name' => 'Burger filtre']);
        $firstTargetDate = now()->subDays(2)->setTime(9, 15);
        $targetDate = now()->subDays(2)->setTime(14, 30);
        $otherDate = now()->subDay()->setTime(10, 15);

        $firstTargetSale = Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => 'MCB-'.$firstTargetDate->format('Ymd').'-0001',
            'payment_method' => PaymentMethod::Cash,
            'total_amount' => 1000,
            'created_at' => $firstTargetDate,
        ]);

        SaleItem::create([
            'sale_id' => $firstTargetSale->id,
            'product_id' => $burger->id,
            'product_name' => 'Burger filtre matin',
            'unit_price' => 1000,
            'quantity' => 1,
            'subtotal' => 1000,
        ]);

        $targetSale = Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => 'MCB-'.$targetDate->format('Ymd').'-0002',
            'payment_method' => PaymentMethod::Cash,
            'total_amount' => 4500,
            'amount_given' => 5000,
            'change_due' => 500,
            'created_at' => $targetDate,
        ]);

        SaleItem::create([
            'sale_id' => $targetSale->id,
            'product_id' => $burger->id,
            'product_name' => 'Burger filtre',
            'unit_price' => 1500,
            'quantity' => 3,
            'subtotal' => 4500,
        ]);

        Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => 'MCB-'.$otherDate->format('Ymd').'-0001',
            'payment_method' => PaymentMethod::Cash,
            'total_amount' => 1000,
            'created_at' => $otherDate,
        ]);

        Livewire::actingAs($manager)
            ->test(ActivityHistory::class)
            ->set('orderDate', $targetDate->toDateString())
            ->assertSet('exportStartDate', $targetDate->toDateString())
            ->assertSet('exportEndDate', $targetDate->toDateString())
            ->assertSee('MCB-'.$targetDate->format('Ymd').'-0001')
            ->assertSeeInOrder([
                'MCB-'.$targetDate->format('Ymd').'-0001',
                'MCB-'.$targetDate->format('Ymd').'-0002',
            ])
            ->assertDontSee('MCB-'.$otherDate->format('Ymd').'-0001')
            ->call('viewOrderTicket', $targetSale->id)
            ->assertSee('Ticket détaillé')
            ->assertSee('3 x Burger filtre')
            ->assertSee('4 500 FCFA')
            ->assertSee('500 FCFA')
            ->call('clearOrderDate')
            ->assertSet('orderDate', '');
    }

    public function test_manager_can_export_order_history_pdf_for_a_date_range(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create(['name' => 'Caissier Export']);
        $category = Category::factory()->create();
        $burger = Product::factory()->create(['category_id' => $category->id, 'name' => 'Burger PDF']);
        $startDate = now()->subDays(3)->setTime(8, 30);
        $endDate = now()->subDays(2)->setTime(16, 45);

        $firstSale = Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => 'MCB-'.$startDate->format('Ymd').'-0001',
            'payment_method' => PaymentMethod::Cash,
            'total_amount' => 3000,
            'amount_given' => 5000,
            'change_due' => 2000,
            'created_at' => $startDate,
        ]);
        SaleItem::create([
            'sale_id' => $firstSale->id,
            'product_id' => $burger->id,
            'product_name' => 'Burger PDF matin',
            'unit_price' => 1500,
            'quantity' => 2,
            'subtotal' => 3000,
        ]);

        $secondSale = Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => 'MCB-'.$endDate->format('Ymd').'-0001',
            'payment_method' => PaymentMethod::OrangeMoney,
            'total_amount' => 1500,
            'created_at' => $endDate,
        ]);
        SaleItem::create([
            'sale_id' => $secondSale->id,
            'product_id' => $burger->id,
            'product_name' => 'Burger PDF soir',
            'unit_price' => 1500,
            'quantity' => 1,
            'subtotal' => 1500,
        ]);

        $outsideDate = now()->subDay()->setTime(12, 0);
        Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => 'MCB-'.$outsideDate->format('Ymd').'-0001',
            'payment_method' => PaymentMethod::Cash,
            'total_amount' => 999,
            'created_at' => $outsideDate,
        ]);

        $response = $this->actingAs($manager)->get(route('system.history.orders.pdf', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_order_history_pdf_rejects_an_invalid_date_range(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('system.history.orders.pdf', [
                'start_date' => now()->toDateString(),
                'end_date' => now()->subDay()->toDateString(),
            ]))
            ->assertStatus(422);
    }
}
