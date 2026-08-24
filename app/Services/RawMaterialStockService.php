<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialStockMovement;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RawMaterialStockService
{
    public function recordPurchase(array $data, User $user): RawMaterialPurchase
    {
        return DB::transaction(function () use ($data, $user) {
            $material = RawMaterial::whereKey($data['raw_material_id'])->lockForUpdate()->firstOrFail();
            $quantity = (float) $data['quantity'];
            $totalPrice = (int) $data['total_price'];
            $unitPrice = $quantity > 0 ? $totalPrice / $quantity : 0;
            $stockBefore = (float) $material->current_quantity;
            $oldValue = $stockBefore * (float) $material->average_unit_cost;
            $stockAfter = $stockBefore + $quantity;
            $averageCost = $stockAfter > 0 ? ($oldValue + $totalPrice) / $stockAfter : $unitPrice;

            $purchase = RawMaterialPurchase::create([
                'raw_material_id' => $material->id,
                'user_id' => $user->id,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
                'unit_price' => $unitPrice,
                'supplier' => $data['supplier'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'note' => $data['note'] ?? null,
            ]);

            $material->update([
                'current_quantity' => $stockAfter,
                'average_unit_cost' => $averageCost,
            ]);

            RawMaterialStockMovement::create([
                'raw_material_id' => $material->id,
                'user_id' => $user->id,
                'raw_material_purchase_id' => $purchase->id,
                'type' => 'purchase',
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'unit_cost' => $unitPrice,
                'total_cost' => $totalPrice,
                'reason' => 'Achat matière première',
                'occurred_at' => $purchase->purchase_date->endOfDay(),
            ]);

            return $purchase;
        });
    }

    public function consumeForSale(Sale $sale): void
    {
        $sale->loadMissing('items.product.recipes.rawMaterial');

        $requirements = $this->requirementsForSale($sale);

        if ($requirements->isEmpty()) {
            return;
        }

        $materialIds = $requirements->keys()->all();
        $materials = RawMaterial::whereIn('id', $materialIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($requirements as $materialId => $required) {
            $material = $materials->get($materialId);

            if (! $material || (float) $material->current_quantity + 0.0001 < (float) $required['quantity']) {
                throw ValidationException::withMessages([
                    'stock' => 'Stock insuffisant pour '.$required['name'].'.',
                ]);
            }
        }

        foreach ($sale->items as $saleItem) {
            $product = $saleItem->product;

            if (! $product) {
                continue;
            }

            foreach ($product->recipes as $recipe) {
                $material = $materials->get($recipe->raw_material_id);
                $quantityOut = (float) $recipe->quantity * (int) $saleItem->quantity;
                $stockBefore = (float) $material->current_quantity;
                $stockAfter = $stockBefore - $quantityOut;
                $unitCost = (float) $material->average_unit_cost;

                $material->update(['current_quantity' => $stockAfter]);

                RawMaterialStockMovement::create([
                    'raw_material_id' => $material->id,
                    'user_id' => Auth::id(),
                    'sale_id' => $sale->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $product->id,
                    'type' => 'sale_consumption',
                    'quantity_in' => 0,
                    'quantity_out' => $quantityOut,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => $unitCost,
                    'total_cost' => (int) round($quantityOut * $unitCost),
                    'reason' => 'Vente '.$sale->receipt_number,
                    'occurred_at' => $sale->created_at,
                ]);
            }
        }
    }

    public function restoreForCanceledSale(Sale $sale, User $user): void
    {
        $movements = RawMaterialStockMovement::query()
            ->where('sale_id', $sale->id)
            ->where('type', 'sale_consumption')
            ->get();

        if ($movements->isEmpty()) {
            return;
        }

        foreach ($movements as $movement) {
            $material = RawMaterial::whereKey($movement->raw_material_id)->lockForUpdate()->first();

            if (! $material) {
                continue;
            }

            $quantityIn = (float) $movement->quantity_out;
            $stockBefore = (float) $material->current_quantity;
            $stockAfter = $stockBefore + $quantityIn;

            $material->update(['current_quantity' => $stockAfter]);

            RawMaterialStockMovement::create([
                'raw_material_id' => $material->id,
                'user_id' => $user->id,
                'sale_id' => $sale->id,
                'sale_item_id' => $movement->sale_item_id,
                'product_id' => $movement->product_id,
                'type' => 'sale_cancellation',
                'quantity_in' => $quantityIn,
                'quantity_out' => 0,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'unit_cost' => $movement->unit_cost,
                'total_cost' => $movement->total_cost,
                'reason' => 'Annulation '.$sale->receipt_number,
                'occurred_at' => now(),
            ]);
        }
    }

    public function recordManualMovement(array $data, User $user): RawMaterialStockMovement
    {
        return DB::transaction(function () use ($data, $user) {
            $material = RawMaterial::whereKey($data['raw_material_id'])->lockForUpdate()->firstOrFail();
            $stockBefore = (float) $material->current_quantity;
            $quantity = (float) $data['quantity'];
            $type = $data['type'];
            $stockAfter = match ($type) {
                'loss' => $stockBefore - $quantity,
                'inventory_correction' => $quantity,
                default => $stockBefore + $quantity,
            };

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Le stock ne peut pas devenir négatif.',
                ]);
            }

            $quantityIn = max($stockAfter - $stockBefore, 0);
            $quantityOut = max($stockBefore - $stockAfter, 0);
            $unitCost = (float) $material->average_unit_cost;

            $material->update(['current_quantity' => $stockAfter]);

            return RawMaterialStockMovement::create([
                'raw_material_id' => $material->id,
                'user_id' => $user->id,
                'type' => $type,
                'quantity_in' => $quantityIn,
                'quantity_out' => $quantityOut,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'unit_cost' => $unitCost,
                'total_cost' => (int) round(max($quantityIn, $quantityOut) * $unitCost),
                'reason' => $data['reason'] ?? null,
                'occurred_at' => now(),
            ]);
        });
    }

    public function recipeCost(Product $product): int
    {
        $product->loadMissing('recipes.rawMaterial');

        return (int) round($product->recipes->sum(
            fn ($recipe) => (float) $recipe->quantity * (float) $recipe->rawMaterial->average_unit_cost
        ));
    }

    private function requirementsForSale(Sale $sale): Collection
    {
        return $sale->items
            ->flatMap(function ($saleItem) {
                return $saleItem->product?->recipes->map(fn ($recipe) => [
                    'raw_material_id' => $recipe->raw_material_id,
                    'name' => $recipe->rawMaterial->name,
                    'quantity' => (float) $recipe->quantity * (int) $saleItem->quantity,
                ]) ?? collect();
            })
            ->groupBy('raw_material_id')
            ->map(fn (Collection $rows) => [
                'name' => $rows->first()['name'],
                'quantity' => $rows->sum('quantity'),
            ]);
    }
}
