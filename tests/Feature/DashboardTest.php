<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Livewire\Dashboard\Overview;
use App\Models\CashRegisterClosing;
use App\Models\Category;
use App\Models\DisciplinarySanction;
use App\Models\Expense;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\RawMaterialStockMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_dashboard_period_kpis_can_use_current_month(): void
    {
        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['total_amount' => 10000, 'created_at' => now()]);
        Sale::factory()->create(['total_amount' => 5000, 'created_at' => now()->startOfMonth()->addDays(2)]);
        Sale::factory()->create(['total_amount' => 99999, 'created_at' => now()->subMonth()]);
        Expense::factory()->create(['amount' => 3000, 'expense_date' => now()->toDateString()]);
        Expense::factory()->create(['amount' => 99999, 'expense_date' => now()->subMonth()->toDateString()]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->set('dashboardPeriod', 'month')
            ->assertSet('periodRevenue', 15000)
            ->assertSet('periodOrdersCount', 2)
            ->assertSet('periodAverageTicket', 7500)
            ->assertSet('periodExpenses', 3000)
            ->assertSet('periodNetProfit', 12000);
    }

    public function test_dashboard_period_kpis_can_use_current_operation_cycle(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['total_amount' => 10000, 'created_at' => Carbon::parse('2026-08-14 09:00:00')]);
        Sale::factory()->create(['total_amount' => 5000, 'created_at' => Carbon::parse('2026-09-02 09:00:00')]);
        Sale::factory()->create(['total_amount' => 99999, 'created_at' => Carbon::parse('2026-08-13 09:00:00')]);
        Sale::factory()->create(['total_amount' => 88888, 'created_at' => Carbon::parse('2026-09-14 09:00:00')]);
        Expense::factory()->create(['amount' => 3000, 'expense_date' => '2026-08-20']);
        Expense::factory()->create(['amount' => 99999, 'expense_date' => '2026-08-13']);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->set('dashboardPeriod', 'cycle')
            ->assertSet('periodLabel', '14/08/2026 - 13/09/2026')
            ->assertSet('periodRevenue', 15000)
            ->assertSet('periodOrdersCount', 2)
            ->assertSet('periodAverageTicket', 7500)
            ->assertSet('periodExpenses', 3000)
            ->assertSet('periodNetProfit', 12000);

        Carbon::setTestNow();
    }

    public function test_dashboard_monthly_kpis_show_payroll_and_real_net_profit(): void
    {
        $manager = User::factory()->manager()->create(['monthly_salary' => 150000]);
        $cashier = User::factory()->cashier()->create(['monthly_salary' => 80000, 'is_active' => true]);
        User::factory()->cashier()->create(['monthly_salary' => 70000, 'is_active' => false]);
        $staffMember = StaffMember::create([
            'name' => 'Cuisinier',
            'job_title' => 'Cuisinier',
            'monthly_salary' => 90000,
            'is_active' => true,
        ]);

        DisciplinarySanction::create([
            'employee_type' => 'user',
            'employee_id' => $cashier->id,
            'fault_type' => 'absence',
            'description' => 'Absence non justifiée',
            'fault_date' => now()->toDateString(),
            'sanction_type' => 'salary_deduction',
            'deduction_amount' => 10000,
            'responsible_id' => $manager->id,
            'status' => 'validated',
            'validated_at' => now(),
        ]);

        DisciplinarySanction::create([
            'employee_type' => 'staff',
            'employee_id' => $staffMember->id,
            'fault_type' => 'late',
            'description' => 'Retard non validé',
            'fault_date' => now()->toDateString(),
            'sanction_type' => 'salary_deduction',
            'deduction_amount' => 5000,
            'responsible_id' => $manager->id,
            'status' => 'draft',
        ]);

        Sale::factory()->create(['total_amount' => 500000, 'created_at' => now()]);
        Expense::factory()->create(['amount' => 20000, 'expense_date' => now()->toDateString()]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->set('dashboardPeriod', 'month')
            ->assertSet('periodRevenue', 500000)
            ->assertSet('periodExpenses', 20000)
            ->assertSet('periodUserPayrollGross', 230000)
            ->assertSet('periodStaffPayrollGross', 90000)
            ->assertSet('periodUserPayrollDeductions', 10000)
            ->assertSet('periodStaffPayrollDeductions', 0)
            ->assertSet('periodPayrollDeductions', 10000)
            ->assertSet('periodUserPayroll', 220000)
            ->assertSet('periodStaffPayroll', 90000)
            ->assertSet('periodPayrollTotal', 310000)
            ->assertSet('periodOperatingExpenses', 330000)
            ->assertSet('periodNetProfit', 170000)
            ->assertSee('Personnel sans accès à payer');
    }

    public function test_dashboard_shows_stock_alerts(): void
    {
        $manager = User::factory()->manager()->create();

        RawMaterial::create([
            'name' => 'Huile critique',
            'unit' => 'litre',
            'current_quantity' => 1,
            'low_stock_threshold' => 2,
            'average_unit_cost' => 1500,
            'is_active' => true,
        ]);

        RawMaterial::create([
            'name' => 'Oeufs à surveiller',
            'unit' => 'piece',
            'current_quantity' => 35,
            'low_stock_threshold' => 20,
            'average_unit_cost' => 100,
            'is_active' => true,
        ]);

        RawMaterial::create([
            'name' => 'Farine OK',
            'unit' => 'kg',
            'current_quantity' => 50,
            'low_stock_threshold' => 10,
            'average_unit_cost' => 500,
            'is_active' => true,
        ]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->assertSet('criticalStockCount', 1)
            ->assertSet('watchStockCount', 1)
            ->assertSee('Alertes stock')
            ->assertSee('Huile critique')
            ->assertSee('Critique')
            ->assertSee('Oeufs à surveiller')
            ->assertSee('À surveiller')
            ->assertDontSee('Farine OK');
    }

    public function test_dashboard_period_kpis_can_use_current_year(): void
    {
        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['total_amount' => 10000, 'created_at' => now()]);
        Sale::factory()->create(['total_amount' => 5000, 'created_at' => now()->startOfYear()->addMonths(2)]);
        Sale::factory()->create(['total_amount' => 99999, 'created_at' => now()->subYear()]);
        Expense::factory()->create(['amount' => 4000, 'expense_date' => now()->startOfYear()->addMonth()->toDateString()]);
        Expense::factory()->create(['amount' => 99999, 'expense_date' => now()->subYear()->toDateString()]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->set('dashboardPeriod', 'year')
            ->assertSet('periodRevenue', 15000)
            ->assertSet('periodOrdersCount', 2)
            ->assertSet('periodExpenses', 4000)
            ->assertSet('periodNetProfit', 11000);
    }

    public function test_dashboard_does_not_show_canceled_orders_section(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create(['name' => 'Caissier Annulation']);
        $cancelingManager = User::factory()->manager()->create(['name' => 'Gérant Annulation']);

        Sale::factory()->create([
            'user_id' => $cashier->id,
            'receipt_number' => 'MCB-20260816-0001',
            'sale_status' => SaleStatus::Canceled,
            'total_amount' => 4500,
            'canceled_by' => $cancelingManager->id,
            'canceled_at' => now(),
            'cancellation_reason' => 'Erreur de saisie',
        ]);

        Sale::factory()->create([
            'sale_status' => SaleStatus::Canceled,
            'total_amount' => 99999,
            'canceled_at' => now()->subMonth(),
        ]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->assertSet('periodCanceledOrdersCount', 1)
            ->assertSet('periodCanceledOrdersTotal', 4500)
            ->assertDontSee('Commandes annulées')
            ->assertDontSee('MCB-20260816-0001')
            ->assertDontSee('Caissier Annulation')
            ->assertDontSee('Gérant Annulation')
            ->assertDontSee('Erreur de saisie')
            ->assertDontSee('4 500 FCFA');
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

    public function test_payment_method_breakdown_follows_dashboard_period(): void
    {
        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['payment_method' => PaymentMethod::Cash, 'total_amount' => 10000, 'created_at' => now()->startOfMonth()->addDays(2)]);
        Sale::factory()->create(['payment_method' => PaymentMethod::OrangeMoney, 'total_amount' => 99999, 'created_at' => now()->subMonth()]);

        $breakdown = Livewire::actingAs($manager)
            ->test(Overview::class)
            ->set('dashboardPeriod', 'month')
            ->instance()
            ->paymentMethodBreakdown();

        $this->assertCount(1, $breakdown);
        $this->assertEquals('cash', $breakdown[0]['method']->value);
        $this->assertEquals(10000, $breakdown[0]['amount']);
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

    public function test_period_breakdown_switches_between_day_month_and_year(): void
    {
        $manager = User::factory()->manager()->create();

        Sale::factory()->create(['total_amount' => 3000, 'created_at' => now()->setTime(12, 0)]);
        Sale::factory()->create(['total_amount' => 2000, 'created_at' => now()->startOfMonth()->addDays(2)]);

        $component = Livewire::actingAs($manager)->test(Overview::class);

        $day = $component->instance()->periodBreakdown();
        $this->assertCount(24, $day['labels']);

        $component->set('dashboardPeriod', 'month');
        $month = $component->instance()->periodBreakdown();
        $this->assertCount(now()->daysInMonth, $month['labels']);
        $this->assertEquals(5000, array_sum($month['values']));

        $component->set('dashboardPeriod', 'cycle');
        $cycle = $component->instance()->periodBreakdown();
        $this->assertContains(now()->format('d/m'), $cycle['labels']);
        $this->assertEquals(5000, array_sum($cycle['values']));

        $component->set('dashboardPeriod', 'year');
        $year = $component->instance()->periodBreakdown();
        $this->assertCount(12, $year['labels']);
        $this->assertEquals(5000, array_sum($year['values']));
    }

    public function test_manager_can_delete_orders_for_a_specific_period_and_restore_consumed_stock(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create();
        $material = RawMaterial::create([
            'name' => 'Pain',
            'unit' => 'piece',
            'current_quantity' => 8,
            'low_stock_threshold' => 2,
            'average_unit_cost' => 100,
            'is_active' => true,
        ]);
        $sale = Sale::factory()->create([
            'user_id' => $cashier->id,
            'total_amount' => 5000,
            'created_at' => Carbon::parse('2026-09-01 12:00:00'),
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_name' => 'Burger',
            'unit_price' => 2500,
            'quantity' => 2,
            'subtotal' => 5000,
        ]);

        RawMaterialStockMovement::create([
            'raw_material_id' => $material->id,
            'user_id' => $cashier->id,
            'sale_id' => $sale->id,
            'type' => 'sale_consumption',
            'quantity_in' => 0,
            'quantity_out' => 2,
            'stock_before' => 10,
            'stock_after' => 8,
            'unit_cost' => 100,
            'total_cost' => 200,
            'reason' => 'Vente '.$sale->receipt_number,
            'occurred_at' => $sale->created_at,
        ]);

        CashRegisterClosing::factory()->create([
            'closing_date' => '2026-09-01',
            'total_amount' => 5000,
            'total_orders_count' => 1,
        ]);

        Sale::factory()->create(['total_amount' => 7000, 'created_at' => Carbon::parse('2026-09-03 12:00:00')]);

        Livewire::actingAs($manager)
            ->test(Overview::class)
            ->set('deleteStartDate', '2026-09-01')
            ->set('deleteEndDate', '2026-09-01')
            ->set('deleteConfirmation', 'SUPPRIMER')
            ->call('deleteOrdersForPeriod')
            ->assertSet('lastDeletedOrders.orders', 1)
            ->assertSet('lastDeletedOrders.items', 1)
            ->assertSet('lastDeletedOrders.closings', 1)
            ->assertSet('lastDeletedOrders.amount', 5000);

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('cash_register_closings', ['closing_date' => '2026-09-01 00:00:00']);
        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(10.0, (float) $material->refresh()->current_quantity);
    }
}
