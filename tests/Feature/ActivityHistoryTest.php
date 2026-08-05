<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_access_activity_history(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get('/system/history')
            ->assertForbidden();
    }

    public function test_manager_can_view_activity_history(): void
    {
        $manager = User::factory()->manager()->create();
        ActivityLog::create([
            'user_id' => $manager->id,
            'action' => 'created',
            'description' => 'Produit Burger créé(e)',
        ]);

        $this->actingAs($manager)
            ->get('/system/history')
            ->assertOk()
            ->assertSee('Historique système')
            ->assertSee('Produit Burger');
    }

    public function test_authenticated_model_changes_are_logged(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::factory()->create();

        $this->actingAs($manager);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Burger Journalisé',
            'price' => 2500,
            'service_area' => 'standard',
            'is_active' => true,
        ]);

        $product->update(['price' => 3000]);
        $product->delete();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $manager->id,
            'action' => 'created',
            'subject_type' => Product::class,
            'subject_id' => $product->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $manager->id,
            'action' => 'updated',
            'subject_type' => Product::class,
            'subject_id' => $product->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $manager->id,
            'action' => 'deleted',
            'subject_type' => Product::class,
            'subject_id' => $product->id,
        ]);
    }
}
