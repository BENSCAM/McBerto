<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ServiceArea;
use App\Models\Concerns\LogsActivity;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'receipt_number',
        'offline_uuid',
        'user_id',
        'payment_method',
        'service_area',
        'sale_status',
        'total_amount',
        'amount_given',
        'change_due',
        'cash_register_closing_id',
        'canceled_by',
        'canceled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'service_area' => ServiceArea::class,
            'sale_status' => SaleStatus::class,
            'total_amount' => 'integer',
            'amount_given' => 'integer',
            'change_due' => 'integer',
            'canceled_at' => 'datetime',
        ];
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('sale_status', SaleStatus::Completed);
    }

    public static function nextReceiptNumber(?Carbon $date = null): string
    {
        $date ??= now();
        $prefix = 'MCB-'.$date->format('Ymd').'-';
        $lastReceiptNumber = static::whereDate('created_at', $date)
            ->where('receipt_number', 'like', $prefix.'%')
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $nextSequence = $lastReceiptNumber ? ((int) substr($lastReceiptNumber, -4)) + 1 : 1;

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
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

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    public function activityLabel(): string
    {
        return 'Vente '.($this->receipt_number ?? '#'.$this->id);
    }
}
