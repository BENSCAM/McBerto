<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Livewire\Reports\DailyReport;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_access_expenses_page(): void
    {
        $cashier = User::factory()->cashier()->create();
        $this->actingAs($cashier)->get('/expenses')->assertForbidden();
    }

    public function test_manager_can_record_an_expense(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test('expenses.index')
            ->set('category', 'charges')
            ->set('amount', '15000')
            ->set('expense_date', now()->format('Y-m-d'))
            ->call('save');

        $this->assertDatabaseHas('expenses', [
            'user_id' => $manager->id,
            'category' => 'charges',
            'amount' => 15000,
        ]);
    }

    public function test_daily_report_computes_revenue_expenses_and_net_profit(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();

        Sale::factory()->create(['user_id' => $cashier->id, 'payment_method' => PaymentMethod::Cash, 'total_amount' => 10000]);
        Sale::factory()->create(['user_id' => $cashier->id, 'payment_method' => PaymentMethod::Cash, 'total_amount' => 5000]);
        Expense::factory()->create(['expense_date' => now()->format('Y-m-d'), 'amount' => 4000]);

        // A sale/expense from yesterday must not leak into today's report.
        Sale::factory()->create(['user_id' => $cashier->id, 'created_at' => now()->subDay(), 'total_amount' => 99999]);
        Expense::factory()->create(['expense_date' => now()->subDay()->format('Y-m-d'), 'amount' => 99999]);

        Livewire::actingAs($owner)
            ->test(DailyReport::class)
            ->assertSet('revenue', 15000)
            ->assertSet('expensesTotal', 4000)
            ->assertSet('netProfit', 11000)
            ->assertSet('salesCount', 2);
    }

    public function test_cashier_cannot_change_report_date_away_from_today(): void
    {
        $cashier = User::factory()->cashier()->create();

        Sale::factory()->create(['user_id' => $cashier->id, 'created_at' => now()->subDay(), 'total_amount' => 5000]);

        Livewire::actingAs($cashier)
            ->test(DailyReport::class)
            ->set('date', now()->subDay()->format('Y-m-d'))
            ->assertSet('date', now()->format('Y-m-d'))
            ->assertSet('revenue', 0);
    }
}
