<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_access_categories_page(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get('/categories')->assertOk();
    }

    public function test_cashier_cannot_access_categories_page(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/categories')->assertForbidden();
    }

    public function test_manager_can_create_a_category(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test('categories.index')
            ->set('name', 'Burgers')
            ->call('save');

        $this->assertDatabaseHas('categories', ['name' => 'Burgers']);
    }

    public function test_manager_can_soft_delete_a_category(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();

        Livewire::actingAs($manager)
            ->test('categories.index')
            ->call('delete', $category->id);

        $this->assertSoftDeleted($category);
    }
}
