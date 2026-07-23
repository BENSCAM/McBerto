<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegisterClosing extends Model
{
    protected $fillable = [
        'closing_date',
        'closed_by',
        'total_cash',
        'total_orange_money',
        'total_mtn_momo',
        'total_other',
        'total_amount',
        'total_orders_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'total_cash' => 'integer',
            'total_orange_money' => 'integer',
            'total_mtn_momo' => 'integer',
            'total_other' => 'integer',
            'total_amount' => 'integer',
            'total_orders_count' => 'integer',
        ];
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
