<?php

namespace Tests\Feature;

use App\Livewire\Pos\ClosingHistory;
use App\Models\CashRegisterClosing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClosingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_access_closing_history(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/pos/cloture/historique')->assertForbidden();
    }

    public function test_manager_can_view_closing_history(): void
    {
        $manager = User::factory()->manager()->create();
        CashRegisterClosing::factory()->count(3)->create(['closed_by' => $manager->id]);

        $this->actingAs($manager)
            ->get('/pos/cloture/historique')
            ->assertOk()
            ->assertSee('Historique des clôtures');
    }

    public function test_closing_history_is_paginated(): void
    {
        $manager = User::factory()->manager()->create();
        CashRegisterClosing::factory()->count(20)->create(['closed_by' => $manager->id]);

        $paginator = Livewire::actingAs($manager)
            ->test(ClosingHistory::class)
            ->instance()
            ->closings();

        $this->assertEquals(15, $paginator->count());
        $this->assertEquals(20, $paginator->total());
    }
}
