<div
    class="py-6"
    x-data="offlinePos(@js($this->offlineCatalog()), '{{ route('pos.offline-sales.sync') }}')"
    x-init="init()"
>
    <div wire:loading.class="opacity-100" class="fixed top-0 left-0 right-0 h-1 bg-brand-600 z-50 opacity-0 transition-opacity duration-150"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 lg:pb-0">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Caisse</h2>

            @unless ($this->todayClosing)
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto" x-show="!offline">
                    <div class="inline-flex rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1">
                        @foreach ($this->serviceAreaOptions() as $serviceArea)
                            <button
                                type="button"
                                wire:click="selectServiceArea('{{ $serviceArea->value }}')"
                                wire:loading.attr="disabled"
                                wire:target="selectServiceArea"
                                class="px-3 py-1.5 rounded text-sm font-medium {{ $activeServiceArea === $serviceArea->value ? 'bg-brand-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                            >{{ $serviceArea->label() }}</button>
                        @endforeach
                    </div>

                    <div class="relative w-full sm:w-64">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Rechercher un produit…"
                            class="w-full pl-9 pr-3 py-2 text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500"
                        >
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔎</span>
                    </div>
                </div>
            @endunless
        </div>

        @if ($this->todayClosing)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-10 text-center max-w-lg mx-auto">
                <div class="text-4xl mb-3">🔒</div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Caisse clôturée pour aujourd'hui</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Clôturée par {{ $this->todayClosing->closedBy->name }} à {{ $this->todayClosing->created_at->format('H:i') }}.
                </p>

                @if (auth()->user()->isAtLeastManager())
                    <button
                        x-on:click="$store.confirmModal.open('Réouvrir la caisse ? Les caissiers pourront de nouveau encaisser des ventes aujourd\'hui.', () => $wire.reopenRegister())"
                        wire:loading.attr="disabled"
                        wire:target="reopenRegister"
                        class="mt-6 bg-brand-600 text-white rounded-md px-4 py-2 font-medium disabled:opacity-50"
                    >Réouvrir la caisse</button>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">Contactez votre gestionnaire ou le propriétaire pour la réouvrir.</p>
                @endif

                <div class="mt-6">
                    <a href="{{ route('pos.closing') }}" wire:navigate class="text-sm text-brand-600 dark:text-brand-400 underline">Voir le récapitulatif de clôture</a>
                </div>
            </div>
        @else
        <div x-show="offline" x-cloak class="space-y-4">
            <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/40 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-amber-900 dark:text-amber-100">Mode hors connexion</h3>
                        <p class="text-sm text-amber-800 dark:text-amber-100 mt-1">Les ventes sont gardées sur cet appareil et seront synchronisées dès que la connexion revient.</p>
                    </div>
                    <button
                        type="button"
                        x-show="online && pendingSales.length > 0"
                        x-on:click="syncPending()"
                        class="bg-brand-600 text-white rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                        :disabled="syncing"
                    >
                        <span x-show="!syncing">Synchroniser</span>
                        <span x-show="syncing">Synchronisation…</span>
                    </button>
                </div>
                <div class="mt-3 text-sm text-amber-900 dark:text-amber-100">
                    <span x-text="pendingSales.length"></span> vente(s) en attente.
                    <span x-show="lastSyncMessage" x-text="lastSyncMessage" class="ml-2"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <div class="inline-flex rounded-md border border-gray-200 dark:border-gray-700 p-1">
                            <template x-for="area in catalog.serviceAreas" :key="area.value">
                                <button
                                    type="button"
                                    x-on:click="serviceArea = area.value; offlineCart = {}"
                                    class="px-3 py-1.5 rounded text-sm font-medium"
                                    :class="serviceArea === area.value ? 'bg-brand-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                    x-text="area.label"
                                ></button>
                            </template>
                        </div>
                        <input
                            type="text"
                            x-model="offlineSearch"
                            placeholder="Rechercher un produit…"
                            class="flex-1 min-w-[220px] rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm"
                        >
                    </div>

                    <template x-if="filteredProducts().length === 0">
                        <div class="text-center py-12 text-sm text-gray-500 dark:text-gray-400 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                            Aucun produit disponible hors connexion pour cette recherche.
                        </div>
                    </template>

                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3">
                        <template x-for="product in filteredProducts()" :key="product.id">
                            <button
                                type="button"
                                x-on:click="addOfflineProduct(product)"
                                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-left hover:border-brand-500 hover:shadow-md transition"
                            >
                                <div class="text-3xl mb-2" x-text="product.emoji || '•'"></div>
                                <div class="font-medium text-gray-900 dark:text-gray-100 leading-snug" x-text="product.name"></div>
                                <div class="text-sm text-brand-600 dark:text-brand-400 font-semibold mt-1" x-text="formatMoney(product.price)"></div>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">Panier hors ligne</h3>
                        <button type="button" x-show="offlineCartCount() > 0" x-on:click="offlineCart = {}" class="text-xs text-red-500 dark:text-red-400 underline">Vider</button>
                    </div>

                    <template x-if="offlineCartCount() === 0">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Panier vide. Sélectionnez un produit.</p>
                    </template>

                    <div class="space-y-3 mb-4">
                        <template x-for="item in offlineCartItems()" :key="item.product_id">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="`${item.emoji || ''} ${item.product_name}`"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="formatMoney(item.unit_price)"></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" x-on:click="decrementOffline(item.product_id)" class="w-6 h-6 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">−</button>
                                    <span class="w-8 text-center text-sm text-gray-800 dark:text-gray-200" x-text="item.quantity"></span>
                                    <button type="button" x-on:click="incrementOffline(item.product_id)" class="w-6 h-6 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">+</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 space-y-3">
                        <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-100">
                            <span>Total</span>
                            <span x-text="formatMoney(offlineCartTotal())"></span>
                        </div>

                        <select x-model="offlinePaymentMethod" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <template x-for="method in catalog.paymentMethods" :key="method.value">
                                <option :value="method.value" x-text="method.label"></option>
                            </template>
                        </select>

                        <div x-show="offlinePaymentMethod === 'cash'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Montant donné</label>
                            <input type="number" min="0" x-model.number="offlineAmountGiven" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                            <p class="mt-1 text-sm" :class="offlineChangeDue() < 0 ? 'text-red-600' : 'text-green-600'" x-text="`Monnaie : ${formatMoney(offlineChangeDue())}`"></p>
                        </div>

                        <button
                            type="button"
                            x-on:click="queueOfflineSale()"
                            :disabled="offlineCartCount() === 0 || (offlinePaymentMethod === 'cash' && offlineChangeDue() < 0)"
                            class="w-full bg-brand-600 text-white rounded-md py-2 font-medium disabled:opacity-50"
                        >Enregistrer hors ligne</button>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="!offline" class="grid grid-cols-1 lg:grid-cols-[200px_1fr_360px] gap-4 lg:h-[calc(100vh-15rem)]">
            <!-- Categories -->
            <div class="min-w-0 flex lg:flex-col gap-2 overflow-x-auto lg:overflow-y-auto lg:h-full lg:min-h-0 pb-1 lg:pb-0">
                @foreach ($this->categoriesWithProducts as $category)
                    <button
                        wire:click="selectCategory({{ $category->id }})"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-wait"
                        wire:target="selectCategory({{ $category->id }})"
                        class="shrink-0 lg:w-full flex items-center justify-between gap-2 px-4 py-2.5 rounded-md text-sm font-medium text-left transition {{ $activeCategoryId === $category->id && trim($search) === '' ? 'bg-brand-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-brand-400' }}"
                    >
                        <span>{{ $category->name }}</span>
                        <span class="text-xs {{ $activeCategoryId === $category->id && trim($search) === '' ? 'text-white/80' : 'text-gray-400 dark:text-gray-500' }}">{{ $category->products->count() }}</span>
                    </button>
                @endforeach
            </div>

            <!-- Products -->
            <div class="min-w-0 lg:h-full lg:min-h-0 lg:overflow-y-auto lg:pr-1">
                @if ($this->visibleProducts->isEmpty())
                    <div class="flex flex-col items-center justify-center text-center py-16 text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 rounded-lg border border-dashed border-gray-200 dark:border-gray-700">
                        <span class="text-3xl mb-2">🔎</span>
                        <p class="text-sm">
                            @if (trim($search) !== '')
                                Aucun produit ne correspond à « {{ $search }} ».
                            @else
                                Aucun produit dans cette catégorie.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                        @foreach ($this->visibleProducts as $product)
                            <button
                                wire:click="addToCart({{ $product->id }})"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-wait"
                                wire:target="addToCart({{ $product->id }})"
                                wire:key="product-{{ $product->id }}"
                                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-left hover:border-brand-500 hover:shadow-md transition"
                            >
                                <div class="text-3xl mb-2">{{ $product->emoji }}</div>
                                <div class="font-medium text-gray-900 dark:text-gray-100 leading-snug">{{ $product->name }}</div>
                                <div class="text-sm text-brand-600 dark:text-brand-400 font-semibold mt-1">{{ number_format($product->price, 0, ',', ' ') }} FCFA</div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Cart + recent sales -->
            <div class="min-w-0 flex flex-col gap-4 lg:h-full lg:min-h-0">
                <div id="cart-section" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex flex-col lg:min-h-0 lg:max-h-[65%]">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">
                            Panier
                            @if (! empty($cart))
                                <span class="text-xs font-normal text-gray-400 dark:text-gray-500">({{ array_sum(array_column($cart, 'quantity')) }})</span>
                            @endif
                        </h3>
                        @if (! empty($cart))
                            <button
                                x-on:click="$store.confirmModal.open('Vider le panier ?', () => $wire.clearCart())"
                                wire:loading.attr="disabled"
                                wire:target="clearCart"
                                class="text-xs text-red-500 dark:text-red-400 underline"
                            >Vider</button>
                        @endif
                    </div>

                    @if (empty($cart))
                        <p class="text-sm text-gray-500 dark:text-gray-400">Panier vide. Sélectionnez un produit.</p>
                    @else
                        <div class="flex-1 min-h-[4rem] lg:min-h-0 overflow-y-auto space-y-3 mb-4 pr-1">
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

                <!-- Recent sales -->
                <div class="flex-1 min-h-[10rem] lg:min-h-0 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex flex-col">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-3 shrink-0">Ventes récentes du jour</h3>

                    @if ($this->recentSales->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">Aucune vente enregistrée aujourd'hui.</p>
                    @else
                        <div class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($this->recentSales as $sale)
                                <div class="py-2 text-sm" wire:key="recent-sale-{{ $sale->id }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ $sale->created_at->format('H:i') }}</span>
                                                <span class="text-gray-700 dark:text-gray-300 truncate">{{ $sale->receipt_number ?? 'Vente #'.$sale->id }}</span>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $sale->user->name }}</div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="text-xs font-medium px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $sale->service_area->label() }}</span>
                                            <span class="text-xs font-medium px-2 py-0.5 rounded {{ $sale->sale_status === \App\Enums\SaleStatus::Canceled ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-100' : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100' }}">{{ $sale->sale_status->label() }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between gap-2">
                                        <span class="text-gray-900 dark:text-gray-100 font-medium {{ $sale->sale_status === \App\Enums\SaleStatus::Canceled ? 'line-through text-gray-400 dark:text-gray-500' : '' }}">{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</span>
                                        @if ($sale->sale_status === \App\Enums\SaleStatus::Canceled)
                                            <span class="text-xs text-red-600 dark:text-red-300">Annulée par {{ $sale->canceledBy?->name }}</span>
                                        @elseif (! $sale->cash_register_closing_id && (auth()->user()->isAtLeastManager() || $sale->user_id === auth()->id()))
                                            <button
                                                x-on:click="$store.confirmModal.open('Annuler la vente {{ $sale->receipt_number ?? '#'.$sale->id }} ? Elle restera visible dans les historiques mais ne comptera plus dans les recettes.', () => $wire.cancelSale({{ $sale->id }}))"
                                                wire:loading.attr="disabled"
                                                wire:target="cancelSale"
                                                class="text-xs text-red-600 dark:text-red-300 underline"
                                            >Annuler</button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if (! empty($cart))
            <a
                href="#cart-section"
                class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-brand-600 text-white px-4 py-3 flex items-center justify-between shadow-lg"
            >
                <span class="text-sm font-medium">{{ array_sum(array_column($cart, 'quantity')) }} article(s)</span>
                <span class="font-semibold">{{ number_format($this->cartTotal, 0, ',', ' ') }} FCFA · Voir le panier</span>
            </a>
        @endif
        @endif

        @if ($showCheckout)
            <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="closeCheckout">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-sm">
                    @if ($checkoutMethod === 'cash')
                        <!-- Cash: amount given / change due -->
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-1">Paiement en espèces</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">{{ \App\Enums\ServiceArea::from($activeServiceArea)->label() }} · Total à payer : <span class="font-semibold">{{ number_format($this->cartTotal, 0, ',', ' ') }} FCFA</span></p>

                        <x-input-label for="amountGiven" value="Montant donné par le client" />
                        <x-text-input
                            wire:model.live="amountGiven"
                            id="amountGiven"
                            class="block mt-1 w-full text-lg"
                            type="number"
                            min="0"
                            placeholder="0"
                            autofocus
                        />

                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach ([500, 1000, 2000, 5000, 10000] as $note)
                                <button
                                    type="button"
                                    wire:click="setAmountGiven({{ $note }})"
                                    class="px-3 py-1 text-sm rounded-md border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-brand-400"
                                >{{ number_format($note, 0, ',', ' ') }}</button>
                            @endforeach
                            <button
                                type="button"
                                wire:click="setAmountGiven({{ $this->cartTotal }})"
                                class="px-3 py-1 text-sm rounded-md border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-brand-400"
                            >Montant exact</button>
                        </div>

                        <div class="mt-4 p-3 rounded-md {{ $this->changeDue === null ? 'bg-gray-50 dark:bg-gray-700' : ($this->changeDue < 0 ? 'bg-red-50 dark:bg-red-900' : 'bg-green-50 dark:bg-green-900') }}">
                            @if ($this->changeDue === null)
                                <p class="text-sm text-gray-500 dark:text-gray-400">Saisissez le montant reçu.</p>
                            @elseif ($this->changeDue < 0)
                                <p class="text-sm text-red-700 dark:text-red-200">Montant insuffisant : {{ number_format(abs($this->changeDue), 0, ',', ' ') }} FCFA manquant.</p>
                            @else
                                <p class="text-sm text-green-800 dark:text-green-200">Monnaie à rendre</p>
                                <p class="text-2xl font-semibold text-green-800 dark:text-green-200">{{ number_format($this->changeDue, 0, ',', ' ') }} FCFA</p>
                            @endif
                        </div>

                        <div class="flex gap-2 mt-4">
                            <button
                                wire:click="backToPaymentMethods"
                                class="flex-1 text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-md py-2"
                            >Retour</button>
                            <button
                                x-on:click="$store.confirmModal.open('Confirmer l\'encaissement de {{ number_format($this->cartTotal, 0, ',', ' ') }} FCFA en espèces ?', () => $wire.confirmCashSale())"
                                wire:loading.attr="disabled"
                                wire:target="confirmCashSale"
                                @disabled($this->changeDue === null || $this->changeDue < 0)
                                class="flex-1 bg-brand-600 text-white rounded-md py-2 font-medium disabled:opacity-50"
                            >Confirmer</button>
                        </div>
                    @else
                        <!-- Payment method selection -->
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-4">Mode de paiement</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">{{ \App\Enums\ServiceArea::from($activeServiceArea)->label() }} · Total : {{ number_format($this->cartTotal, 0, ',', ' ') }} FCFA</p>

                        <div class="space-y-2">
                            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                @if ($method === \App\Enums\PaymentMethod::Cash)
                                    <button
                                        wire:click="selectPaymentMethod('{{ $method->value }}')"
                                        class="w-full border border-gray-200 dark:border-gray-700 rounded-md py-2 text-gray-800 dark:text-gray-200 hover:border-brand-500"
                                    >{{ $method->label() }}</button>
                                @else
                                    <button
                                        x-on:click="$store.confirmModal.open('Confirmer l\'encaissement de {{ number_format($this->cartTotal, 0, ',', ' ') }} FCFA en {{ $method->label() }} ?', () => $wire.completeSale('{{ $method->value }}'))"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-wait"
                                        wire:target="completeSale"
                                        class="w-full border border-gray-200 dark:border-gray-700 rounded-md py-2 text-gray-800 dark:text-gray-200 hover:border-brand-500 disabled:opacity-50"
                                    >
                                        <span wire:loading.remove wire:target="completeSale">{{ $method->label() }}</span>
                                        <span wire:loading wire:target="completeSale">Traitement…</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>

                        <button
                            wire:click="closeCheckout"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-wait"
                            wire:target="completeSale"
                            class="mt-4 text-sm text-gray-500 dark:text-gray-400 underline"
                        >Annuler</button>
                    @endif
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
                            <p class="text-xs text-gray-500 dark:text-gray-400">Ticket {{ $lastSaleReceipt['receipt_number'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Zone : {{ $lastSaleReceipt['service_area']->label() }}</p>
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
                        <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-1">
                            <span>Paiement</span>
                            <span>{{ $lastSaleReceipt['payment_method']->label() }}</span>
                        </div>
                        @if ($lastSaleReceipt['amount_given'] !== null)
                            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                                <span>Montant donné</span>
                                <span>{{ number_format($lastSaleReceipt['amount_given'], 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-4">
                                <span>Monnaie rendue</span>
                                <span>{{ number_format($lastSaleReceipt['change_due'], 0, ',', ' ') }} FCFA</span>
                            </div>
                        @else
                            <div class="mb-4"></div>
                        @endif

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

    <script>
        function offlinePos(catalog, syncUrl) {
            return {
                catalog,
                syncUrl,
                storageKey: 'mcberto:offline-sales:v1',
                offline: !navigator.onLine,
                online: navigator.onLine,
                syncing: false,
                lastSyncMessage: '',
                serviceArea: 'standard',
                offlineSearch: '',
                offlineCart: {},
                offlinePaymentMethod: 'cash',
                offlineAmountGiven: 0,
                pendingSales: [],

                init() {
                    this.pendingSales = this.loadPendingSales();

                    window.addEventListener('online', () => {
                        this.online = true;
                        this.offline = false;
                        this.syncPending();
                    });

                    window.addEventListener('offline', () => {
                        this.online = false;
                        this.offline = true;
                        this.lastSyncMessage = 'Connexion perdue.';
                    });

                    if (this.online && this.pendingSales.length > 0) {
                        this.syncPending();
                    }
                },

                filteredProducts() {
                    const query = this.offlineSearch.trim().toLowerCase();

                    return this.catalog.categories
                        .flatMap(category => category.products)
                        .filter(product => product.service_area === this.serviceArea)
                        .filter(product => query === '' || product.name.toLowerCase().includes(query));
                },

                addOfflineProduct(product) {
                    if (this.offlineCart[product.id]) {
                        this.offlineCart[product.id].quantity++;
                    } else {
                        this.offlineCart[product.id] = {
                            product_id: product.id,
                            product_name: product.name,
                            emoji: product.emoji,
                            unit_price: product.price,
                            quantity: 1,
                        };
                    }

                    if (this.offlinePaymentMethod === 'cash' && Number(this.offlineAmountGiven || 0) < this.offlineCartTotal()) {
                        this.offlineAmountGiven = this.offlineCartTotal();
                    }
                },

                incrementOffline(productId) {
                    if (this.offlineCart[productId]) {
                        this.offlineCart[productId].quantity++;
                    }
                },

                decrementOffline(productId) {
                    if (!this.offlineCart[productId]) return;

                    this.offlineCart[productId].quantity--;

                    if (this.offlineCart[productId].quantity <= 0) {
                        delete this.offlineCart[productId];
                    }
                },

                offlineCartItems() {
                    return Object.values(this.offlineCart);
                },

                offlineCartCount() {
                    return this.offlineCartItems().reduce((total, item) => total + item.quantity, 0);
                },

                offlineCartTotal() {
                    return this.offlineCartItems().reduce((total, item) => total + item.unit_price * item.quantity, 0);
                },

                offlineChangeDue() {
                    if (this.offlinePaymentMethod !== 'cash') return 0;

                    return Number(this.offlineAmountGiven || 0) - this.offlineCartTotal();
                },

                queueOfflineSale() {
                    if (this.offlineCartCount() === 0) return;
                    if (this.offlinePaymentMethod === 'cash' && this.offlineChangeDue() < 0) return;

                    const total = this.offlineCartTotal();
                    const sale = {
                        offline_uuid: this.uuid(),
                        created_at: new Date().toISOString(),
                        payment_method: this.offlinePaymentMethod,
                        service_area: this.serviceArea,
                        total_amount: total,
                        amount_given: this.offlinePaymentMethod === 'cash' ? Number(this.offlineAmountGiven || total) : null,
                        change_due: this.offlinePaymentMethod === 'cash' ? this.offlineChangeDue() : null,
                        items: this.offlineCartItems().map(item => ({
                            product_id: item.product_id,
                            product_name: item.product_name,
                            unit_price: item.unit_price,
                            quantity: item.quantity,
                            subtotal: item.unit_price * item.quantity,
                        })),
                    };

                    this.pendingSales.push(sale);
                    this.savePendingSales();
                    this.offlineCart = {};
                    this.offlineAmountGiven = 0;
                    this.lastSyncMessage = 'Vente enregistrée sur cet appareil.';

                    if (this.online) {
                        this.syncPending();
                    }
                },

                async syncPending() {
                    if (!this.online || this.syncing || this.pendingSales.length === 0) return;

                    this.syncing = true;
                    this.lastSyncMessage = '';

                    try {
                        const response = await fetch(this.syncUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ sales: this.pendingSales }),
                        });

                        if (!response.ok) {
                            throw new Error('sync_failed');
                        }

                        const data = await response.json();
                        const syncedIds = new Set((data.synced || []).map(item => item.offline_uuid));
                        this.pendingSales = this.pendingSales.filter(sale => !syncedIds.has(sale.offline_uuid));
                        this.savePendingSales();

                        const syncedCount = syncedIds.size;
                        const failedCount = (data.failed || []).length;
                        this.lastSyncMessage = `${syncedCount} synchronisée(s)` + (failedCount > 0 ? `, ${failedCount} refusée(s)` : '.');
                    } catch (error) {
                        this.lastSyncMessage = 'Synchronisation impossible pour le moment.';
                    } finally {
                        this.syncing = false;
                    }
                },

                loadPendingSales() {
                    try {
                        return JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                    } catch (error) {
                        return [];
                    }
                },

                savePendingSales() {
                    localStorage.setItem(this.storageKey, JSON.stringify(this.pendingSales));
                },

                uuid() {
                    if (window.crypto && crypto.randomUUID) {
                        return crypto.randomUUID();
                    }

                    return `offline-${Date.now()}-${Math.random().toString(16).slice(2)}`;
                },

                formatMoney(amount) {
                    return `${Number(amount || 0).toLocaleString('fr-FR')} FCFA`;
                },
            };
        }
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }

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
