<?php

namespace App\Livewire\Hr;

use App\Models\DisciplinarySanction;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class DisciplineHistory extends Component
{
    use WithPagination;

    public string $employeeKey = '';

    public string $faultType = 'late';

    public string $description = '';

    public string $faultDate;

    public string $sanctionType = 'verbal_reminder';

    public string $deductionAmount = '0';

    public string $comment = '';

    public string $status = 'all';

    public ?string $notice = null;

    public function mount(): void
    {
        $this->faultDate = today()->toDateString();
    }

    public function createSanction(): void
    {
        $this->validate([
            'employeeKey' => ['required', 'string'],
            'faultType' => ['required', 'in:late,absence,abandonment,other'],
            'description' => ['required', 'string', 'max:1500'],
            'faultDate' => ['required', 'date'],
            'sanctionType' => ['required', 'in:verbal_reminder,written_warning,salary_deduction,suspension,last_warning,end_collaboration'],
            'deductionAmount' => ['required', 'integer', 'min:0'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        [$employeeType, $employeeId] = $this->employeeParts($this->employeeKey);

        if (! $this->findEmployee($employeeType, $employeeId)) {
            throw ValidationException::withMessages(['employeeKey' => 'Employé introuvable.']);
        }

        DisciplinarySanction::create([
            'employee_type' => $employeeType,
            'employee_id' => $employeeId,
            'fault_type' => $this->faultType,
            'description' => $this->description,
            'fault_date' => $this->faultDate,
            'sanction_type' => $this->sanctionType,
            'deduction_amount' => (int) $this->deductionAmount,
            'responsible_id' => Auth::id(),
            'status' => 'draft',
            'comment' => $this->comment ?: null,
        ]);

        $this->notice = 'Sanction créée en brouillon.';
        $this->reset(['employeeKey', 'description', 'comment']);
        $this->deductionAmount = '0';
        $this->faultType = 'late';
        $this->sanctionType = 'verbal_reminder';
        $this->faultDate = today()->toDateString();
    }

    public function validateSanction(int $sanctionId): void
    {
        DisciplinarySanction::findOrFail($sanctionId)->validateSanction(Auth::user());
        $this->notice = 'Sanction validée.';
    }

    public function cancelSanction(int $sanctionId): void
    {
        DisciplinarySanction::findOrFail($sanctionId)->cancelSanction(Auth::user(), 'Annulée depuis l’historique disciplinaire.');
        $this->notice = 'Sanction annulée.';
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
            ]);

        $staff = StaffMember::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (StaffMember $staffMember) => [
                'key' => "staff:{$staffMember->id}",
                'name' => $staffMember->name,
                'job_title' => $staffMember->job_title ?? 'Personnel',
            ]);

        return $users->concat($staff)->values()->all();
    }

    public function faultLabel(string $type): string
    {
        return [
            'late' => 'Retard',
            'absence' => 'Absence',
            'abandonment' => 'Abandon de poste',
            'other' => 'Autre',
        ][$type] ?? $type;
    }

    public function sanctionLabel(string $type): string
    {
        return [
            'verbal_reminder' => 'Rappel verbal',
            'written_warning' => 'Avertissement écrit',
            'salary_deduction' => 'Retenue sur salaire',
            'suspension' => 'Suspension',
            'last_warning' => 'Dernier avertissement',
            'end_collaboration' => 'Fin de collaboration',
        ][$type] ?? $type;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.hr.discipline-history', [
            'sanctions' => DisciplinarySanction::query()
                ->with('responsible')
                ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
                ->latest('fault_date')
                ->latest()
                ->paginate(12),
        ]);
    }
}
