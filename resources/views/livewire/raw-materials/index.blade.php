<?php

use App\Models\RawMaterial;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Validate('required|string|max:150')]
    public string $name = '';

    #[Validate('required|in:kg,g,litre,ml,piece,carton,paquet')]
    public string $unit = 'piece';

    #[Validate('required|numeric|min:0')]
    public string $current_quantity = '0';

    #[Validate('required|numeric|min:0')]
    public string $low_stock_threshold = '0';

    #[Validate('required|numeric|min:0')]
    public string $average_unit_cost = '0';

    public ?int $editingId = null;

    public ?string $notice = null;

    public function materials()
    {
        return RawMaterial::orderBy('name')->paginate(12);
    }

    public function lowStockCount(): int
    {
        return RawMaterial::whereColumn('current_quantity', '<=', 'low_stock_threshold')->count();
    }

    public function edit(int $id): void
    {
        $material = RawMaterial::findOrFail($id);
        $this->editingId = $material->id;
        $this->name = $material->name;
        $this->unit = $material->unit;
        $this->current_quantity = (string) $material->current_quantity;
        $this->low_stock_threshold = (string) $material->low_stock_threshold;
        $this->average_unit_cost = (string) $material->average_unit_cost;
        $this->notice = null;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name']);
        $this->unit = 'piece';
        $this->current_quantity = '0';
        $this->low_stock_threshold = '0';
        $this->average_unit_cost = '0';
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => trim($this->name),
            'unit' => $this->unit,
            'current_quantity' => (float) $this->current_quantity,
            'low_stock_threshold' => (float) $this->low_stock_threshold,
            'average_unit_cost' => (float) $this->average_unit_cost,
        ];

        if ($this->editingId) {
            RawMaterial::findOrFail($this->editingId)->update($data);
            $this->notice = 'Matière première mise à jour.';
        } else {
            RawMaterial::create($data);
            $this->notice = 'Matière première ajoutée.';
        }

        $this->cancelEdit();
    }

    public function toggleActive(int $id): void
    {
        $material = RawMaterial::findOrFail($id);
        $material->update(['is_active' => ! $material->is_active]);
        $this->notice = $material->is_active ? 'Matière activée.' : 'Matière désactivée.';
    }
}; ?>

<div class="py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Matières premières</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Stocks simples, coût moyen et seuil d'alerte.</p>
            </div>
            <div class="rounded-md border border-amber-200 dark:border-amber-800 px-3 py-2 text-sm text-amber-700 dark:text-amber-200">
                {{ $this->lowStockCount() }} alerte(s) stock bas
            </div>
        </div>

        @if ($notice)
            <div class="rounded-md bg-blue-50 dark:bg-blue-900 p-3 text-blue-800 dark:text-blue-100 text-sm">{{ $notice }}</div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ $editingId ? 'Modifier la matière' : 'Nouvelle matière' }}</h3>
            </div>
            <form wire:submit="save" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-2">
                        <x-input-label for="name" value="Nom" />
                        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="unit" value="Unité" />
                        <select wire:model="unit" id="unit" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                            @foreach (\App\Models\RawMaterial::UNITS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="current_quantity" value="Stock actuel" />
                        <x-text-input wire:model="current_quantity" id="current_quantity" class="block mt-1 w-full" type="number" min="0" step="0.001" required />
                    </div>
                    <div>
                        <x-input-label for="low_stock_threshold" value="Seuil bas" />
                        <x-text-input wire:model="low_stock_threshold" id="low_stock_threshold" class="block mt-1 w-full" type="number" min="0" step="0.001" required />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="average_unit_cost" value="Coût moyen unitaire (FCFA)" />
                        <x-text-input wire:model="average_unit_cost" id="average_unit_cost" class="block mt-1 w-full" type="number" min="0" step="0.0001" required />
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button>{{ $editingId ? 'Mettre à jour' : 'Ajouter' }}</x-primary-button>
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
                            <th class="px-6 py-3">Matière</th>
                            <th class="px-6 py-3">Stock</th>
                            <th class="px-6 py-3">Coût moyen</th>
                            <th class="px-6 py-3">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->materials() as $material)
                            <tr wire:key="raw-material-{{ $material->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $material->name }}</td>
                                <td class="px-6 py-3 {{ $material->isLowStock() ? 'text-amber-700 dark:text-amber-300 font-medium' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ number_format((float) $material->current_quantity, 3, ',', ' ') }} {{ \App\Models\RawMaterial::UNITS[$material->unit] }}
                                </td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ number_format((float) $material->average_unit_cost, 2, ',', ' ') }} FCFA</td>
                                <td class="px-6 py-3">
                                    <button wire:click="toggleActive({{ $material->id }})" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $material->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $material->is_active ? 'Actif' : 'Inactif' }}
                                    </button>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <button wire:click="edit({{ $material->id }})" class="inline-flex items-center rounded-md border border-brand-200 dark:border-brand-800 px-2.5 py-1 text-xs font-medium text-brand-700 dark:text-brand-300 hover:bg-brand-50 dark:hover:bg-gray-700">Modifier</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Aucune matière première.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $this->materials()->links() }}
    </div>
</div>
