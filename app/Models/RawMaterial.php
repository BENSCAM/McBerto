<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    use LogsActivity;

    public const UNITS = [
        'kg' => 'kg',
        'g' => 'g',
        'litre' => 'litre',
        'ml' => 'ml',
        'piece' => 'pièce',
        'carton' => 'carton',
        'paquet' => 'paquet',
    ];

    protected $fillable = [
        'name',
        'unit',
        'current_quantity',
        'low_stock_threshold',
        'average_unit_cost',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'current_quantity' => 'decimal:3',
            'low_stock_threshold' => 'decimal:3',
            'average_unit_cost' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(RawMaterialPurchase::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(RawMaterialStockMovement::class);
    }

    public function isLowStock(): bool
    {
        return (float) $this->current_quantity <= (float) $this->low_stock_threshold;
    }

    public function activityLabel(): string
    {
        return "Matière première {$this->name}";
    }
}
