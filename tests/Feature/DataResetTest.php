<?php

namespace Tests\Feature;

use App\Livewire\System\DataReset;
use App\Models\ActivityLog;
use App\Models\BugLog;
use App\Models\CashRegisterClosing;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class DataResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_can_access_data_reset_page(): void
    {
        $manager = User::factory()->manager()->create();
        $owner = User::factory()->owner()->create();

        $this->actingAs($manager)->get('/system/reset')->assertForbidden();
        $this->actingAs($owner)->get('/system/reset')->assertOk()->assertSee('Réinitialisation des données');
    }

    public function test_confirmation_is_required_before_reset(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(DataReset::class)
            ->call('resetData')
            ->assertHasErrors('confirmation');
    }

    public function test_owner_can_reset_operational_dashboard_data(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $sale = Sale::factory()->create(['user_id' => $owner->id]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);
        CashRegisterClosing::factory()->create(['closed_by' => $owner->id]);
        Expense::factory()->create(['user_id' => $owner->id]);
        ActivityLog::create(['user_id' => $owner->id, 'action' => 'created', 'description' => 'Action test']);
        BugLog::record(new RuntimeException('Bug test'));

        Livewire::actingAs($owner)
            ->test(DataReset::class)
            ->set('confirmation', 'REINITIALISER')
            ->call('resetData')
            ->assertSet('resetDone', true);

        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('cash_register_closings', 0);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('bug_logs', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $owner->id,
            'action' => 'reset',
            'description' => 'Données du dashboard réinitialisées pour le lancement',
        ]);
    }
}
