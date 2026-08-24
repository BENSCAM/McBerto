<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\CashRegisterClosing;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('required|in:cashier,manager,owner')]
    public string $role = 'cashier';

    #[Validate('nullable|string|max:100')]
    public string $job_title = '';

    #[Validate('required|integer|min:0')]
    public string $monthly_salary = '0';

    #[Validate('required|string|min:8')]
    public string $password = '';

    public ?string $error = null;

    /** @var array<int, string> */
    public array $backdateSaleDates = [];

    /** @var array<int, array{job_title: string, monthly_salary: string}> */
    public array $employment = [];

    public function users()
    {
        return User::query()
            ->orderBy('name')
            ->paginate(10);
    }

    public function createUser(): void
    {
        $this->validate();

        if (Auth::user()->isManager() && $this->role !== UserRole::Cashier->value) {
            throw ValidationException::withMessages([
                'role' => 'Le gérant peut créer uniquement des comptes caissiers.',
            ]);
        }

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'role' => UserRole::from($this->role),
            'job_title' => $this->job_title ?: null,
            'monthly_salary' => (int) $this->monthly_salary,
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->reset(['name', 'email', 'role', 'job_title', 'monthly_salary', 'password']);
        $this->role = 'cashier';
        $this->monthly_salary = '0';
    }

    public function canManageUser(User $target): bool
    {
        return Auth::user()->isOwner() || (Auth::user()->isManager() && $target->isCashier());
    }

    public function canManageEmployment(User $target): bool
    {
        return Auth::user()->isAtLeastManager();
    }

    public function toggleActive(int $userId): void
    {
        $this->error = null;
        $target = User::findOrFail($userId);

        if (! $this->canManageUser($target)) {
            $this->error = 'Le gérant peut gérer uniquement les comptes caissiers.';

            return;
        }

        if ($target->id === Auth::id()) {
            $this->error = 'Vous ne pouvez pas désactiver votre propre compte.';

            return;
        }

        if ($target->is_active && $target->isOwner()) {
            $remainingActiveOwners = User::where('role', UserRole::Owner)
                ->where('is_active', true)
                ->where('id', '!=', $target->id)
                ->count();

            if ($remainingActiveOwners === 0) {
                $this->error = 'Impossible de désactiver le dernier propriétaire actif.';

                return;
            }
        }

        $target->update(['is_active' => ! $target->is_active]);
    }

    public function editEmployment(int $userId): void
    {
        $this->error = null;
        $target = User::findOrFail($userId);

        if (! $this->canManageEmployment($target)) {
            $this->error = 'Vous n’avez pas l’autorisation de modifier ces informations comptables.';

            return;
        }

        $this->employment[$target->id] = [
            'job_title' => $target->job_title ?? '',
            'monthly_salary' => (string) $target->monthly_salary,
        ];
    }

    public function saveEmployment(int $userId): void
    {
        $this->error = null;
        $target = User::findOrFail($userId);

        if (! $this->canManageEmployment($target)) {
            $this->error = 'Vous n’avez pas l’autorisation de modifier ces informations comptables.';

            return;
        }

        $this->validate([
            "employment.{$userId}.job_title" => ['nullable', 'string', 'max:100'],
            "employment.{$userId}.monthly_salary" => ['required', 'integer', 'min:0'],
        ], [
            "employment.{$userId}.monthly_salary.required" => 'Le salaire mensuel est obligatoire.',
            "employment.{$userId}.monthly_salary.integer" => 'Le salaire mensuel doit être un nombre entier.',
            "employment.{$userId}.monthly_salary.min" => 'Le salaire mensuel ne peut pas être négatif.',
        ]);

        $target->update([
            'job_title' => $this->employment[$userId]['job_title'] ?: null,
            'monthly_salary' => (int) $this->employment[$userId]['monthly_salary'],
        ]);

        unset($this->employment[$userId]);
    }

    public function cancelEmploymentEdit(int $userId): void
    {
        unset($this->employment[$userId]);
    }

    public function authorizeBackdatedSales(int $userId): void
    {
        $this->error = null;
        $target = User::findOrFail($userId);

        if (! $this->canManageUser($target)) {
            $this->error = 'Le gérant peut gérer uniquement les comptes caissiers.';

            return;
        }

        if (! $target->isCashier()) {
            $this->error = 'Cette autorisation est réservée aux comptes caissiers.';

            return;
        }

        $dateInput = trim($this->backdateSaleDates[$userId] ?? '');

        try {
            $date = Carbon::createFromFormat('Y-m-d', $dateInput)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                "backdateSaleDates.{$userId}" => 'Choisissez une date valide.',
            ]);
        }

        if ($date->format('Y-m-d') !== $dateInput) {
            throw ValidationException::withMessages([
                "backdateSaleDates.{$userId}" => 'Choisissez une date valide.',
            ]);
        }

        if ($date->gt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                "backdateSaleDates.{$userId}" => 'La date ne peut pas être dans le futur.',
            ]);
        }

        if (CashRegisterClosing::whereDate('closing_date', $date)->exists()) {
            throw ValidationException::withMessages([
                "backdateSaleDates.{$userId}" => 'Cette journée est déjà clôturée.',
            ]);
        }

        $target->update([
            'can_backdate_sales' => true,
            'backdate_sales_date' => $date,
        ]);
    }

    public function revokeBackdatedSales(int $userId): void
    {
        $this->error = null;
        $target = User::findOrFail($userId);

        if (! $this->canManageUser($target)) {
            $this->error = 'Le gérant peut gérer uniquement les comptes caissiers.';

            return;
        }

        $target->update([
            'can_backdate_sales' => false,
            'backdate_sales_date' => null,
        ]);

        unset($this->backdateSaleDates[$userId]);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.admin.user-management');
    }
}
