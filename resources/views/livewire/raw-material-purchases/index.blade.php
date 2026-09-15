<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Services\ChickenStockService;
use App\Services\RawMaterialStockService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Validate('required|exists:raw_materials,id')]
    public string $raw_material_id = '';

    #[Validate('required|numeric|min:0.001')]
    public string $quantity = '';

    #[Validate('required|integer|min:1')]
    public string $total_price = '';

    #[Validate('nullable|string|max:150')]
    public string $supplier = '';

    #[Validate('required|date')]
    public string $purchase_date = '';

    #[Validate('nullable|string|max:500')]
    public string $note = '';

    public ?string $notice = null;

    #[Validate('required|integer|min:1')]
    public string $chicken_count = '1';

    #[Validate('required|integer|min:1')]
    public string $chicken_total_price = '';

    #[Validate('nullable|string|max:150')]
    public string $chicken_supplier = '';

    #[Validate('required|date')]
    public string $chicken_purchase_date = '';

    #[Validate('nullable|string|max:500')]
    public string $chicken_note = '';

    public function mount(): void
    {
        $this->purchase_date = now()->toDateString();
        $this->chicken_purchase_date = now()->toDateString();
    }

    public function materials()
    {
        return RawMaterial::where('is_active', true)->orderBy('name')->get();
    }

    public function purchases()
    {
        return RawMaterialPurchase::with(['rawMaterial', 'user'])->orderByDesc('purchase_date')->orderByDesc('id')->paginate(12);
    }

    public function save(RawMaterialStockService $stockService): void
    {
        $this->validate();

        $stockService->recordPurchase([
            'raw_material_id' => (int) $this->raw_material_id,
            'quantity' => (float) $this->quantity,
            'total_price' => (int) $this->total_price,
            'supplier' => $this->supplier ?: null,
            'purchase_date' => $this->purchase_date,
            'note' => $this->note ?: null,
        ], Auth::user());

        $this->reset(['raw_material_id', 'quantity', 'total_price', 'supplier', 'note']);
        $this->purchase_date = now()->toDateString();
        $this->notice = 'Achat enregistré et stock mis à jour.';
    }

    public function recordChickenBatch(ChickenStockService $chickenStockService): void
    {
        $this->validateOnly('chicken_count');
        $this->validateOnly('chicken_total_price');
        $this->validateOnly('chicken_supplier');
        $this->validateOnly('chicken_purchase_date');
        $this->validateOnly('chicken_note');

        $count = (int) $this->chicken_count;

        $chickenStockService->recordBatch([
            'chickens' => $count,
            'total_price' => (int) $this->chicken_total_price,
            'supplier' => $this->chicken_supplier ?: null,
            'purchase_date' => $this->chicken_purchase_date,
            'note' => $this->chicken_note ?: null,
        ], Auth::user());

        $this->reset(['chicken_total_price', 'chicken_supplier', 'chicken_note']);
        $this->chicken_count = '1';
        $this->chicken_purchase_date = now()->toDateString();
        $this->notice = $count.' poulet(s) enregistré(s) : '.($count * 12).' morceaux à paner et '.($count * 15).' steaks ajoutés au stock.';
        $this->resetPage();
    }
}; ?>

<div class="py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Achats matières premières</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Chaque achat augmente le stock et recalcule le coût moyen.</p>
        </div>

        @if ($notice)
            <div class="rounded-md bg-blue-50 dark:bg-blue-900 p-3 text-blue-800 dark:text-blue-100 text-sm">{{ $notice }}</div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-amber-200 dark:border-amber-800">
            <div class="px-6 py-4 border-b border-amber-100 dark:border-amber-800">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">Approvisionnement poulet</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Chaque poulet ajoute automatiquement 12 morceaux à paner et 15 steaks de poulet.</p>
            </div>
            <form wire:submit="recordChickenBatch" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="chicken_count" value="Nombre de poulets" />
                        <x-text-input wire:model.live="chicken_count" id="chicken_count" class="block mt-1 w-full" type="number" min="1" step="1" required />
                        <x-input-error :messages="$errors->get('chicken_count')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="chicken_total_price" value="Coût total des poulets (FCFA)" />
                        <x-text-input wire:model="chicken_total_price" id="chicken_total_price" class="block mt-1 w-full" type="number" min="1" required />
                        <x-input-error :messages="$errors->get('chicken_total_price')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="chicken_purchase_date" value="Date" />
                        <x-text-input wire:model="chicken_purchase_date" id="chicken_purchase_date" class="block mt-1 w-full" type="date" required />
                        <x-input-error :messages="$errors->get('chicken_purchase_date')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="chicken_supplier" value="Fournisseur" />
                        <x-text-input wire:model="chicken_supplier" id="chicken_supplier" class="block mt-1 w-full" type="text" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                    <div>
                        <x-input-label for="chicken_note" value="Note" />
                        <x-text-input wire:model="chicken_note" id="chicken_note" class="block mt-1 w-full" type="text" />
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        Rendement prévu : <strong>{{ max(1, (int) $chicken_count) * 12 }} morceaux</strong> + <strong>{{ max(1, (int) $chicken_count) * 15 }} steaks</strong>
                    </div>
                </div>
                <x-primary-button>Enregistrer les poulets</x-primary-button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">Nouvel achat</h3>
            </div>
            <form wire:submit="save" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="raw_material_id" value="Matière" />
                        <select wire:model="raw_material_id" id="raw_material_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                            <option value="">-- Choisir --</option>
                            @foreach ($this->materials() as $material)
                                <option value="{{ $material->id }}">{{ $material->name }} ({{ \App\Models\RawMaterial::UNITS[$material->unit] }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('raw_material_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="quantity" value="Quantité achetée" />
                        <x-text-input wire:model="quantity" id="quantity" class="block mt-1 w-full" type="number" min="0.001" step="0.001" required />
                    </div>
                    <div>
                        <x-input-label for="total_price" value="Prix total (FCFA)" />
                        <x-text-input wire:model="total_price" id="total_price" class="block mt-1 w-full" type="number" min="1" required />
                    </div>
                    <div>
                        <x-input-label for="purchase_date" value="Date" />
                        <x-text-input wire:model="purchase_date" id="purchase_date" class="block mt-1 w-full" type="date" required />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="supplier" value="Fournisseur" />
                        <x-text-input wire:model="supplier" id="supplier" class="block mt-1 w-full" type="text" />
                    </div>
                    <div>
                        <x-input-label for="note" value="Note" />
                        <x-text-input wire:model="note" id="note" class="block mt-1 w-full" type="text" />
                    </div>
                </div>
                <x-primary-button>Enregistrer l'achat</x-primary-button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden" wire:poll.visible.15s>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Matière</th>
                            <th class="px-6 py-3">Quantité</th>
                            <th class="px-6 py-3">Prix total</th>
                            <th class="px-6 py-3">Prix unitaire</th>
                            <th class="px-6 py-3">Fournisseur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->purchases() as $purchase)
                            <tr wire:key="purchase-{{ $purchase->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $purchase->rawMaterial->name }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ number_format((float) $purchase->quantity, 3, ',', ' ') }}</td>
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ number_format($purchase->total_price, 0, ',', ' ') }} FCFA</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ number_format((float) $purchase->unit_price, 2, ',', ' ') }} FCFA</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $purchase->supplier }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Aucun achat enregistré.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $this->purchases()->links() }}
    </div>
</div>
