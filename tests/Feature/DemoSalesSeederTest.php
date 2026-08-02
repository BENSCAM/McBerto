<?php

namespace Tests\Feature;

use App\Enums\ServiceArea;
use App\Models\CashRegisterClosing;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSalesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSalesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_sales_seeder_can_be_run_more_than_once(): void
    {
        $manager = User::factory()->manager()->create();
        $cashiers = collect([
            User::factory()->cashier()->create(),
            User::factory()->cashier()->create(),
        ]);
        $category = Category::factory()->create();
        $products = collect([
            Product::factory()->create(['category_id' => $category->id, 'service_area' => ServiceArea::Standard]),
            Product::factory()->create(['category_id' => $category->id, 'service_area' => ServiceArea::Vip]),
        ]);

        $seeder = new DemoSalesSeeder;

        $seeder->run($cashiers, $products, $manager);
        $firstClosingCount = CashRegisterClosing::count();

        $seeder->run($cashiers, $products, $manager);

        $this->assertSame($firstClosingCount, CashRegisterClosing::count());
    }
}
