<?php

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;
use App\Services\RawMaterialStockService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Validate('required|exists:products,id')]
    public string $product_id = '';

    #[Validate('required|exists:raw_materials,id')]
    public string $raw_material_id = '';

    #[Validate('required|numeric|min:0.001')]
    public string $quantity = '';

    public ?string $notice = null;

    public function products()
    {
        return Product::with(['recipes.rawMaterial', 'category'])->orderBy('name')->get();
    }

    public function materials()
    {
        return RawMaterial::where('is_active', true)->orderBy('name')->get();
    }

    public function selectedProduct(): ?Product
    {
        if (! $this->product_id) {
            return $this->products()->first();
        }

        return Product::with(['recipes.rawMaterial', 'category'])->find($this->product_id);
    }

    public function save(): void
    {
        $this->validate();

        ProductRecipe::updateOrCreate([
            'product_id' => (int) $this->product_id,
            'raw_material_id' => (int) $this->raw_material_id,
        ], [
            'quantity' => (float) $this->quantity,
        ]);

        $this->reset(['raw_material_id', 'quantity']);
        $this->notice = 'Recette mise à jour.';
    }

    public function remove(int $id): void
    {
        ProductRecipe::findOrFail($id)->delete();
        $this->notice = 'Ligne supprimée.';
    }

    public function productCost(Product $product): int
    {
        return app(RawMaterialStockService::class)->recipeCost($product);
    }
}; ?>

<div class="py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Recettes par produit</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Matières consommées pour une unité vendue.</p>
        </div>

        @if ($notice)
            <div class="rounded-md bg-blue-50 dark:bg-blue-900 p-3 text-blue-800 dark:text-blue-100 text-sm">{{ $notice }}</div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
            <form wire:submit="save" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="product_id" value="Produit" />
                        <select wire:model.live="product_id" id="product_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                            <option value="">-- Choisir --</option>
                            @foreach ($this->products() as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} - {{ $product->service_area->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                    </div>
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
                        <x-input-label for="quantity" value="Quantité par vente" />
                        <x-text-input wire:model="quantity" id="quantity" class="block mt-1 w-full" type="number" min="0.001" step="0.001" required />
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>
                    <div class="flex items-end">
                        <x-primary-button>Ajouter à la recette</x-primary-button>
                    </div>
                </div>
            </form>
        </div>

        @php($selected = $this->selectedProduct())
        @if ($selected)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap justify-between gap-3">
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ $selected->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $selected->service_area->label() }} · Prix: {{ number_format($selected->price, 0, ',', ' ') }} FCFA
                        </p>
                    </div>
                    @php($cost = $this->productCost($selected))
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Coût matière: <span class="font-semibold">{{ number_format($cost, 0, ',', ' ') }} FCFA</span>
                        <span class="mx-2">|</span>
                        Marge estimée: <span class="font-semibold {{ $selected->price - $cost >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($selected->price - $cost, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-6 py-3">Matière</th>
                                <th class="px-6 py-3">Quantité</th>
                                <th class="px-6 py-3">Coût estimé</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($selected->recipes as $recipe)
                                <tr wire:key="recipe-{{ $recipe->id }}">
                                    <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $recipe->rawMaterial->name }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ number_format((float) $recipe->quantity, 3, ',', ' ') }} {{ \App\Models\RawMaterial::UNITS[$recipe->rawMaterial->unit] }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ number_format((float) $recipe->quantity * (float) $recipe->rawMaterial->average_unit_cost, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-6 py-3 text-right">
                                        <button x-on:click="$store.confirmModal.open('Supprimer cette matière de la recette ?', () => $wire.remove({{ $recipe->id }}))" class="inline-flex items-center rounded-md border border-red-200 dark:border-red-800 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-gray-700">Supprimer</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Aucune matière définie pour ce produit.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
