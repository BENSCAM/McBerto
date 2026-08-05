<?php

use App\Enums\ServiceArea;
use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Validate('required|string|max:150')]
    public string $name = '';

    #[Validate('nullable|string|max:8')]
    public string $emoji = '';

    #[Validate('required|integer|min:0')]
    public string $price = '';

    #[Validate('required|in:standard,vip')]
    public string $service_area = 'standard';

    #[Validate('required|exists:categories,id')]
    public string $category_id = '';

    public ?int $editingId = null;

    public ?string $notice = null;

    public function products()
    {
        return Product::with('category')->orderBy('service_area')->orderBy('name')->paginate(10);
    }

    public function categoryOptions()
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    public function emojiSuggestions(): array
    {
        return ['🍔', '🍟', '🍗', '🍕', '🌮', '🥪', '🍿', '🥤', '☕', '🧃', '🍦', '🍩', '🍪', '🥗', '🧀', '🍳'];
    }

    public function serviceAreaOptions(): array
    {
        return ServiceArea::cases();
    }

    public function pickEmoji(string $emoji): void
    {
        $this->emoji = $emoji;
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $product = Product::findOrFail($id);
        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->emoji = $product->emoji ?? '';
        $this->price = (string) $product->price;
        $this->service_area = $product->service_area->value;
        $this->category_id = (string) $product->category_id;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'emoji', 'price', 'service_area', 'category_id']);
    }

    public function save(): void
    {
        $this->resetErrorBag();
        $this->validate();
        $this->notice = null;

        $data = [
            'name' => $this->name,
            'emoji' => $this->emoji ?: null,
            'price' => (int) $this->price,
            'service_area' => $this->service_area,
            'category_id' => (int) $this->category_id,
        ];

        if ($this->editingId) {
            Product::findOrFail($this->editingId)->update($data);
            $this->notice = 'Produit mis à jour.';
        } else {
            Product::create($data);
            $this->notice = 'Produit ajouté.';
        }

        $this->reset(['editingId', 'name', 'emoji', 'price', 'service_area', 'category_id']);
        $this->dispatch('product-catalog-changed');
    }

    public function toggleActive(int $id): void
    {
        $this->notice = null;
        $product = Product::findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
        $this->notice = $product->is_active ? 'Produit activé.' : 'Produit désactivé.';
        $this->dispatch('product-catalog-changed');
    }

    public function delete(int $id): void
    {
        $product = Product::findOrFail($id);

        if ($product->saleItems()->exists()) {
            $product->update(['is_active' => false]);
            $this->notice = 'Ce produit a déjà été vendu. Il a été désactivé pour conserver l’historique des ventes.';
            $this->dispatch('product-catalog-changed');

            return;
        }

        $product->delete();
        $this->notice = 'Produit supprimé.';
        $this->dispatch('product-catalog-changed');
    }

    public function deactivateAll(): void
    {
        $this->notice = null;

        $count = Product::where('is_active', true)->count();

        Product::where('is_active', true)
            ->each(fn (Product $product) => $product->update(['is_active' => false]));

        $this->notice = $count > 0
            ? "{$count} produit(s) désactivé(s)."
            : 'Aucun produit actif à désactiver.';

        $this->dispatch('product-catalog-changed');
    }
}; ?>

