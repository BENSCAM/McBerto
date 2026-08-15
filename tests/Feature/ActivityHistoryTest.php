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
}
