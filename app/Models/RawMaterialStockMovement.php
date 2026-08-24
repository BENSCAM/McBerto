<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialStockMovement extends Model
{
    use LogsActivity;

    public const TYPES = [
        'purchase' => 'Achat',
        'sale_consumption' => 'Consommation vente',
        'sale_cancellation' => 'Annulation vente',
        'adjustment' => 'Ajustement manuel',
        'loss' => 'Perte / casse',
        'inventory_correction' => 'Correction inventaire',
    ];

    protected $fillable = [
        'raw_material_id',
        'user_id',
        'sale_id',
        'sale_item_id',
        'product_id',
        'raw_material_purchase_id',
        'type',
        'quantity_in',
        'quantity_out',
        'stock_before',
        'stock_after',
        'unit_cost',
        'total_cost',
        'reason',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_in' => 'decimal:3',
            'quantity_out' => 'decimal:3',
            'stock_before' => 'decimal:3',
            'stock_after' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(RawMaterialPurchase::class, 'raw_material_purchase_id');
    }

    public function activityLabel(): string
    {
        return 'Mouvement '.$this->rawMaterial?->name;
    }
}