<div class="py-8">
    <div
        x-data
        x-on:product-catalog-changed.window="
            Object.keys(localStorage)
                .filter((key) => key.startsWith('mcberto:offline-catalog:'))
                .forEach((key) => localStorage.removeItem(key))
        "
    ></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Produits</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Catalogue standard et VIP utilisé par la caisse.</p>
            </div>

            <button
                type="button"
                x-on:click="$store.confirmModal.open('Désactiver tous les produits ? Ils ne seront plus visibles à la caisse, mais resteront dans le catalogue pour être réactivés plus tard.', () => $wire.deactivateAll())"
                wire:loading.attr="disabled"
                wire:target="deactivateAll"
                class="inline-flex items-center rounded-md border border-red-200 dark:border-red-800 px-3 py-2 text-sm font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-gray-700 disabled:opacity-50"
            >
                Désactiver tous les produits
            </button>
        </div>

        @if ($notice)
            <div class="rounded-md bg-blue-50 dark:bg-blue-900 p-3 text-blue-800 dark:text-blue-100 text-sm">
                {{ $notice }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ $editingId ? 'Modifier le produit' : 'Créer un produit' }}</h3>
            </div>

            <form wire:submit="save" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-[1fr_120px] gap-4">
                    <div>
                    <x-input-label for="name" value="Nom du produit" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                    <div>
                        <x-input-label for="emoji" value="Emoji" />
                        <x-text-input wire:model="emoji" id="emoji" class="block mt-1 w-full text-center text-lg" type="text" maxlength="8" placeholder="🍔" />
                        <x-input-error :messages="$errors->get('emoji')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="price" value="Prix (FCFA)" />
                        <x-text-input wire:model="price" id="price" class="block mt-1 w-full" type="number" min="0" required />
                        <x-input-error :messages="$errors->get('price')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="service_area" value="Zone" />
                        <select wire:model="service_area" id="service_area" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                            @foreach ($this->serviceAreaOptions() as $serviceArea)
                                <option value="{{ $serviceArea->value }}">{{ $serviceArea->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('service_area')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="category_id" value="Catégorie" />
                        <select wire:model="category_id" id="category_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm mt-1 block w-full">
                            <option value="">-- Choisir --</option>
                            @foreach ($this->categoryOptions() as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Suggestions rapides</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->emojiSuggestions() as $suggestion)
                            <button type="button" wire:click="pickEmoji('{{ $suggestion }}')" class="text-lg leading-none w-9 h-9 flex items-center justify-center rounded-md border {{ $emoji === $suggestion ? 'border-brand-500 bg-brand-50 dark:bg-gray-700' : 'border-gray-200 dark:border-gray-700 hover:border-brand-400' }}">
                                {{ $suggestion }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <x-primary-button>{{ $editingId ? 'Mettre à jour le produit' : 'Ajouter le produit' }}</x-primary-button>
                    @if ($editingId)
                        <button type="button" wire:click="cancelEdit" class="text-sm text-gray-600 dark:text-gray-400 underline">Annuler</button>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-3">Nom</th>
                        <th class="px-6 py-3">Catégorie</th>
                        <th class="px-6 py-3">Zone</th>
                        <th class="px-6 py-3">Prix</th>
                        <th class="px-6 py-3">Statut</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->products() as $product)
                        <tr wire:key="product-{{ $product->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100">
                                <span class="text-lg mr-1">{{ $product->emoji }}</span>{{ $product->name }}
                            </td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $product->category?->name }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $product->service_area === \App\Enums\ServiceArea::Vip ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">{{ $product->service_area->label() }}</span>
                            </td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ number_format($product->price, 0, ',', ' ') }} FCFA</td>
                            <td class="px-6 py-3">
                                <button wire:click="toggleActive({{ $product->id }})" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $product->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $product->is_active ? 'Actif' : 'Inactif' }}
                                </button>
                            </td>
                            <td class="px-6 py-3 text-right space-x-3">
                                <button wire:click="edit({{ $product->id }})" class="inline-flex items-center rounded-md border border-brand-200 dark:border-brand-800 px-2.5 py-1 text-xs font-medium text-brand-700 dark:text-brand-300 hover:bg-brand-50 dark:hover:bg-gray-700">Modifier</button>
                                <button x-on:click="$store.confirmModal.open('Supprimer ce produit ? S’il a déjà été vendu, il sera désactivé pour préserver l’historique.', () => $wire.delete({{ $product->id }}))" class="inline-flex items-center rounded-md border border-red-200 dark:border-red-800 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-gray-700">Supprimer</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Aucun produit enregistré</div>
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ajoutez un produit standard ou VIP pour alimenter la caisse.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{ $this->products()->links() }}
    </div>
</div>
