<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\CashRegisterClosingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CashRegisterClosing extends Model
{
    /** @use HasFactory<CashRegisterClosingFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'closing_date',
        'closed_by',
        'total_cash',
        'counted_cash',
        'variance',
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
            'counted_cash' => 'integer',
            'variance' => 'integer',
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

    public function activityLabel(): string
    {
        return 'Clôture caisse '.$this->closing_date?->format('d/m/Y');
    }

    /**
     * Reopen today's cash register: detaches its sales (so they become
     * pending again) and removes the closing record, allowing both new
     * sales and a fresh closing later that covers everything.
     */
    public static function reopenToday(): void
    {
        DB::transaction(function () {
            $closing = static::whereDate('closing_date', Carbon::today())->first();

            if (! $closing) {
                return;
            }

            Sale::where('cash_register_closing_id', $closing->id)->update(['cash_register_closing_id' => null]);
            $closing->delete();
        });
    }
}
