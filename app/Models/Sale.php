<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_method',
        'service_area',
        'total_amount',
        'amount_given',
        'change_due',
        'cash_register_closing_id',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'service_area' => \App\Enums\ServiceArea::class,
            'total_amount' => 'integer',
            'amount_given' => 'integer',
            'change_due' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function closing(): BelongsTo
    {
        return $this->belongsTo(CashRegisterClosing::class, 'cash_register_closing_id');
    }
}
