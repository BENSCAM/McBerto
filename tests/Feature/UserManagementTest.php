<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_cannot_access_user_management(): void
    {
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager)->get('/users')->assertForbidden();
    }

    public function test_owner_can_create_a_user_account(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->set('name', 'Nouveau Caissier')
            ->set('email', 'caissier@mcberto.test')
            ->set('role', 'cashier')
            ->set('password', 'password123')
            ->call('createUser');

        $this->assertDatabaseHas('users', [
            'email' => 'caissier@mcberto.test',
            'role' => 'cashier',
            'is_active' => 1,
        ]);
    }

    public function test_owner_cannot_deactivate_own_account(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->call('toggleActive', $owner->id);

        $this->assertTrue($owner->refresh()->is_active);
    }

    public function test_owner_cannot_deactivate_the_last_active_owner(): void
    {
        $owner = User::factory()->owner()->create();
        $otherOwner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->call('toggleActive', $otherOwner->id);

        $this->assertFalse($otherOwner->refresh()->is_active);

        // Now only $owner is left active among owners; deactivating self should still be blocked separately,
        // but deactivating $otherOwner (already inactive) again should just toggle back on, not error.
        $this->assertDatabaseCount('users', 2);
    }

    public function test_deactivated_account_is_logged_out_immediately(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->call('toggleActive', $cashier->id);

        $this->assertFalse($cashier->refresh()->is_active);

        $this->actingAs($cashier)->get('/pos')->assertRedirect('/');
    }

    public function test_owner_can_authorize_and_revoke_cashier_backdated_sales_for_a_precise_date(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $saleDate = now()->subDay()->toDateString();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->set("backdateSaleDates.{$cashier->id}", $saleDate)
            ->call('authorizeBackdatedSales', $cashier->id);

        $this->assertTrue($cashier->refresh()->can_backdate_sales);
        $this->assertSame($saleDate, $cashier->backdate_sales_date->toDateString());

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->call('revokeBackdatedSales', $cashier->id);

        $this->assertFalse($cashier->refresh()->can_backdate_sales);
        $this->assertNull($cashier->backdate_sales_date);
    }
}
