<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Livewire\Dashboard\Overview;
use App\Models\CashRegisterClosing;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_access_dashboard(): void
    {
        $cashier = User::factory()->cashier()->create();
        $this->actingAs($cashier)->get('/dashboard')->assertForbidden();
    }

    public function test_manager_sees_correct_kpis(): void
    {
        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['total_amount' => 4000]);
        Sale::factory()->create(['total_amount' => 6000]);
        Sale::factory()->create(['sale_status' => SaleStatus::Canceled, 'total_amount' => 9000]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->assertSet('todayRevenue', 10000)
            ->assertSet('todayOrdersCount', 2)
            ->assertSet('averageTicket', 5000);
    }

    public function test_chart_data_covers_the_selected_period(): void
    {
        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['total_amount' => 1000, 'created_at' => now()]);
        Sale::factory()->create(['total_amount' => 2000, 'created_at' => now()->subDays(3)]);
        Sale::factory()->create(['total_amount' => 99999, 'created_at' => now()->subDays(20)]);

        $component = Livewire::actingAs($manager)->test(Overview::class);
        $data = $component->instance()->chartData();

        $this->assertCount(7, $data['labels']);
        $this->assertEquals(3000, array_sum($data['values']));

        $component->set('period', '30d');
        $data30 = $component->instance()->chartData();
        $this->assertCount(30, $data30['labels']);
        $this->assertEquals(102999, array_sum($data30['values']));
    }

    public function test_dashboard_uses_closing_totals_for_closed_days(): void
    {
        $manager = User::factory()->manager()->create();
        $closedDay = now()->subDay();
        $closing = CashRegisterClosing::factory()->create([
            'closing_date' => $closedDay->format('Y-m-d'),
            'total_amount' => 12000,
            'total_orders_count' => 3,
        ]);

        Sale::factory()->create([
            'cash_register_closing_id' => $closing->id,
            'total_amount' => 12000,
            'created_at' => $closedDay,
        ]);

        Sale::factory()->create([
            'cash_register_closing_id' => null,
            'total_amount' => 99999,
            'created_at' => $closedDay,
        ]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->assertSet('yesterdayRevenue', 12000)
            ->assertSet('yesterdayOrdersCount', 3);

        $data = Livewire::actingAs($manager)
            ->test(Overview::class)
            ->instance()
            ->chartData();

        $this->assertEquals(12000, $data['values'][5]);
    }

    public function test_net_profit_is_revenue_minus_expenses(): void
    {
        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['total_amount' => 10000, 'created_at' => now()]);
        Expense::factory()->create(['amount' => 4000, 'expense_date' => now()->format('Y-m-d')]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->assertSet('todayRevenue', 10000)
            ->assertSet('todayExpenses', 4000)
            ->assertSet('todayNetProfit', 6000);
    }

    public function test_change_percent_compares_today_to_yesterday(): void
    {
        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['total_amount' => 5000, 'created_at' => now()->subDay()]);
        Sale::factory()->create(['total_amount' => 6000, 'created_at' => now()]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->assertSet('yesterdayRevenue', 5000)
            ->assertSet('todayRevenue', 6000)
            ->assertSet('revenueChangePercent', 20.0);
    }

    public function test_change_percent_is_null_when_there_is_no_baseline(): void
    {
        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['total_amount' => 6000, 'created_at' => now()]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->assertSet('yesterdayRevenue', 0)
            ->assertSet('revenueChangePercent', null);
    }

    public function test_payment_method_breakdown_sums_and_percentages_are_correct(): void
    {
        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['payment_method' => PaymentMethod::Cash, 'total_amount' => 7500, 'created_at' => now()]);
        Sale::factory()->create(['payment_method' => PaymentMethod::OrangeMoney, 'total_amount' => 2500, 'created_at' => now()]);

        $breakdown = Livewire::actingAs($manager)
            ->test(Overview::class)
            ->instance()
            ->paymentMethodBreakdown();

        $this->assertCount(2, $breakdown);
        $this->assertEquals('cash', $breakdown[0]['method']->value);
        $this->assertEquals(7500, $breakdown[0]['amount']);
        $this->assertEquals(75.0, $breakdown[0]['percent']);
        $this->assertEquals(25.0, $breakdown[1]['percent']);
    }

    public function test_top_products_ranks_by_quantity_sold_within_the_period(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();
        $burger = Product::factory()->create(['category_id' => $category->id, 'name' => 'Cheeseburger']);
        $fries = Product::factory()->create(['category_id' => $category->id, 'name' => 'Frites']);

        $sale = Sale::factory()->create(['created_at' => now()]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $burger->id, 'product_name' => 'Cheeseburger', 'unit_price' => 1500, 'quantity' => 5, 'subtotal' => 7500]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $fries->id, 'product_name' => 'Frites', 'unit_price' => 800, 'quantity' => 2, 'subtotal' => 1600]);

        $top = Livewire::actingAs($manager)
            ->test(Overview::class)
            ->instance()
            ->topProducts();

        $this->assertEquals('Cheeseburger', $top->first()->product_name);
        $this->assertEquals(5, $top->first()->total_quantity);
    }

    public function test_today_closing_reflects_register_status(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->assertSet('todayClosing', null);

        CashRegisterClosing::factory()->create(['closing_date' => now()->format('Y-m-d')]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->assertSet('todayClosing.id', fn ($id) => $id !== null);
    }

    public function test_hourly_sales_bucket_todays_sales_by_hour(): void
    {
        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['total_amount' => 3000, 'created_at' => now()->setTime(12, 30)]);
        Sale::factory()->create(['total_amount' => 2000, 'created_at' => now()->setTime(12, 45)]);
        Sale::factory()->create(['total_amount' => 5000, 'created_at' => now()->setTime(19, 10)]);
        // Yesterday's sale must not leak into today's hourly breakdown.
        Sale::factory()->create(['total_amount' => 99999, 'created_at' => now()->subDay()->setTime(12, 0)]);

        $hourly = Livewire::actingAs($manager)
            ->test(Overview::class)
            ->instance()
            ->hourlySales();

        $this->assertCount(24, $hourly['labels']);
        $this->assertEquals('12h', $hourly['labels'][12]);
        $this->assertEquals(5000, $hourly['values'][12]);
        $this->assertEquals(5000, $hourly['values'][19]);
        $this->assertEquals(0, $hourly['values'][0]);
        $this->assertEquals(10000, array_sum($hourly['values']));
    }
}
