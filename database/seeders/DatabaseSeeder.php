<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Bertony Effa',
            'email' => 'owner@mcberto.test',
            'role' => UserRole::Owner,
        ]);

        $manager = User::factory()->create([
            'name' => 'Gestionnaire McBerto',
            'email' => 'manager@mcberto.test',
            'role' => UserRole::Manager,
        ]);

        $cashiers = collect([
            ['name' => 'Caissier Un', 'email' => 'cashier1@mcberto.test'],
            ['name' => 'Caissier Deux', 'email' => 'cashier2@mcberto.test'],
        ])->map(fn (array $attrs) => User::factory()->create([
            ...$attrs,
            'role' => UserRole::Cashier,
        ]));

        $catalog = [
            'Burgers' => [
                ['Cheeseburger', 1500],
                ['Chicken Deluxe', 2000],
                ['Double Beef', 2500],
                ['Fish Burger', 1800],
            ],
            'Poulet' => [
                ['Poulet Pané (3 pièces)', 2200],
                ['Ailes de poulet (6 pièces)', 1800],
                ['Poulet Braisé', 2500],
            ],
            'Boissons' => [
                ['Coca-Cola 33cl', 500],
                ['Fanta 33cl', 500],
                ['Eau minérale 50cl', 300],
                ['Jus naturel', 700],
            ],
            'Accompagnements' => [
                ['Frites', 800],
                ['Salade', 700],
                ['Beignets (5 pièces)', 600],
            ],
        ];

        $products = collect();

        foreach ($catalog as $categoryName => $items) {
            $category = Category::create(['name' => $categoryName]);

            foreach ($items as [$name, $price]) {
                $products->push(Product::create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'price' => $price,
                ]));
            }
        }

        (new DemoSalesSeeder)->run($cashiers, $products, $manager);
    }
}
