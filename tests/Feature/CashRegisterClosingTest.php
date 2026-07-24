<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashRegisterClosingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aggregates_todays_unclosed_sales_and_closes_the_register(): void
    {
        $cashier = User::factory()->cashier()->create();

        Sale::factory()->create(['user_id' => $cashier->id, 'payment_method' => PaymentMethod::Cash, 'total_amount' => 1000]);
        Sale::factory()->create(['user_id' => $cashier->id, 'payment_method' => PaymentMethod::OrangeMoney, 'total_amount' => 2000]);

        Livewire::actingAs($cashier)
            ->test(\App\Livewire\Pos\CashRegisterClosing::class)
            ->assertSet('pendingTotal', 3000)
            ->assertSet('pendingCount', 2)
            ->set('countedCash', '1000')
            ->call('close')
            ->assertSet('pendingTotal', 0);

        $this->assertDatabaseHas('cash_register_closings', [
            'total_cash' => 1000,
            'counted_cash' => 1000,
            'variance' => 0,
            'total_orange_money' => 2000,
            'total_amount' => 3000,
            'total_orders_count' => 2,
            'closed_by' => $cashier->id,
        ]);

        $this->assertDatabaseCount('sales', 2);
        $this->assertEquals(2, Sale::whereNotNull('cash_register_closing_id')->count());
    }

    public function test_close_requires_a_counted_cash_amount(): void
    {
        $cashier = User::factory()->cashier()->create();
        Sale::factory()->create(['user_id' => $cashier->id, 'payment_method' => PaymentMethod::Cash, 'total_amount' => 1000]);

        Livewire::actingAs($cashier)
            ->test(\App\Livewire\Pos\CashRegisterClosing::class)
            ->call('close')
            ->assertHasErrors('countedCash');

        $this->assertDatabaseCount('cash_register_closings', 0);
    }

    public function test_variance_is_computed_between_counted_and_system_cash(): void
    {
        $cashier = User::factory()->cashier()->create();
        Sale::factory()->create(['user_id' => $cashier->id, 'payment_method' => PaymentMethod::Cash, 'total_amount' => 5000]);

        Livewire::actingAs($cashier)
            ->test(\App\Livewire\Pos\CashRegisterClosing::class)
            ->set('countedCash', '4700')
            ->assertSet('projectedVariance', -300)
            ->call('close');

        $this->assertDatabaseHas('cash_register_closings', [
            'total_cash' => 5000,
            'counted_cash' => 4700,
            'variance' => -300,
        ]);
    }

    public function test_closing_is_idempotent_for_the_same_day(): void
    {
        $cashier = User::factory()->cashier()->create();
        Sale::factory()->create(['user_id' => $cashier->id, 'total_amount' => 1000]);

        $component = Livewire::actingAs($cashier)->test(\App\Livewire\Pos\CashRegisterClosing::class);
        $component->set('countedCash', '1000')->call('close');

        $this->assertDatabaseCount('cash_register_closings', 1);

        // A fresh mount on the same day should show the existing closing, not allow a new one.
        $fresh = Livewire::actingAs($cashier)->test(\App\Livewire\Pos\CashRegisterClosing::class);
        $fresh->set('countedCash', '1000')->call('close');

        $this->assertDatabaseCount('cash_register_closings', 1);
    }
}
