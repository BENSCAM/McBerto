<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\Overview;
use App\Models\Sale;
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
}
