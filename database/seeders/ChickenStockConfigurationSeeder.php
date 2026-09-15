<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;
use App\Services\ChickenStockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChickenStockConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        RawMaterial::firstOrCreate(
            ['name' => ChickenStockService::BREADED_PIECES_MATERIAL],
            ['unit' => 'piece', 'low_stock_threshold' => 24, 'is_active' => true]
        );

        $steaks = RawMaterial::firstOrCreate(
            ['name' => ChickenStockService::CHICKEN_STEAKS_MATERIAL],
            ['unit' => 'piece', 'low_stock_threshold' => 30, 'is_active' => true]
        );

        $breadedPieces = RawMaterial::where('name', ChickenStockService::BREADED_PIECES_MATERIAL)->firstOrFail();

        foreach ($this->breadedChickenRecipes() as [$productNames, $quantity]) {
            $this->attachRecipe($productNames, $breadedPieces, $quantity);
        }

        foreach ($this->burgerRecipes() as [$productNames, $quantity]) {
            $this->attachRecipe($productNames, $steaks, $quantity);
        }
    }

    private function attachRecipe(array $productNames, RawMaterial $material, int $quantity): void
    {
        Product::whereIn(
            DB::raw('LOWER(name)'),
            array_map('mb_strtolower', $productNames)
        )
            ->get()
            ->each(fn (Product $product) => ProductRecipe::updateOrCreate(
                ['product_id' => $product->id, 'raw_material_id' => $material->id],
                ['quantity' => $quantity, 'is_active' => true]
            ));
    }

    private function breadedChickenRecipes(): array
    {
        return [
            [['Poulet Pané (3 pièces)', 'Poulet Pané 3 pièces'], 3],
            [['Menu Poulet Pané'], 3],
        ];
    }

    private function burgerRecipes(): array
    {
        return [
            [['Berto Beef Chicken'], 1],
            [['Big Berto Chicken'], 2],
            [['Double Cheese Chicken', 'Double Chees Chicken'], 2],
        ];
    }
}
