<?php

namespace App\Livewire\Hr;

use App\Models\DisciplinarySanction;
use App\Models\HrSetting;
use App\Models\StaffAttendance;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\HrDisciplineCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Attendance extends Component
{
    public string $workDate;

    public string $employeeKey = '';

    public string $scheduledStart = '08:00';

    public string $actualStart = '';

    public string $scheduledEnd = '18:00';

    public string $actualEnd = '';

    public string $status = 'present';

    public bool $absenceJustified = false;

    public string $comment = '';

    public string $abandonedPost = '';

    public string $departureTime = '';

    public string $abandonmentSeverity = 'grave';

    public string $abandonmentExplanation = '';

    public ?string $notice = null;

    public function mount(): void
    {
        $this->workDate = today()->toDateString();
    }

    public function saveAttendance(): void
    {
        $this->validate([
            'workDate' => ['required', 'date'],
            'employeeKey' => ['required', 'string'],
            'scheduledStart' => ['nullable', 'date_format:H:i'],
            'actualStart' => ['nullable', 'date_format:H:i'],
            'scheduledEnd' => ['nullable', 'date_format:H:i'],
            'actualEnd' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:present,late,absent,abandoned,rest,leave'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'abandonedPost' => ['nullable', 'string', 'max:120'],
            'departureTime' => ['nullable', 'date_format:H:i'],
            'abandonmentSeverity' => ['nullable', 'in:faible,moyenne,grave'],
            'abandonmentExplanation' => ['nullable', 'string', 'max:1000'],
        ]);

        [$employeeType, $employeeId] = $this->employeeParts($this->employeeKey);
        $employee = $this->findEmployee($employeeType, $employeeId);

        if (! $employee) {
            throw ValidationException::withMessages(['employeeKey' => 'Employé introuvable.']);
        }

        $lateMinutes = StaffAttendance::calculateLateMinutes($this->scheduledStart ?: null, $this->actualStart ?: null);
        $status = $this->status === 'present' && $lateMinutes > 0 ? 'late' : $this->status;

        StaffAttendance::updateOrCreate([
            'employee_type' => $employeeType,
            'employee_id' => $employeeId,
            'work_date' => $this->workDate,
        ], [
            'scheduled_start' => $this->scheduledStart ?: null,
            'actual_start' => $this->actualStart ?: null,
            'scheduled_end' => $this->scheduledEnd ?: null,
            'actual_end' => $this->actualEnd ?: null,
            'status' => $status,
            'late_minutes' => $status === 'late' ? $lateMinutes : 0,
            'absence_justified' => $status === 'absent' && $this->absenceJustified,
            'abandoned_post' => $status === 'abandoned' ? ($this->abandonedPost ?: ($employee->job_title ?? null)) : null,
            'departure_time' => $status === 'abandoned' ? ($this->departureTime ?: null) : null,
            'abandonment_severity' => $status === 'abandoned' ? $this->abandonmentSeverity : null,
            'abandonment_explanation' => $status === 'abandoned' ? ($this->abandonmentExplanation ?: null) : null,
            'comment' => $this->comment ?: null,
            'reported_by' => $status === 'abandoned' ? Auth::id() : null,
            'recorded_by' => Auth::id(),
        ]);

        $this->notice = 'Présence enregistrée.';
        $this->resetForm();
    }

    public function createSanctionFromAttendance(int $attendanceId): void
    {
        $attendance = StaffAttendance::findOrFail($attendanceId);
        $employee = $attendance->employee();

        if (! $employee) {
            return;
        }

        $deduction = $this->suggestedDeduction($attendance);
        $faultType = match ($attendance->status) {
            'late' => 'late',
            'absent' => 'absence',
            'abandoned' => 'abandonment',
            default => 'other',
        };

        DisciplinarySanction::create([
            'employee_type' => $attendance->employee_type,
            'employee_id' => $attendance->employee_id,
            'fault_type' => $faultType,
            'description' => $this->sanctionDescription($attendance),
            'fault_date' => $attendance->work_date,
            'sanction_type' => $deduction > 0 ? 'salary_deduction' : 'verbal_reminder',
            'deduction_amount' => $deduction,
            'responsible_id' => Auth::id(),
            'status' => 'draft',
            'comment' => 'Créée depuis la présence.',
        ]);

        $this->notice = 'Sanction brouillon créée.';
    }

    public function suggestedDeduction(StaffAttendance $attendance): int
    {
        $employee = $attendance->employee();
        $salary = (int) ($employee?->monthly_salary ?? 0);
        $calculator = new HrDisciplineCalculator(HrSetting::current());

        return match ($attendance->status) {
            'absent' => $calculator->absenceDeduction($salary, $attendance->absence_justified),
            'late' => $calculator->lateDeduction($salary, $attendance->late_minutes),
            'abandoned' => $calculator->abandonmentDeduction($salary, $this->remainingMinutes($attendance)),
            default => 0,
        };
    }

    protected function remainingMinutes(StaffAttendance $attendance): int
    {
        if (! $attendance->departure_time || ! $attendance->scheduled_end) {
            return 0;
        }

        $departure = Carbon::createFromFormat('H:i', substr($attendance->departure_time, 0, 5));
        $scheduledEnd = Carbon::createFromFormat('H:i', substr($attendance->scheduled_end, 0, 5));

        return max(0, $departure->diffInMinutes($scheduledEnd, false));
    }

    protected function sanctionDescription(StaffAttendance $attendance): string
    {
        return match ($attendance->status) {
            'late' => "Retard de {$attendance->late_minutes} minute(s).",
            'absent' => $attendance->absence_justified ? 'Absence justifiée.' : 'Absence non justifiée.',
            'abandoned' => 'Abandon de poste constaté.',
            default => 'Incident disciplinaire.',
        };
    }

    protected function resetForm(): void
    {
        $this->reset(['employeeKey', 'actualStart', 'actualEnd', 'comment', 'abandonedPost', 'departureTime', 'abandonmentExplanation']);
        $this->status = 'present';
        $this->absenceJustified = false;
        $this->abandonmentSeverity = 'grave';
    }

    protected function employeeParts(string $key): array
    {
        $parts = explode(':', $key, 2);

        if (count($parts) !== 2 || ! in_array($parts[0], ['user', 'staff'], true) || ! ctype_digit($parts[1])) {
            throw ValidationException::withMessages(['employeeKey' => 'Employé invalide.']);
        }

        return [$parts[0], (int) $parts[1]];
    }

    protected function findEmployee(string $type, int $id): User|StaffMember|null
    {
        return $type === 'user' ? User::find($id) : StaffMember::find($id);
    }

    public function employees(): array
    {
        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'key' => "user:{$user->id}",
                'name' => $user->name,
                'job_title' => $user->job_title ?? $user->role->label(),
                'salary' => $user->monthly_salary,
            ]);

        $staff = StaffMember::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (StaffMember $staffMember) => [
                'key' => "staff:{$staffMember->id}",
                'name' => $staffMember->name,
                'job_title' => $staffMember->job_title ?? 'Personnel',
                'salary' => $staffMember->monthly_salary,
            ]);

        return $users->concat($staff)->values()->all();
    }

    public function statusLabel(string $status): string
    {
        return [
            'present' => 'Présent',
            'late' => 'Retard',
            'absent' => 'Absent',
            'abandoned' => 'Abandon',
            'rest' => 'Repos',
            'leave' => 'Congé',
        ][$status] ?? $status;
    }

    public function lateSeverity(int $minutes): string
    {
        $settings = HrSetting::current();

        if ($minutes < $settings->simple_late_threshold_minutes) {
            return 'Observation';
        }

        if ($minutes <= $settings->sanctionable_late_threshold_minutes) {
            return 'Avertissement possible';
        }

        return 'Sanction possible';
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.hr.attendance', [
            'settings' => HrSetting::current(),
            'attendances' => StaffAttendance::query()
                ->whereDate('work_date', $this->workDate)
                ->latest()
                ->get(),
        ]);
    }
}
