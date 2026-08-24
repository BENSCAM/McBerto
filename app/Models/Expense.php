<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, LogsActivity;

    public const CATEGORIES = [
        'matieres_premieres' => 'Matières premières',
        'charges' => 'Charges',
        'salaires' => 'Salaires',
        'transport' => 'Transport',
        'loyer' => 'Loyer',
        'electricite_eau' => 'Électricité / eau',
        'autre' => 'Autre',
    ];

    public const GENERAL_CATEGORIES = [
        'charges' => 'Charges',
        'salaires' => 'Salaires',
        'transport' => 'Transport',
        'loyer' => 'Loyer',
        'electricite_eau' => 'Électricité / eau',
        'autre' => 'Autre',
    ];

    protected $fillable = [
        'user_id',
        'category',
        'description',
        'amount',
        'expense_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'expense_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activityLabel(): string
    {
        return "Dépense {$this->description}";
    }
}
