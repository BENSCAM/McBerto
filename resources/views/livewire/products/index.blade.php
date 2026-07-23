<?php

use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Validate('required|string|max:150')]
    public string $name = '';

    #[Validate('required|integer|min:0')]
    public string $price = '';

    #[Validate('required|exists:categories,id')]
    public string $category_id = '';

    public ?int $editingId = null;

    public function products()
    {
        return Product::with('category')->orderBy('name')->get();
    }

    public function categoryOptions()
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    public function edit(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->price = (string) $product->price;
        $this->category_id = (string) $product->category_id;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'price', 'category_id']);
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'price' => (int) $this->price,
            'category_id' => (int) $this->category_id,
        ];

        if ($this->editingId) {
            Product::findOrFail($this->editingId)->update($data);
        } else {
            Product::create($data);
        }

        $this->reset(['editingId', 'name', 'price', 'category_id']);
    }

    public function toggleActive(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
    }

    public function delete(int $id): void
    {
        Product::findOrFail($id)->delete();
    }
}; ?>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Produits</h2>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form wire:submit="save" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                <div class="sm:col-span-2">
                    <x-input-label for="name" value="Nom du produit" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="price" value="Prix (FCFA)" />
                    <x-text-input wire:model="price" id="price" class="block mt-1 w-full" type="number" min="0" required />
                    <x-input-error :messages="$errors->get('price')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="category_id" value="Catégorie" />
                    <select wire:model="category_id" id="category_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                        <option value="">-- Choisir --</option>
                        @foreach ($this->categoryOptions() as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                </div>
                <div class="sm:col-span-4 flex gap-3">
                    <x-primary-button>{{ $editingId ? 'Mettre à jour' : 'Ajouter' }}</x-primary-button>
                    @if ($editingId)
                        <button type="button" wire:click="cancelEdit" class="text-sm text-gray-600 dark:text-gray-400 underline">Annuler</button>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-3">Nom</th>
                        <th class="px-6 py-3">Catégorie</th>
                        <th class="px-6 py-3">Prix</th>
                        <th class="px-6 py-3">Statut</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($this->products() as $product)
                        <tr wire:key="product-{{ $product->id }}">
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $product->name }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $product->category?->name }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ number_format($product->price, 0, ',', ' ') }} FCFA</td>
                            <td class="px-6 py-3">
                                <button wire:click="toggleActive({{ $product->id }})" class="text-sm {{ $product->is_active ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $product->is_active ? 'Actif' : 'Inactif' }}
                                </button>
                            </td>
                            <td class="px-6 py-3 text-right space-x-3">
                                <button wire:click="edit({{ $product->id }})" class="text-sm text-indigo-600 dark:text-indigo-400">Modifier</button>
                                <button wire:click="delete({{ $product->id }})" wire:confirm="Supprimer ce produit ?" class="text-sm text-red-600 dark:text-red-400">Supprimer</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
