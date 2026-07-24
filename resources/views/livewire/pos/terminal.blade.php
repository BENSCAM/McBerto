<div class="py-6">
    <div wire:loading.class="opacity-100" class="fixed top-0 left-0 right-0 h-1 bg-brand-600 z-50 opacity-0 transition-opacity duration-150"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mb-6">Caisse</h2>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Categories & products -->
            <div class="lg:col-span-2 space-y-4">
                <div class="flex gap-2 overflow-x-auto pb-2">
                    @foreach ($this->categoriesWithProducts as $category)
                        <button
                            wire:click="selectCategory({{ $category->id }})"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-wait"
                            wire:target="selectCategory({{ $category->id }})"
                            class="shrink-0 px-4 py-2 rounded-md text-sm font-medium {{ $activeCategoryId === $category->id ? 'bg-brand-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700' }}"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach ($this->categoriesWithProducts as $category)
                        @if ($category->id === $activeCategoryId)
                            @foreach ($category->products as $product)
                                <button
                                    wire:click="addToCart({{ $product->id }})"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-wait"
                                    wire:target="addToCart({{ $product->id }})"
                                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-left hover:border-brand-500 transition"
                                >
                                    <div class="text-2xl mb-1">{{ $product->emoji }}</div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $product->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($product->price, 0, ',', ' ') }} FCFA</div>
                                </button>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Cart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex flex-col h-fit">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200">Panier</h3>
                    @if (! empty($cart))
                        <button
                            wire:click="clearCart"
                            wire:confirm="Vider le panier ?"
                            wire:loading.attr="disabled"
                            wire:target="clearCart"
                            class="text-xs text-red-500 dark:text-red-400 underline"
                        >Vider</button>
                    @endif
                </div>

                @if (empty($cart))
                    <p class="text-sm text-gray-500 dark:text-gray-400">Panier vide. Sélectionnez un produit.</p>
                @else
                    <div class="space-y-3 mb-4">
                        @foreach ($cart as $productId => $item)
                            <div class="flex items-center justify-between gap-2" wire:key="cart-{{ $productId }}">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        <span class="mr-1">{{ $item['emoji'] ?? '' }}</span>{{ $item['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($item['price'], 0, ',', ' ') }} FCFA</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        wire:click="decrementQty({{ $productId }})"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-wait"
                                        wire:target="decrementQty({{ $productId }})"
                                        class="w-6 h-6 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                    >−</button>
                                    <input
                                        type="number"
                                        min="1"
                                        max="999"
                                        value="{{ $item['quantity'] }}"
                                        wire:change="setQuantity({{ $productId }}, $event.target.value)"
                                        wire:loading.attr="disabled"
                                        wire:target="setQuantity"
                                        class="w-12 text-center text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md py-1"
                                    >
                                    <button
                                        wire:click="incrementQty({{ $productId }})"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-wait"
                                        wire:target="incrementQty({{ $productId }})"
                                        class="w-6 h-6 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                    >+</button>
                                </div>
                                <button wire:click="removeFromCart({{ $productId }})" class="text-red-500 text-xs">✕</button>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between font-semibold text-gray-900 dark:text-gray-100">
                        <span>Total</span>
                        <span>{{ number_format($this->cartTotal, 0, ',', ' ') }} FCFA</span>
                    </div>

                    <button wire:click="openCheckout" class="mt-4 w-full bg-brand-600 text-white rounded-md py-2 font-medium">
                        Encaisser
                    </button>
                @endif
            </div>
        </div>

        @if ($showCheckout)
            <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="closeCheckout">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-sm">
                    <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-4">Mode de paiement</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">Total : {{ number_format($this->cartTotal, 0, ',', ' ') }} FCFA</p>

                    <div class="space-y-2">
                        @foreach (\App\Enums\PaymentMethod::cases() as $method)
                            <button
                                wire:click="completeSale('{{ $method->value }}')"
                                wire:confirm="Confirmer l'encaissement de {{ number_format($this->cartTotal, 0, ',', ' ') }} FCFA en {{ $method->label() }} ?"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-wait"
                                wire:target="completeSale"
                                class="w-full border border-gray-200 dark:border-gray-700 rounded-md py-2 text-gray-800 dark:text-gray-200 hover:border-brand-500 disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="completeSale">{{ $method->label() }}</span>
                                <span wire:loading wire:target="completeSale">Traitement…</span>
                            </button>
                        @endforeach
                    </div>

                    <button
                        wire:click="closeCheckout"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-wait"
                        wire:target="completeSale"
                        class="mt-4 text-sm text-gray-500 dark:text-gray-400 underline"
                    >Annuler</button>
                </div>
            </div>
        @endif

        @if ($lastSaleReceipt)
            <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="closeReceipt">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-sm">
                    <div id="receipt-print" class="font-mono">
                        <div class="text-center mb-4">
                            <div class="no-print text-green-600 text-2xl mb-1">✓</div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100">McBerto</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Vente #{{ $lastSaleReceipt['id'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $lastSaleReceipt['created_at']->format('d/m/Y H:i') }} — {{ $lastSaleReceipt['cashier'] }}</p>
                        </div>

                        <div class="divide-y divide-gray-200 dark:divide-gray-700 border-y border-gray-200 dark:border-gray-700 mb-4">
                            @foreach ($lastSaleReceipt['items'] as $item)
                                <div class="flex justify-between py-2 text-sm">
                                    <span class="text-gray-700 dark:text-gray-300">
                                        <span class="mr-1">{{ $item['emoji'] ?? '' }}</span>{{ $item['quantity'] }}× {{ $item['name'] }}
                                    </span>
                                    <span class="text-gray-900 dark:text-gray-100">{{ number_format($item['price'] * $item['quantity'], 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-100 mb-1">
                            <span>Total</span>
                            <span>{{ number_format($lastSaleReceipt['total'], 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-4">
                            <span>Paiement</span>
                            <span>{{ $lastSaleReceipt['payment_method']->label() }}</span>
                        </div>

                        <p class="text-center text-xs text-gray-500 dark:text-gray-400 mb-4">Merci de votre visite !</p>
                    </div>

                    <div class="no-print flex gap-2">
                        <button onclick="window.print()" type="button" class="flex-1 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 rounded-md py-2 font-medium">
                            Imprimer
                        </button>
                        <button wire:click="closeReceipt" class="flex-1 bg-brand-600 text-white rounded-md py-2 font-medium">
                            Nouvelle vente
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #receipt-print, #receipt-print * {
                visibility: visible;
            }
            #receipt-print {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</div>
