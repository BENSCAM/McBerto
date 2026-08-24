<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinarySanction extends Model
{
    use LogsActivity;

    protected $fillable = [
        'employee_type',
        'employee_id',
        'fault_type',
        'description',
        'fault_date',
        'sanction_type',
        'deduction_amount',
        'responsible_id',
        'status',
        'comment',
        'validated_at',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'fault_date' => 'date',
            'deduction_amount' => 'integer',
            'validated_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
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

    public function validateSanction(User $responsible): void
    {
        $this->update([
            'status' => 'validated',
            'responsible_id' => $responsible->id,
            'validated_at' => now(),
            'canceled_at' => null,
        ]);
    }

    public function cancelSanction(User $responsible, ?string $comment = null): void
    {
        $this->update([
            'status' => 'canceled',
            'responsible_id' => $responsible->id,
            'comment' => $comment ?: $this->comment,
            'canceled_at' => now(),
        ]);
    }

    public function activityLabel(): string
    {
        return "Sanction {$this->employeeName()} du {$this->fault_date?->format('d/m/Y')}";
    }
}
