<?php

use App\Models\Category;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Validate('required|string|max:100')]
    public string $name = '';

    public ?int $editingId = null;

    public function categories()
    {
        return Category::withCount('products')->orderBy('name')->paginate(10);
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name']);
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $category = Category::findOrFail($this->editingId);
            $category->update(['name' => $this->name]);
        } else {
            Category::create(['name' => $this->name]);
        }

        $this->reset(['editingId', 'name']);
    }

    public function toggleActive(int $id): void
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function delete(int $id): void
    {
        Category::findOrFail($id)->delete();
    }
}; ?>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Catégories</h2>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form wire:submit="save" class="flex items-end gap-3">
                <div class="flex-1">
                    <x-input-label for="name" value="Nom de la catégorie" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <x-primary-button>{{ $editingId ? 'Mettre à jour' : 'Ajouter' }}</x-primary-button>
                @if ($editingId)
                    <button type="button" wire:click="cancelEdit" class="text-sm text-gray-600 dark:text-gray-400 underline">Annuler</button>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-3">Nom</th>
                        <th class="px-6 py-3">Produits</th>
                        <th class="px-6 py-3">Statut</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($this->categories() as $category)
                        <tr wire:key="category-{{ $category->id }}">
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $category->name }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $category->products_count }}</td>
                            <td class="px-6 py-3">
                                <button wire:click="toggleActive({{ $category->id }})" class="text-sm {{ $category->is_active ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-6 py-3 text-right space-x-3">
                                <button wire:click="edit({{ $category->id }})" class="text-sm text-brand-600 dark:text-brand-400">Modifier</button>
                                <button x-on:click="$store.confirmModal.open('Supprimer cette catégorie ?', () => $wire.delete({{ $category->id }}))" class="text-sm text-red-600 dark:text-red-400">Supprimer</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $this->categories()->links() }}
    </div>
</div>
