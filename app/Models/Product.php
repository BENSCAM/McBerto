<?php

namespace App\Models;

use App\Enums\ServiceArea;
use App\Models\Concerns\LogsActivity;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'emoji',
        'price',
        'service_area',
        'stock_quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'service_area' => ServiceArea::class,
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function materialCost(): int
    {
        return (int) round($this->recipes->sum(
            fn (ProductRecipe $recipe) => (float) $recipe->quantity * (float) $recipe->rawMaterial->average_unit_cost
        ));
    }

    public function activityLabel(): string
    {
        return "Produit {$this->name}";
    }
}
