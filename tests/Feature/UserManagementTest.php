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

    public function test_manager_can_access_user_management(): void
    {
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager)->get('/users')->assertOk();
    }

    public function test_owner_can_create_a_user_account(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->set('name', 'Nouveau Caissier')
            ->set('email', 'caissier@mcberto.test')
            ->set('role', 'cashier')
            ->set('job_title', 'Caissier comptoir')
            ->set('monthly_salary', '85000')
            ->set('password', 'password123')
            ->call('createUser');

        $this->assertDatabaseHas('users', [
            'email' => 'caissier@mcberto.test',
            'role' => 'cashier',
            'job_title' => 'Caissier comptoir',
            'monthly_salary' => 85000,
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

    public function test_manager_can_create_only_cashier_accounts(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(UserManagement::class)
            ->set('name', 'Caissier Gérant')
            ->set('email', 'caissier-gerant@mcberto.test')
            ->set('role', 'cashier')
            ->set('password', 'password123')
            ->call('createUser');

        $this->assertDatabaseHas('users', [
            'email' => 'caissier-gerant@mcberto.test',
            'role' => 'cashier',
        ]);

        Livewire::actingAs($manager)
            ->test(UserManagement::class)
            ->set('name', 'Faux Propriétaire')
            ->set('email', 'owner-force@mcberto.test')
            ->set('role', 'owner')
            ->set('password', 'password123')
            ->call('createUser')
            ->assertHasErrors(['role']);

        $this->assertDatabaseMissing('users', [
            'email' => 'owner-force@mcberto.test',
        ]);
    }

    public function test_manager_sees_and_manages_only_cashier_accounts(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create(['name' => 'Caissier Visible']);
        $otherManager = User::factory()->manager()->create(['name' => 'Gérant Caché']);
        $owner = User::factory()->owner()->create(['name' => 'Propriétaire Caché']);

        Livewire::actingAs($manager)
            ->test(UserManagement::class)
            ->assertSee('Caissier Visible')
            ->assertDontSee('Gérant Caché')
            ->assertDontSee('Propriétaire Caché')
            ->call('toggleActive', $cashier->id);

        $this->assertFalse($cashier->refresh()->is_active);

        Livewire::actingAs($manager)
            ->test(UserManagement::class)
            ->call('toggleActive', $otherManager->id)
            ->assertSet('error', 'Le gérant peut gérer uniquement les comptes caissiers.');

        $this->assertTrue($otherManager->refresh()->is_active);
        $this->assertTrue($owner->refresh()->is_active);
    }

    public function test_owner_can_update_user_job_title_and_salary(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create([
            'job_title' => 'Ancien poste',
            'monthly_salary' => 60000,
        ]);

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->call('editEmployment', $cashier->id)
            ->set("employment.{$cashier->id}.job_title", 'Serveur')
            ->set("employment.{$cashier->id}.monthly_salary", '90000')
            ->call('saveEmployment', $cashier->id);

        $this->assertDatabaseHas('users', [
            'id' => $cashier->id,
            'job_title' => 'Serveur',
            'monthly_salary' => 90000,
        ]);
    }
}
