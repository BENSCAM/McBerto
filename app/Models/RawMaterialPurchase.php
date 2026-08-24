<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialPurchase extends Model
{
    use LogsActivity;

    protected $fillable = [
        'raw_material_id',
        'user_id',
        'quantity',
        'total_price',
        'unit_price',
        'supplier',
        'purchase_date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'total_price' => 'integer',
            'unit_price' => 'decimal:4',
            'purchase_date' => 'date',
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

    public function activityLabel(): string
    {
        return 'Achat '.$this->rawMaterial?->name;
    }
}
