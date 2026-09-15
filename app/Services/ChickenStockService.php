<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChickenStockService
{
    public const BREADED_PIECES_PER_CHICKEN = 12;

    public const STEAKS_PER_CHICKEN = 15;

    public const BREADED_PIECES_MATERIAL = 'Morceaux de poulet à paner';

    public const CHICKEN_STEAKS_MATERIAL = 'Steaks de poulet';

    /**
     * Record whole chickens as their two usable stock outputs.
     *
     * The purchase cost is split using the expected revenue supplied by the
     * business: 10,500 FCFA for the breaded pieces and 22,500 FCFA for steaks.
     */
    public function recordBatch(array $data, User $user): void
    {
        DB::transaction(function () use ($data, $user) {
            $chickens = (int) $data['chickens'];
            $totalPrice = (int) $data['total_price'];
            $breadedCost = (int) round($totalPrice * 10_500 / 33_000);
            $steakCost = $totalPrice - $breadedCost;

            $breadedPieces = $this->material(
                self::BREADED_PIECES_MATERIAL,
                self::BREADED_PIECES_PER_CHICKEN * 2
            );
            $steaks = $this->material(
                self::CHICKEN_STEAKS_MATERIAL,
                self::STEAKS_PER_CHICKEN * 2
            );

            $common = [
                'supplier' => $data['supplier'] ?? null,
                'purchase_date' => $data['purchase_date'],
            ];

            app(RawMaterialStockService::class)->recordPurchase($common + [
                'raw_material_id' => $breadedPieces->id,
                'quantity' => $chickens * self::BREADED_PIECES_PER_CHICKEN,
                'total_price' => $breadedCost,
                'note' => $this->note($chickens, '12 morceaux à paner', $data['note'] ?? null),
                'movement_type' => 'chicken_supply',
                'movement_reason' => $this->note($chickens, '12 morceaux à paner', $data['note'] ?? null),
            ], $user);

            app(RawMaterialStockService::class)->recordPurchase($common + [
                'raw_material_id' => $steaks->id,
                'quantity' => $chickens * self::STEAKS_PER_CHICKEN,
                'total_price' => $steakCost,
                'note' => $this->note($chickens, '15 steaks', $data['note'] ?? null),
                'movement_type' => 'chicken_supply',
                'movement_reason' => $this->note($chickens, '15 steaks', $data['note'] ?? null),
            ], $user);
        });
    }

    private function material(string $name, int $threshold): RawMaterial
    {
        return RawMaterial::firstOrCreate(
            ['name' => $name],
            [
                'unit' => 'piece',
                'current_quantity' => 0,
                'low_stock_threshold' => $threshold,
                'average_unit_cost' => 0,
                'is_active' => true,
            ]
        );
    }

    private function note(int $chickens, string $output, ?string $note): string
    {
        return trim("Découpe de {$chickens} poulet(s) — {$output}".($note ? " — {$note}" : ''));
    }
}
