<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Safe to run in production: only the real Propriétaire account (from
     * .env) and the product catalog are created. Demo manager/cashier
     * accounts and fake historical sales are local-only, since they'd be
     * confusing or misleading in a real deployment.
     */
    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => config('mcberto.admin.email')],
            [
                'name' => config('mcberto.admin.name'),
                'password' => bcrypt(config('mcberto.admin.password')),
                'role' => UserRole::Owner,
                'email_verified_at' => now(),
            ]
        );

        $catalog = $this->seedCatalog();

        if (app()->environment('local')) {
            $this->seedDemoData($owner, $catalog);
        }
    }

    protected function seedDemoData(User $owner, Collection $products): void
    {
        $manager = User::firstOrCreate(
            ['email' => 'manager@mcberto.test'],
            ['name' => 'Gestionnaire McBerto', 'password' => bcrypt('password'), 'role' => UserRole::Manager, 'email_verified_at' => now()]
        );

        $cashiers = collect([
            ['name' => 'Caissier Un', 'email' => 'cashier1@mcberto.test'],
            ['name' => 'Caissier Deux', 'email' => 'cashier2@mcberto.test'],
        ])->map(fn (array $attrs) => User::firstOrCreate(
            ['email' => $attrs['email']],
            ['name' => $attrs['name'], 'password' => bcrypt('password'), 'role' => UserRole::Cashier, 'email_verified_at' => now()]
        ));

        (new DemoSalesSeeder)->run($cashiers, $products, $manager);
    }

    protected function seedCatalog(): Collection
    {
        $catalog = [
            'Burgers' => [
                ['Cheeseburger', 1500, '🍔'],
                ['Chicken Deluxe', 2000, '🍔'],
                ['Double Beef', 2500, '🍔'],
                ['Fish Burger', 1800, '🍔'],
                ['Big McBerto', 3300, '🍔'],
                ['Veggie Burger', 1700, '🥬'],
            ],
            'Poulet' => [
                ['Poulet Pané (3 pièces)', 2200, '🍗'],
                ['Ailes de poulet (6 pièces)', 1800, '🍗'],
                ['Poulet Braisé', 2500, '🍗'],
                ['Nuggets (6 pièces)', 1500, '🍗'],
                ['Wrap Poulet', 1900, '🌯'],
            ],
            'Sandwichs' => [
                ['Sandwich Club', 2000, '🥪'],
                ['Sandwich Thon', 1800, '🥪'],
                ['Panini Jambon-Fromage', 1600, '🥪'],
            ],
            'Accompagnements' => [
                ['Frites', 800, '🍟'],
                ['Salade', 700, '🥗'],
                ['Beignets (5 pièces)', 600, '🍩'],
                ['Onion Rings', 900, '🧅'],
            ],
            'Boissons' => [
                ['Coca-Cola 33cl', 500, '🥤'],
                ['Fanta 33cl', 500, '🥤'],
                ['Eau minérale 50cl', 300, '💧'],
                ['Jus naturel', 700, '🧃'],
                ['Milkshake', 1200, '🥤'],
            ],
            'Boissons Chaudes' => [
                ['Café', 500, '☕'],
                ['Café Latte', 800, '☕'],
                ['Chocolat chaud', 700, '☕'],
            ],
            'Desserts' => [
                ['Muffin', 600, '🧁'],
                ['Glace', 700, '🍦'],
                ['Tarte aux pommes', 650, '🥧'],
            ],
            'Menus' => [
                ['Menu Cheeseburger', 2200, '🍽️'],
                ['Menu Poulet Pané', 2800, '🍽️'],
                ['Menu Double Beef', 3200, '🍽️'],
            ],
        ];

        $products = collect();

        foreach ($catalog as $categoryName => $items) {
            $category = Category::firstOrCreate(['name' => $categoryName]);

            foreach ($items as [$name, $price, $emoji]) {
                $products->push(Product::firstOrCreate(
                    ['category_id' => $category->id, 'name' => $name],
                    ['emoji' => $emoji, 'price' => $price]
                ));
            }
        }

        return $products;
    }
}
