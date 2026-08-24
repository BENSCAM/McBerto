<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\User;
use App\Services\RawMaterialStockService;
use Illuminate\Database\Seeder;

class McBertoInitialRawMaterialsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('is_active', true)->orderBy('id')->first();

        if (! $user) {
            return;
        }

        $purchaseDate = now()->toDateString();
        $note = 'Stock initial matières premières - liste WhatsApp 46 400 FCFA';
        $legacyNote = 'Stock initial matières premières - liste WhatsApp 46 700 FCFA';

        foreach ($this->rawMaterials() as $item) {
            $material = RawMaterial::firstOrCreate(
                ['name' => $item['name']],
                [
                    'unit' => $item['unit'],
                    'current_quantity' => 0,
                    'low_stock_threshold' => $item['threshold'],
                    'average_unit_cost' => 0,
                    'is_active' => true,
                ]
            );

            $material->update([
                'unit' => $item['unit'],
                'low_stock_threshold' => $item['threshold'],
                'is_active' => true,
            ]);

            $alreadySeeded = RawMaterialPurchase::where('raw_material_id', $material->id)
                ->whereIn('note', [$note, $legacyNote])
                ->exists();

            if ($alreadySeeded) {
                continue;
            }

            app(RawMaterialStockService::class)->recordPurchase([
                'raw_material_id' => $material->id,
                'quantity' => $item['quantity'],
                'total_price' => $item['total_price'],
                'supplier' => 'Achat marché',
                'purchase_date' => $purchaseDate,
                'note' => $note,
            ], $user);
        }

        foreach ($this->generalExpenses() as $expense) {
            $exists = Expense::where('category', $expense['category'])
                ->where('description', $expense['description'])
                ->whereDate('expense_date', $purchaseDate)
                ->exists();

            if (! $exists) {
                Expense::create([
                    'user_id' => $user->id,
                    'category' => $expense['category'],
                    'description' => $expense['description'],
                    'amount' => $expense['amount'],
                    'expense_date' => $purchaseDate,
                ]);
            }
        }
    }

    protected function rawMaterials(): array
    {
        return [
            ['name' => 'Huile', 'unit' => 'litre', 'quantity' => 5, 'threshold' => 1, 'total_price' => 7500],
            ['name' => 'Viande hachée', 'unit' => 'kg', 'quantity' => 2, 'threshold' => 0.5, 'total_price' => 7400],
            ['name' => 'Oeufs', 'unit' => 'piece', 'quantity' => 30, 'threshold' => 6, 'total_price' => 2800],
            ['name' => 'Cornflakes', 'unit' => 'paquet', 'quantity' => 3, 'threshold' => 1, 'total_price' => 8400],
            ['name' => 'Mayonnaise', 'unit' => 'piece', 'quantity' => 1, 'threshold' => 1, 'total_price' => 3300],
            ['name' => 'Lait entier', 'unit' => 'litre', 'quantity' => 1, 'threshold' => 1, 'total_price' => 1500],
            ['name' => 'Moutarde', 'unit' => 'piece', 'quantity' => 1, 'threshold' => 1, 'total_price' => 1500],
            ['name' => 'Plantains', 'unit' => 'paquet', 'quantity' => 1, 'threshold' => 1, 'total_price' => 2000],
            ['name' => 'Ketchup', 'unit' => 'piece', 'quantity' => 1, 'threshold' => 1, 'total_price' => 2000],
            ['name' => 'Tomates', 'unit' => 'kg', 'quantity' => 1, 'threshold' => 0.5, 'total_price' => 1000],
            ['name' => 'Vinaigre', 'unit' => 'litre', 'quantity' => 1, 'threshold' => 0.25, 'total_price' => 400],
            ['name' => 'Pain rassis', 'unit' => 'piece', 'quantity' => 1, 'threshold' => 1, 'total_price' => 500],
            ['name' => 'Pain', 'unit' => 'piece', 'quantity' => 1, 'threshold' => 1, 'total_price' => 500],
            ['name' => 'Épices poulet & viande', 'unit' => 'paquet', 'quantity' => 1, 'threshold' => 1, 'total_price' => 600],
            ['name' => 'Poivre blanc', 'unit' => 'paquet', 'quantity' => 1, 'threshold' => 1, 'total_price' => 1000],
            ['name' => 'Céleri, basilic & persil', 'unit' => 'paquet', 'quantity' => 1, 'threshold' => 1, 'total_price' => 500],
            ['name' => 'Plastiques noirs', 'unit' => 'paquet', 'quantity' => 1, 'threshold' => 1, 'total_price' => 500],
        ];
    }

    protected function generalExpenses(): array
    {
        return [
            ['category' => 'charges', 'description' => 'Insecticides', 'amount' => 3000],
            ['category' => 'charges', 'description' => 'Pelles à ordures x 2', 'amount' => 1000],
            ['category' => 'charges', 'description' => 'Détergent', 'amount' => 500],
            ['category' => 'transport', 'description' => 'Transport achat matières premières', 'amount' => 500],
        ];
    }
}
