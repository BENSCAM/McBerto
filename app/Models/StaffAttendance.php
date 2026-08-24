<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class StaffAttendance extends Model
{
    use LogsActivity;

    protected $fillable = [
        'employee_type',
        'employee_id',
        'work_date',
        'scheduled_start',
        'actual_start',
        'scheduled_end',
        'actual_end',
        'status',
        'late_minutes',
        'absence_justified',
        'abandoned_post',
        'departure_time',
        'abandonment_severity',
        'abandonment_explanation',
        'comment',
        'reported_by',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'absence_justified' => 'boolean',
            'late_minutes' => 'integer',
        ];
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
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
        return "Présence {$this->employeeName()} du {$this->work_date?->format('d/m/Y')}";
    }

    public static function calculateLateMinutes(?string $scheduledStart, ?string $actualStart): int
    {
        if (! $scheduledStart || ! $actualStart) {
            return 0;
        }

        $scheduled = Carbon::createFromFormat('H:i', substr($scheduledStart, 0, 5));
        $actual = Carbon::createFromFormat('H:i', substr($actualStart, 0, 5));

        return max(0, $scheduled->diffInMinutes($actual, false));
    }
}
