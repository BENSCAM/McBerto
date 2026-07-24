<?php

use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Validate('required|in:matieres_premieres,charges,salaires,autre')]
    public string $category = '';

    #[Validate('nullable|string|max:255')]
    public string $description = '';

    #[Validate('required|integer|min:0')]
    public string $amount = '';

    #[Validate('required|date')]
    public string $expense_date = '';

    public function mount(): void
    {
        $this->expense_date = now()->format('Y-m-d');
    }

    public function expenses()
    {
        return Expense::with('user')->orderByDesc('expense_date')->orderByDesc('id')->paginate(10);
    }

    public function save(): void
    {
        $this->validate();

        Expense::create([
            'user_id' => Auth::id(),
            'category' => $this->category,
            'description' => $this->description ?: null,
            'amount' => (int) $this->amount,
            'expense_date' => $this->expense_date,
        ]);

        $this->reset(['category', 'description', 'amount']);
        $this->expense_date = now()->format('Y-m-d');
    }

    public function delete(int $id): void
    {
        Expense::findOrFail($id)->delete();
    }
}; ?>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Dépenses</h2>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form wire:submit="save" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                <div>
                    <x-input-label for="category" value="Catégorie" />
                    <select wire:model="category" id="category" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                        <option value="">-- Choisir --</option>
                        @foreach (\App\Models\Expense::CATEGORIES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="amount" value="Montant (FCFA)" />
                    <x-text-input wire:model="amount" id="amount" class="block mt-1 w-full" type="number" min="0" required />
                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="expense_date" value="Date" />
                    <x-text-input wire:model="expense_date" id="expense_date" class="block mt-1 w-full" type="date" required />
                    <x-input-error :messages="$errors->get('expense_date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description" value="Description" />
                    <x-text-input wire:model="description" id="description" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
                <div class="sm:col-span-4">
                    <x-primary-button>Ajouter la dépense</x-primary-button>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Catégorie</th>
                        <th class="px-6 py-3">Description</th>
                        <th class="px-6 py-3">Montant</th>
                        <th class="px-6 py-3">Saisi par</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($this->expenses() as $expense)
                        <tr wire:key="expense-{{ $expense->id }}">
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $expense->expense_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ \App\Models\Expense::CATEGORIES[$expense->category] }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $expense->description }}</td>
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ number_format($expense->amount, 0, ',', ' ') }} FCFA</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $expense->user->name }}</td>
                            <td class="px-6 py-3 text-right">
                                <button wire:click="delete({{ $expense->id }})" wire:confirm="Supprimer cette dépense ?" class="text-sm text-red-600 dark:text-red-400">Supprimer</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $this->expenses()->links() }}
    </div>
</div>
