<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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

    #[Validate('required|string|min:8')]
    public string $password = '';

    public ?string $error = null;

    public function users()
    {
        return User::orderBy('name')->paginate(10);
    }

    public function createUser(): void
    {
        $this->validate();

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'role' => UserRole::from($this->role),
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->reset(['name', 'email', 'role', 'password']);
        $this->role = 'cashier';
    }

    public function toggleActive(int $userId): void
    {
        $this->error = null;
        $target = User::findOrFail($userId);

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

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.admin.user-management');
    }
}
