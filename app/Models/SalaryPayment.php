<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'employee_type',
        'employee_id',
        'payroll_month',
        'gross_salary',
        'deduction_amount',
        'net_salary',
        'paid_at',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'payroll_month' => 'date',
            'gross_salary' => 'integer',
            'deduction_amount' => 'integer',
            'net_salary' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function employee(): User|StaffMember|null
    {
        return $this->employee_type === 'user'
            ? User::find($this->employee_id)
            : StaffMember::find($this->employee_id);
    }

    public function employeeName(): string
    {
        return $this->employee()?->name ?? 'Personnel supprimé';
    }

    public function activityLabel(): string
    {
        return "Paiement du salaire de {$this->employeeName()} pour {$this->payroll_month?->format('m/Y')}";
    }
}
