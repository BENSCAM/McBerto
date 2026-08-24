<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialStockMovement;
use App\Services\RawMaterialStockService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $raw_material_id = '';

    public string $type = '';

    #[Validate('required|exists:raw_materials,id')]
    public string $adjust_raw_material_id = '';

    #[Validate('required|in:adjustment,loss,inventory_correction')]
    public string $adjust_type = 'adjustment';

    #[Validate('required|numeric|min:0')]
    public string $adjust_quantity = '';

    #[Validate('nullable|string|max:255')]
    public string $adjust_reason = '';

    public ?string $notice = null;

    public function movements()
    {
        return RawMaterialStockMovement::with(['rawMaterial', 'user', 'sale', 'product'])
            ->when($this->raw_material_id !== '', fn ($query) => $query->where('raw_material_id', $this->raw_material_id))
            ->when($this->type !== '', fn ($query) => $query->where('type', $this->type))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function materials()
    {
        return RawMaterial::orderBy('name')->get();
    }

    public function recordAdjustment(RawMaterialStockService $stockService): void
    {
        $this->validate();

        $stockService->recordManualMovement([
            'raw_material_id' => (int) $this->adjust_raw_material_id,
            'type' => $this->adjust_type,
            'quantity' => (float) $this->adjust_quantity,
            'reason' => $this->adjust_reason ?: null,
        ], Auth::user());

        $this->reset(['adjust_raw_material_id', 'adjust_quantity', 'adjust_reason']);
        $this->adjust_type = 'adjustment';
        $this->notice = 'Mouvement manuel enregistré.';
        $this->resetPage();
    }
}; ?>

<div class="py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Mouvements de stock</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Historique des achats, consommations et restaurations de stock.</p>
        </div>

        @if ($notice)
            <div class="rounded-md bg-blue-50 dark:bg-blue-900 p-3 text-blue-800 dark:text-blue-100 text-sm">{{ $notice }}</div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">Ajustement manuel</h3>
            </div>
            <form wire:submit="recordAdjustment" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="adjust_raw_material_id" value="Matière" />
                        <select wire:model="adjust_raw_material_id" id="adjust_raw_material_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                            <option value="">-- Choisir --</option>
                            @foreach ($this->materials() as $material)
                                <option value="{{ $material->id }}">{{ $material->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('adjust_raw_material_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="adjust_type" value="Type" />
                        <select wire:model="adjust_type" id="adjust_type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                            <option value="adjustment">Entrée manuelle</option>
                            <option value="loss">Perte / casse</option>
                            <option value="inventory_correction">Correction inventaire</option>
                        </select>
                        <x-input-error :messages="$errors->get('adjust_type')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="adjust_quantity" value="Quantité" />
                        <x-text-input wire:model="adjust_quantity" id="adjust_quantity" class="block mt-1 w-full" type="number" min="0" step="0.001" required />
                        <x-input-error :messages="$errors->get('adjust_quantity')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="adjust_reason" value="Raison" />
                        <x-text-input wire:model="adjust_reason" id="adjust_reason" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('adjust_reason')" class="mt-2" />
                    </div>
                </div>
                <x-primary-button>Enregistrer</x-primary-button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <select wire:model.live="raw_material_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                    <option value="">Toutes les matières</option>
                    @foreach ($this->materials() as $material)
                        <option value="{{ $material->id }}">{{ $material->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                    <option value="">Tous les mouvements</option>
                    @foreach (\App\Models\RawMaterialStockMovement::TYPES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Matière</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Entrée</th>
                            <th class="px-6 py-3">Sortie</th>
                            <th class="px-6 py-3">Stock après</th>
                            <th class="px-6 py-3">Valeur</th>
                            <th class="px-6 py-3">Raison</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->movements() as $movement)
                            <tr wire:key="movement-{{ $movement->id }}">
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $movement->occurred_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $movement->rawMaterial->name }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ \App\Models\RawMaterialStockMovement::TYPES[$movement->type] ?? $movement->type }}</td>
                                <td class="px-6 py-3 text-green-700 dark:text-green-300">{{ (float) $movement->quantity_in > 0 ? number_format((float) $movement->quantity_in, 3, ',', ' ') : '-' }}</td>
                                <td class="px-6 py-3 text-red-700 dark:text-red-300">{{ (float) $movement->quantity_out > 0 ? number_format((float) $movement->quantity_out, 3, ',', ' ') : '-' }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ number_format((float) $movement->stock_after, 3, ',', ' ') }}</td>
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ number_format($movement->total_cost, 0, ',', ' ') }} FCFA</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $movement->reason }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Aucun mouvement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $this->movements()->links() }}
    </div>
</div>
