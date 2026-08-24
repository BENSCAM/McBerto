<?php

namespace App\Livewire\Hr;

use App\Models\DisciplinarySanction;
use App\Models\HrSetting;
use App\Models\StaffAttendance;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MonthlyReport extends Component
{
    public string $month;

    public string $plannedWorkingDays = '26';

    public string $plannedWorkingHours = '8';

    public string $simpleLateThreshold = '15';

    public string $sanctionableLateThreshold = '30';

    public ?string $notice = null;

    public function mount(): void
    {
        $this->month = today()->format('Y-m');
        $settings = HrSetting::current();
        $this->plannedWorkingDays = (string) $settings->planned_working_days_per_month;
        $this->plannedWorkingHours = (string) $settings->planned_working_hours_per_day;
        $this->simpleLateThreshold = (string) $settings->simple_late_threshold_minutes;
        $this->sanctionableLateThreshold = (string) $settings->sanctionable_late_threshold_minutes;
    }

    public function saveSettings(): void
    {
        $this->validate([
            'plannedWorkingDays' => ['required', 'integer', 'min:1', 'max:31'],
            'plannedWorkingHours' => ['required', 'integer', 'min:1', 'max:24'],
            'simpleLateThreshold' => ['required', 'integer', 'min:1', 'max:240'],
            'sanctionableLateThreshold' => ['required', 'integer', 'min:1', 'max:240', 'gte:simpleLateThreshold'],
        ]);

        HrSetting::current()->update([
            'planned_working_days_per_month' => (int) $this->plannedWorkingDays,
            'planned_working_hours_per_day' => (int) $this->plannedWorkingHours,
            'simple_late_threshold_minutes' => (int) $this->simpleLateThreshold,
            'sanctionable_late_threshold_minutes' => (int) $this->sanctionableLateThreshold,
        ]);

        $this->notice = 'Paramètres RH enregistrés.';
    }

    public function reportRows(): array
    {
        return collect($this->employees())
            ->map(function (array $employee) {
                $attendances = $this->attendancesFor($employee['type'], $employee['id']);
                $validatedSanctions = $this->sanctionsFor($employee['type'], $employee['id']);
                $deductions = (int) $validatedSanctions->sum('deduction_amount');
                $salary = (int) $employee['salary'];

                return [
                    ...$employee,
                    'present_days' => $attendances->whereIn('status', ['present', 'late'])->count(),
                    'late_count' => $attendances->where('status', 'late')->count(),
                    'late_minutes' => (int) $attendances->sum('late_minutes'),
                    'justified_absences' => $attendances->where('status', 'absent')->where('absence_justified', true)->count(),
                    'unjustified_absences' => $attendances->where('status', 'absent')->where('absence_justified', false)->count(),
                    'abandonments' => $attendances->where('status', 'abandoned')->count(),
                    'deductions' => $deductions,
                    'gross_salary' => $salary,
                    'net_salary' => max(0, $salary - $deductions),
                    'sanctions' => $validatedSanctions,
                ];
            })
            ->values()
            ->all();
    }

    public function dashboard(): array
    {
        $rows = collect($this->reportRows());

        return [
            'total_deductions' => (int) $rows->sum('deductions'),
            'late_total' => (int) $rows->sum('late_count'),
            'unjustified_absences' => (int) $rows->sum('unjustified_absences'),
            'abandonments' => (int) $rows->sum('abandonments'),
            'most_punctual' => $rows->where('late_count', 0)->where('unjustified_absences', 0)->sortByDesc('present_days')->take(3)->values(),
            'most_late' => $rows->sortByDesc('late_count')->take(3)->values(),
        ];
    }

    protected function employees(): array
    {
        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'type' => 'user',
                'id' => $user->id,
                'name' => $user->name,
                'job_title' => $user->job_title ?? $user->role->label(),
                'salary' => $user->monthly_salary,
            ]);

        $staff = StaffMember::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (StaffMember $staffMember) => [
                'type' => 'staff',
                'id' => $staffMember->id,
                'name' => $staffMember->name,
                'job_title' => $staffMember->job_title ?? 'Personnel',
                'salary' => $staffMember->monthly_salary,
            ]);

        return $users->concat($staff)->values()->all();
    }

    protected function period(): array
    {
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    protected function attendancesFor(string $type, int $id)
    {
        [$start, $end] = $this->period();

        return StaffAttendance::where('employee_type', $type)
            ->where('employee_id', $id)
            ->whereBetween('work_date', [$start, $end])
            ->get();
    }

    protected function sanctionsFor(string $type, int $id)
    {
        [$start, $end] = $this->period();

        return DisciplinarySanction::where('employee_type', $type)
            ->where('employee_id', $id)
            ->where('status', 'validated')
            ->whereBetween('fault_date', [$start, $end])
            ->latest('fault_date')
            ->get();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.hr.monthly-report', [
            'rows' => $this->reportRows(),
            'dashboard' => $this->dashboard(),
        ]);
    }
}
