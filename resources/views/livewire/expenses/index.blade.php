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

    #[Validate('required|in:charges,salaires,transport,loyer,electricite_eau,autre')]
    public string $category = '';

    #[Validate('nullable|string|max:255')]
    public string $description = '';

    #[Validate('required|integer|min:0')]
    public string $amount = '';

    #[Validate('required|date')]
    public string $expense_date = '';

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->expense_date = now()->format('Y-m-d');
    }

    public function expenses()
    {
        return Expense::with('user')->orderByDesc('expense_date')->orderByDesc('id')->paginate(10);
    }

    public function edit(int $id): void
    {
        $expense = Expense::findOrFail($id);

        $this->editingId = $expense->id;
        $this->category = $expense->category;
        $this->description = $expense->description ?? '';
        $this->amount = (string) $expense->amount;
        $this->expense_date = $expense->expense_date->format('Y-m-d');
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'category', 'description', 'amount']);
        $this->expense_date = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'category' => $this->category,
            'description' => $this->description ?: null,
            'amount' => (int) $this->amount,
            'expense_date' => $this->expense_date,
        ];

        if ($this->editingId) {
            Expense::findOrFail($this->editingId)->update($data);
        } else {
            Expense::create([
                ...$data,
                'user_id' => Auth::id(),
            ]);
        }

        $this->reset(['editingId', 'category', 'description', 'amount']);
        $this->expense_date = now()->format('Y-m-d');
    }

    public function delete(int $id): void
    {
        Expense::findOrFail($id)->delete();
    }
}; ?>

<div class="py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Dépenses</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Saisie des charges générales. Les achats de matières se font dans le module Achats.</p>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ $editingId ? 'Modifier la dépense' : 'Nouvelle dépense' }}</h3>
            </div>

            <form wire:submit="save" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                    <x-input-label for="category" value="Catégorie" />
                    <select wire:model="category" id="category" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                        <option value="">-- Choisir --</option>
                        @foreach (\App\Models\Expense::GENERAL_CATEGORIES as $value => $label)
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
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <x-text-input wire:model="description" id="description" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button>{{ $editingId ? 'Mettre à jour la dépense' : 'Ajouter la dépense' }}</x-primary-button>
                    @if ($editingId)
                        <button type="button" wire:click="cancelEdit" class="text-sm text-gray-600 dark:text-gray-400 underline">Annuler</button>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden" wire:poll.visible.15s>
            <div class="overflow-x-auto">
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
                    @forelse ($this->expenses() as $expense)
                        <tr wire:key="expense-{{ $expense->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $expense->expense_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ \App\Models\Expense::CATEGORIES[$expense->category] }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $expense->description }}</td>
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ number_format($expense->amount, 0, ',', ' ') }} FCFA</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $expense->user->name }}</td>
                            <td class="px-6 py-3 text-right space-x-3">
                                <button wire:click="edit({{ $expense->id }})" class="inline-flex items-center rounded-md border border-brand-200 dark:border-brand-800 px-2.5 py-1 text-xs font-medium text-brand-700 dark:text-brand-300 hover:bg-brand-50 dark:hover:bg-gray-700">Modifier</button>
                                <button x-on:click="$store.confirmModal.open('Supprimer cette dépense ?', () => $wire.delete({{ $expense->id }}))" class="inline-flex items-center rounded-md border border-red-200 dark:border-red-800 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-gray-700">Supprimer</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Aucune dépense enregistrée</div>
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ajoutez les sorties d'argent pour fiabiliser le rapport journalier.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{ $this->expenses()->links() }}
    </div>
</div>
