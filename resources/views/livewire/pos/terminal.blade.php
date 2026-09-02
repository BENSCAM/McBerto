<div
    class="py-6"
    x-data="offlinePos(@js($this->offlineCatalog()), '{{ route('pos.offline-sales.sync') }}', @js($saleDate))"
    x-init="init()"
>
    <div wire:loading.class="opacity-100" class="fixed top-0 left-0 right-0 h-1 bg-brand-600 z-50 opacity-0 transition-opacity duration-150"></div>

    <div class="w-full px-4 sm:px-6 lg:px-8 pb-20 lg:pb-0">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Caisse</h2>
                <span
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="online ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100'"
                    x-text="online ? 'En ligne' : 'Hors connexion'"
                ></span>
            </div>

            @unless ($this->todayClosing)
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto" x-show="!offline">
                    @if (auth()->user()->isAtLeastManager())
                        <div class="flex items-center gap-2 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5">
                            <label for="saleDate" class="text-sm font-medium text-gray-600 dark:text-gray-300">Date</label>
                            <input
                                id="saleDate"
                                type="date"
                                x-model="onlineSaleDate"
                                x-on:change="$wire.set('saleDate', onlineSaleDate)"
                                max="{{ now()->toDateString() }}"
                                class="w-36 border-0 p-0 text-sm bg-transparent text-gray-900 dark:text-gray-100 focus:ring-0"
                            >
                        </div>
                        <x-input-error :messages="$errors->get('saleDate')" class="w-full sm:w-auto" />
                    @elseif ($this->cashierHasBackdatedSalePermission())
                        <div class="rounded-md border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 px-3 py-2 text-sm text-amber-900 dark:text-amber-100">
                            Date autorisée : {{ auth()->user()->backdate_sales_date->format('d/m/Y') }}
                        </div>
                    @endif

                    <div class="inline-flex rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1">
                        <template x-for="area in catalog.serviceAreas" :key="`online-area-${area.value}`">
                            <button
                                type="button"
                                x-on:click="selectOnlineServiceArea(area.value)"
                                class="px-3 py-1.5 rounded text-sm font-medium"
                                :class="onlineServiceArea === area.value ? 'bg-brand-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                x-text="area.label"
                            ></button>
                        </template>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <input
                            type="text"
                            x-model="onlineSearch"
                            x-on:input="onlineActiveCategoryId = null"
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
                    @if (auth()->user()->isAtLeastManager())
                        <div class="flex items-center gap-2 rounded-md border border-amber-200 dark:border-amber-700 bg-white/70 dark:bg-gray-900/50 px-3 py-2">
                            <label for="offlineSaleDate" class="text-sm font-medium text-amber-900 dark:text-amber-100">Date</label>
                            <input
                                id="offlineSaleDate"
                                type="date"
                                x-model="onlineSaleDate"
                                max="{{ now()->toDateString() }}"
                                class="w-36 border-0 p-0 text-sm bg-transparent text-gray-900 dark:text-gray-100 focus:ring-0"
                            >
                        </div>
                    @elseif ($this->cashierHasBackdatedSalePermission())
                        <div class="rounded-md border border-amber-200 dark:border-amber-700 bg-white/70 dark:bg-gray-900/50 px-3 py-2 text-sm text-amber-900 dark:text-amber-100">
                            Date autorisée : {{ auth()->user()->backdate_sales_date->format('d/m/Y') }}
                        </div>
                    @endif
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
                    <span x-show="syncingSalesCount() > 0" class="ml-2"><span x-text="syncingSalesCount()"></span> en cours.</span>
                    <span x-show="syncedSales.length > 0" class="ml-2 text-green-700 dark:text-green-200"><span x-text="syncedSales.length"></span> synchronisée(s).</span>
                    <span x-show="failedSales.length > 0" class="ml-2 text-red-700 dark:text-red-200"><span x-text="failedSales.length"></span> refusée(s).</span>
                    <span x-show="lastSyncMessage" x-text="lastSyncMessage" class="ml-2"></span>
                </div>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs text-amber-900 dark:text-amber-100">
                    <div>Service worker : <span x-text="serviceWorkerReady ? 'actif' : 'non détecté'"></span></div>
                    <div>Catalogue : <span x-text="catalog.version || 'local'"></span></div>
                    <div>Dernière sync : <span x-text="lastSyncAt || 'jamais'"></span></div>
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
                                class="h-44 min-w-0 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-left hover:border-brand-500 hover:shadow-md transition flex flex-col overflow-hidden"
                            >
                                <div class="text-3xl mb-3 shrink-0" x-text="product.emoji || '•'"></div>
                                <div class="min-h-[4.25rem] font-medium text-gray-900 dark:text-gray-100 leading-snug overflow-hidden [display:-webkit-box] [-webkit-line-clamp:3] [-webkit-box-orient:vertical] [overflow-wrap:anywhere]" x-text="product.name"></div>
                                <div class="mt-auto text-sm text-brand-600 dark:text-brand-400 font-semibold" x-text="formatMoney(product.price)"></div>
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

                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium text-gray-800 dark:text-gray-200 text-sm">File de synchronisation</h4>
                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="formatMoney(pendingTotal())"></span>
                        </div>

                        <template x-if="pendingSales.length === 0 && failedSales.length === 0 && syncedSales.length === 0">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune vente en attente.</p>
                        </template>

                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <template x-for="sale in pendingSales" :key="sale.offline_uuid">
                                <div class="rounded-md border border-gray-200 dark:border-gray-700 p-2 text-sm">
                                    <div class="flex justify-between gap-2">
                                        <span class="text-gray-700 dark:text-gray-300" x-text="offlineReference(sale)"></span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100" x-text="formatMoney(sale.total_amount)"></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span x-text="`${sale.items.length} ligne(s) · ${sale.payment_method}`"></span>
                                        <span class="rounded-full px-2 py-0.5" :class="sale.status === 'syncing' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-100' : 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-100'" x-text="sale.status === 'syncing' ? 'syncing' : 'pending'"></span>
                                    </div>
                                </div>
                            </template>

                            <template x-for="sale in failedSales" :key="sale.offline_uuid">
                                <div class="rounded-md border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 p-2 text-sm">
                                    <div class="flex justify-between gap-2">
                                        <span class="text-red-700 dark:text-red-100" x-text="offlineReference(sale)"></span>
                                        <div class="flex gap-2">
                                            <button type="button" x-on:click="retryFailedSale(sale.offline_uuid)" class="text-xs text-red-600 dark:text-red-200 underline">Réessayer</button>
                                            <button type="button" x-on:click="removeFailedSale(sale.offline_uuid)" class="text-xs text-red-600 dark:text-red-200 underline">Retirer</button>
                                        </div>
                                    </div>
                                    <div class="text-xs text-red-600 dark:text-red-200 break-words [overflow-wrap:anywhere]" x-text="sale.error_message || 'Synchronisation refusée.'"></div>
                                </div>
                            </template>

                            <template x-for="sale in syncedSales.slice(0, 5)" :key="`synced-${sale.offline_uuid}`">
                                <div class="rounded-md border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 p-2 text-sm">
                                    <div class="flex justify-between gap-2">
                                        <span class="text-green-700 dark:text-green-100" x-text="offlineReference(sale)"></span>
                                        <span class="font-medium text-green-800 dark:text-green-100" x-text="sale.receipt_number || 'Synchronisée'"></span>
                                    </div>
                                    <template x-if="sale.warning_message">
                                        <div class="text-xs text-amber-700 dark:text-amber-200 break-words [overflow-wrap:anywhere]" x-text="sale.warning_message"></div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="!offline" class="grid grid-cols-1 lg:grid-cols-[200px_1fr_360px] gap-4 lg:h-[calc(100vh-15rem)]">
            <!-- Categories -->
            <div class="min-w-0 flex lg:flex-col gap-2 overflow-x-auto lg:overflow-y-auto lg:h-full lg:min-h-0 pb-1 lg:pb-0">
                <template x-for="category in onlineCategories()" :key="`online-category-${category.id}`">
                    <button
                        type="button"
                        x-on:click="selectOnlineCategory(category.id)"
                        class="shrink-0 lg:w-full flex items-center justify-between gap-2 px-4 py-2.5 rounded-md text-sm font-medium text-left transition"
                        :class="onlineActiveCategoryId === category.id && onlineSearch.trim() === '' ? 'bg-brand-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-brand-400'"
                    >
                        <span x-text="category.name"></span>
                        <span
                            class="text-xs"
                            :class="onlineActiveCategoryId === category.id && onlineSearch.trim() === '' ? 'text-white/80' : 'text-gray-400 dark:text-gray-500'"
                            x-text="category.products.filter(product => product.service_area === onlineServiceArea).length"
                        ></span>
                    </button>
                </template>
            </div>

            <!-- Products -->
            <div class="min-w-0 lg:h-full lg:min-h-0 lg:overflow-y-auto lg:pr-1">
                <template x-if="onlineVisibleProducts().length === 0">
                    <div class="flex flex-col items-center justify-center text-center py-16 text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 rounded-lg border border-dashed border-gray-200 dark:border-gray-700">
                        <span class="text-3xl mb-2">🔎</span>
                        <p class="text-sm" x-text="onlineSearch.trim() !== '' ? `Aucun produit ne correspond à « ${onlineSearch} ».` : 'Aucun produit dans cette catégorie.'"></p>
                    </div>
                </template>

                <template x-if="onlineVisibleProducts().length > 0">
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                        <template x-for="product in onlineVisibleProducts()" :key="`online-product-${product.id}`">
                            <button
                                type="button"
                                x-on:click="addOnlineProduct(product)"
                                class="h-44 min-w-0 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-left hover:border-brand-500 hover:shadow-md transition flex flex-col overflow-hidden"
                            >
                                <div class="text-3xl mb-3 shrink-0" x-text="product.emoji || '•'"></div>
                                <div class="min-h-[4.25rem] font-medium text-gray-900 dark:text-gray-100 leading-snug overflow-hidden [display:-webkit-box] [-webkit-line-clamp:3] [-webkit-box-orient:vertical] [overflow-wrap:anywhere]" x-text="product.name"></div>
                                <div class="mt-auto text-sm text-brand-600 dark:text-brand-400 font-semibold" x-text="formatMoney(product.price)"></div>
                            </button>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Cart + recent sales -->
            <div class="min-w-0 flex flex-col gap-4 lg:h-full lg:min-h-0">
                <div id="cart-section" wire:ignore class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex flex-col lg:min-h-0 lg:max-h-[65%]">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">
                            Panier
                            <span x-show="onlineCartCount() > 0" class="text-xs font-normal text-gray-400 dark:text-gray-500">(<span x-text="onlineCartCount()"></span>)</span>
                        </h3>
                        <button
                            x-show="onlineCartCount() > 0"
                            x-on:click="$store.confirmModal.open('Vider le panier ?', () => clearOnlineCart())"
                            class="text-xs text-red-500 dark:text-red-400 underline"
                        >Vider</button>
                    </div>

                    <p x-show="onlineCartCount() === 0" class="text-sm text-gray-500 dark:text-gray-400">Panier vide. Sélectionnez un produit.</p>

                    <div x-show="onlineCartCount() > 0" class="flex flex-col min-h-0">
                        <div class="max-h-56 overflow-y-auto space-y-3 mb-4 pr-1">
                            <template x-for="item in onlineCartItems()" :key="`online-cart-${item.product_id}`">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                            <span class="mr-1" x-text="item.emoji || ''"></span><span class="break-words [overflow-wrap:anywhere]" x-text="item.product_name"></span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="formatMoney(item.unit_price)"></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            x-on:click="decrementOnline(item.product_id)"
                                            class="w-6 h-6 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                        >−</button>
                                        <input
                                            type="number"
                                            min="1"
                                            max="999"
                                            x-bind:value="item.quantity"
                                            x-on:change="setOnlineQuantity(item.product_id, $event.target.value)"
                                            class="w-12 text-center text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md py-1"
                                        >
                                        <button
                                            type="button"
                                            x-on:click="incrementOnline(item.product_id)"
                                            class="w-6 h-6 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                        >+</button>
                                    </div>
                                    <button type="button" x-on:click="removeOnlineProduct(item.product_id)" class="text-red-500 text-xs">✕</button>
                                </div>
                            </template>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between font-semibold text-gray-900 dark:text-gray-100">
                            <span>Total</span>
                            <span x-text="formatMoney(onlineCartTotal())"></span>
                        </div>

                        <button type="button" x-on:click="openOnlineCheckout()" class="mt-4 w-full bg-brand-600 text-white rounded-md py-2 font-medium">
                            Encaisser
                        </button>
                    </div>
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
                                            <span class="text-xs text-red-600 dark:text-red-300">
                                                Annulée par {{ $sale->canceledBy?->name }}
                                                @if ($sale->cancellation_reason)
                                                    · {{ $sale->cancellation_reason }}
                                                @endif
                                            </span>
                                        @elseif (! $sale->cash_register_closing_id && (auth()->user()->isAtLeastManager() || $sale->user_id === auth()->id()))
                                            <button
                                                wire:click="openCancelSale({{ $sale->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="openCancelSale"
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

        <a
            x-show="onlineCartCount() > 0"
            x-cloak
            href="#cart-section"
            class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-brand-600 text-white px-4 py-3 flex items-center justify-between shadow-lg"
        >
            <span class="text-sm font-medium"><span x-text="onlineCartCount()"></span> article(s)</span>
            <span class="font-semibold"><span x-text="formatMoney(onlineCartTotal())"></span> · Voir le panier</span>
        </a>
        @endif

        @if ($salePendingCancellationId)
            <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="closeCancelSale">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                    <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-2">Justificatif d'annulation</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Indiquez pourquoi cette commande doit être annulée. Ce justificatif sera visible par le gérant et le propriétaire.</p>

                    <label for="cancellationReason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motif</label>
                    <textarea
                        wire:model="cancellationReason"
                        id="cancellationReason"
                        rows="4"
                        maxlength="255"
                        class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Exemple : erreur de saisie, client parti, doublon..."
                    ></textarea>
                    <x-input-error :messages="$errors->get('cancellationReason')" class="mt-2" />

                    <div class="flex gap-2 mt-5">
                        <button
                            type="button"
                            wire:click="closeCancelSale"
                            class="flex-1 text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-md py-2"
                        >Retour</button>
                        <button
                            type="button"
                            wire:click="confirmCancelSale"
                            wire:loading.attr="disabled"
                            wire:target="confirmCancelSale"
                            class="flex-1 bg-red-600 text-white rounded-md py-2 font-medium disabled:opacity-50"
                        >Confirmer l'annulation</button>
                    </div>
                </div>
            </div>
        @endif

        <div x-show="onlineCheckoutOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-on:click.self="closeOnlineCheckout()">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-sm">
                <template x-if="onlineError">
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/40 px-3 py-2 text-sm text-red-700 dark:text-red-100" x-text="onlineError"></div>
                </template>

                <template x-if="onlineCheckoutMethod === 'cash'">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-1">Paiement en espèces</h3>
                        <div class="mb-4 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <p>Total à payer : <span class="font-semibold" x-text="formatMoney(onlineCartTotal())"></span></p>
                            <p>Date de vente : <span class="font-semibold" x-text="formatDisplayDate(onlineSaleDate)"></span></p>
                        </div>

                        <label for="onlineAmountGiven" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Montant donné par le client</label>
                        <input
                            x-model.number="onlineAmountGiven"
                            id="onlineAmountGiven"
                            class="block mt-1 w-full text-lg rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500"
                            type="number"
                            min="0"
                            placeholder="0"
                        >

                        <div class="flex flex-wrap gap-2 mt-2">
                            <template x-for="note in [500, 1000, 2000, 5000, 10000]" :key="note">
                                <button
                                    type="button"
                                    x-on:click="onlineAmountGiven = note"
                                    class="px-3 py-1 text-sm rounded-md border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-brand-400"
                                    x-text="formatMoney(note).replace(' FCFA', '')"
                                ></button>
                            </template>
                            <button
                                type="button"
                                x-on:click="onlineAmountGiven = onlineCartTotal()"
                                class="px-3 py-1 text-sm rounded-md border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-brand-400"
                            >Montant exact</button>
                        </div>

                        <div
                            class="mt-4 p-3 rounded-md"
                            :class="onlineChangeDue() === null ? 'bg-gray-50 dark:bg-gray-700' : (onlineChangeDue() < 0 ? 'bg-red-50 dark:bg-red-900' : 'bg-green-50 dark:bg-green-900')"
                        >
                            <template x-if="onlineChangeDue() === null">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Saisissez le montant reçu.</p>
                            </template>
                            <template x-if="onlineChangeDue() !== null && onlineChangeDue() < 0">
                                <p class="text-sm text-red-700 dark:text-red-200">Montant insuffisant : <span x-text="formatMoney(Math.abs(onlineChangeDue()))"></span> manquant.</p>
                            </template>
                            <template x-if="onlineChangeDue() !== null && onlineChangeDue() >= 0">
                                <div>
                                    <p class="text-sm text-green-800 dark:text-green-200">Monnaie à rendre</p>
                                    <p class="text-2xl font-semibold text-green-800 dark:text-green-200" x-text="formatMoney(onlineChangeDue())"></p>
                                </div>
                            </template>
                        </div>

                        <div class="flex gap-2 mt-4">
                            <button
                                type="button"
                                x-on:click="backToOnlinePaymentMethods()"
                                class="flex-1 text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-md py-2"
                            >Retour</button>
                            <button
                                type="button"
                                x-on:click="confirmOnlineSale('cash')"
                                :disabled="onlineProcessing || onlineChangeDue() === null || onlineChangeDue() < 0"
                                class="flex-1 bg-brand-600 text-white rounded-md py-2 font-medium disabled:opacity-50"
                            >
                                <span x-show="!onlineProcessing">Confirmer</span>
                                <span x-show="onlineProcessing">Traitement…</span>
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="onlineCheckoutMethod !== 'cash'">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-4">Mode de paiement</h3>
                        <div class="mb-4 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <p>Total : <span class="font-semibold" x-text="formatMoney(onlineCartTotal())"></span></p>
                            <p>Date de vente : <span class="font-semibold" x-text="formatDisplayDate(onlineSaleDate)"></span></p>
                        </div>

                        <div class="space-y-2">
                            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                @if ($method === \App\Enums\PaymentMethod::Cash)
                                    <button
                                        type="button"
                                        x-on:click="selectOnlinePaymentMethod('{{ $method->value }}')"
                                        class="w-full border border-gray-200 dark:border-gray-700 rounded-md py-2 text-gray-800 dark:text-gray-200 hover:border-brand-500"
                                    >{{ $method->label() }}</button>
                                @else
                                    <button
                                        type="button"
                                        x-on:click="confirmOnlineSale('{{ $method->value }}')"
                                        :disabled="onlineProcessing"
                                        class="w-full border border-gray-200 dark:border-gray-700 rounded-md py-2 text-gray-800 dark:text-gray-200 hover:border-brand-500 disabled:opacity-50"
                                    >
                                        <span x-show="!onlineProcessing">{{ $method->label() }}</span>
                                        <span x-show="onlineProcessing">Traitement…</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>

                        <button
                            type="button"
                            x-on:click="closeOnlineCheckout()"
                            :disabled="onlineProcessing"
                            class="mt-4 text-sm text-gray-500 dark:text-gray-400 underline disabled:opacity-50"
                        >Annuler</button>
                    </div>
                </template>
            </div>
        </div>

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
        function offlinePos(catalog, syncUrl, initialSaleDate) {
            return {
                catalog,
                syncUrl,
                storageKey: 'mcberto:offline-sales:v1',
                failedStorageKey: 'mcberto:offline-failed-sales:v1',
                syncedStorageKey: 'mcberto:offline-synced-sales:v1',
                catalogStorageKey: 'mcberto:offline-catalog:v3',
                offline: !navigator.onLine,
                online: navigator.onLine,
                syncing: false,
                serviceWorkerReady: false,
                lastSyncMessage: '',
                lastSyncAt: localStorage.getItem('mcberto:last-sync-at') || '',
                serviceArea: 'standard',
                offlineSearch: '',
                offlineCart: {},
                offlinePaymentMethod: 'cash',
                offlineAmountGiven: 0,
                onlineServiceArea: 'standard',
                onlineSaleDate: initialSaleDate,
                onlineActiveCategoryId: null,
                onlineSearch: '',
                onlineCart: {},
                onlineCheckoutOpen: false,
                onlineCheckoutMethod: null,
                onlineAmountGiven: '',
                onlineProcessing: false,
                onlineError: '',
                pendingSales: [],
                failedSales: [],
                syncedSales: [],

                init() {
                    this.catalog = this.loadCatalog();
                    this.saveCatalog(this.catalog);
                    this.onlineActiveCategoryId = this.onlineCategories()[0]?.id ?? null;
                    this.pendingSales = this.loadPendingSales();
                    this.failedSales = this.loadFailedSales();
                    this.syncedSales = this.loadSyncedSales();
                    this.detectServiceWorker();

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

                onlineCategories() {
                    return this.catalog.categories
                        .map(category => ({
                            ...category,
                            products: category.products.filter(product => product.service_area === this.onlineServiceArea),
                        }))
                        .filter(category => category.products.length > 0);
                },

                onlineVisibleProducts() {
                    const query = this.onlineSearch.trim().toLowerCase();
                    const products = this.catalog.categories
                        .flatMap(category => category.products)
                        .filter(product => product.service_area === this.onlineServiceArea);

                    if (query !== '') {
                        return products.filter(product => product.name.toLowerCase().includes(query));
                    }

                    const activeCategory = this.onlineCategories().find(category => category.id === this.onlineActiveCategoryId)
                        ?? this.onlineCategories()[0];

                    if (!activeCategory) {
                        return [];
                    }

                    if (this.onlineActiveCategoryId !== activeCategory.id) {
                        this.onlineActiveCategoryId = activeCategory.id;
                    }

                    return activeCategory.products;
                },

                selectOnlineCategory(categoryId) {
                    this.onlineActiveCategoryId = categoryId;
                    this.onlineSearch = '';
                },

                selectOnlineServiceArea(serviceArea) {
                    if (this.onlineServiceArea === serviceArea) return;

                    this.onlineServiceArea = serviceArea;
                    this.onlineActiveCategoryId = this.onlineCategories()[0]?.id ?? null;
                    this.onlineSearch = '';
                    this.clearOnlineCart();
                    this.closeOnlineCheckout();
                },

                addOfflineProduct(product) {
                    const normalized = this.normalizeProduct(product);

                    if (!normalized) return;

                    if (this.offlineCart[normalized.id]) {
                        this.offlineCart[normalized.id].quantity++;
                    } else {
                        this.offlineCart[normalized.id] = {
                            product_id: normalized.id,
                            product_name: normalized.name,
                            emoji: normalized.emoji,
                            unit_price: normalized.price,
                            quantity: 1,
                        };
                    }

                    if (this.offlinePaymentMethod === 'cash' && Number(this.offlineAmountGiven || 0) < this.offlineCartTotal()) {
                        this.offlineAmountGiven = this.offlineCartTotal();
                    }
                },

                addOnlineProduct(product) {
                    const normalized = this.normalizeProduct(product);

                    if (!normalized) return;

                    const current = this.onlineCart[normalized.id];

                    if (this.onlineCart[normalized.id]) {
                        this.onlineCart = {
                            ...this.onlineCart,
                            [normalized.id]: {
                                ...current,
                                quantity: Number(current.quantity || 0) + 1,
                            },
                        };
                    } else {
                        this.onlineCart = {
                            ...this.onlineCart,
                            [normalized.id]: {
                                product_id: normalized.id,
                                product_name: normalized.name,
                                emoji: normalized.emoji,
                                unit_price: normalized.price,
                                quantity: 1,
                            },
                        };
                    }
                },

                incrementOnline(productId) {
                    const item = this.onlineCart[productId];

                    if (!item) return;

                    this.onlineCart = {
                        ...this.onlineCart,
                        [productId]: {
                            ...item,
                            quantity: Number(item.quantity || 0) + 1,
                        },
                    };
                },

                decrementOnline(productId) {
                    const item = this.onlineCart[productId];

                    if (!item) return;

                    const nextQuantity = Number(item.quantity || 0) - 1;

                    if (nextQuantity <= 0) {
                        this.removeOnlineProduct(productId);
                        return;
                    }

                    this.onlineCart = {
                        ...this.onlineCart,
                        [productId]: {
                            ...item,
                            quantity: nextQuantity,
                        },
                    };
                },

                removeOnlineProduct(productId) {
                    const nextCart = { ...this.onlineCart };
                    delete nextCart[productId];
                    this.onlineCart = nextCart;
                },

                setOnlineQuantity(productId, quantity) {
                    const item = this.onlineCart[productId];

                    if (!item) return;

                    const nextQuantity = Math.min(Math.max(Number.parseInt(quantity || 0, 10), 0), 999);

                    if (nextQuantity < 1) {
                        this.removeOnlineProduct(productId);
                        return;
                    }

                    this.onlineCart = {
                        ...this.onlineCart,
                        [productId]: {
                            ...item,
                            quantity: nextQuantity,
                        },
                    };
                },

                clearOnlineCart() {
                    this.onlineCart = {};
                },

                onlineCartItems() {
                    return Object.values(this.onlineCart);
                },

                onlineCartCount() {
                    return this.onlineCartItems().reduce((total, item) => total + Number(item.quantity || 0), 0);
                },

                onlineCartTotal() {
                    return this.onlineCartItems().reduce((total, item) => {
                        return total + Number(item.unit_price || 0) * Number(item.quantity || 0);
                    }, 0);
                },

                openOnlineCheckout() {
                    if (this.onlineCartCount() === 0) return;

                    this.onlineCheckoutOpen = true;
                    this.onlineCheckoutMethod = null;
                    this.onlineAmountGiven = '';
                    this.onlineError = '';
                },

                closeOnlineCheckout() {
                    if (this.onlineProcessing) return;

                    this.onlineCheckoutOpen = false;
                    this.onlineCheckoutMethod = null;
                    this.onlineAmountGiven = '';
                    this.onlineError = '';
                },

                selectOnlinePaymentMethod(paymentMethod) {
                    this.onlineError = '';

                    if (paymentMethod === 'cash') {
                        this.onlineCheckoutMethod = 'cash';
                        this.onlineAmountGiven = '';
                    }
                },

                backToOnlinePaymentMethods() {
                    if (this.onlineProcessing) return;

                    this.onlineCheckoutMethod = null;
                    this.onlineAmountGiven = '';
                    this.onlineError = '';
                },

                onlineChangeDue() {
                    const amountGiven = this.moneyInputValue(this.onlineAmountGiven);

                    if (this.onlineAmountGiven === '' || Number.isNaN(amountGiven)) {
                        return null;
                    }

                    return amountGiven - this.onlineCartTotal();
                },

                async confirmOnlineSale(paymentMethod) {
                    if (this.onlineProcessing || this.onlineCartCount() === 0) return;

                    if (!this.online || !navigator.onLine) {
                        this.onlineError = 'Connexion interrompue. Vérifiez le réseau avant de confirmer la vente.';
                        return;
                    }

                    this.onlineError = '';
                    const total = this.onlineCartTotal();
                    const amountGiven = paymentMethod === 'cash' ? this.moneyInputValue(this.onlineAmountGiven) : null;
                    const changeDue = paymentMethod === 'cash' ? amountGiven - total : null;

                    if (paymentMethod === 'cash' && (Number.isNaN(amountGiven) || changeDue < 0)) {
                        this.onlineError = 'Le montant donné est insuffisant.';
                        return;
                    }

                    this.onlineProcessing = true;

                    try {
                        await this.$wire.completeClientSale(
                            this.onlineCartItems().map(item => ({
                                product_id: item.product_id,
                                quantity: item.quantity,
                            })),
                            paymentMethod,
                            amountGiven,
                            changeDue,
                            this.onlineServiceArea,
                            this.onlineSaleDate,
                        );

                        this.clearOnlineCart();
                        this.onlineCheckoutOpen = false;
                        this.onlineCheckoutMethod = null;
                        this.onlineAmountGiven = '';
                        this.onlineError = '';
                    } catch (error) {
                        console.error('Erreur encaissement', error);
                        this.onlineError = this.livewireErrorMessage(error) || 'Impossible d’enregistrer cette vente. Vérifiez la date, le montant et réessayez.';
                    } finally {
                        this.onlineProcessing = false;
                    }
                },

                livewireErrorMessage(error) {
                    const errors = error?.response?.effects?.errors
                        || error?.response?.serverMemo?.errors
                        || error?.response?.data?.errors
                        || error?.errors;

                    if (errors && typeof errors === 'object') {
                        const first = Object.values(errors).flat()[0];

                        if (Array.isArray(first)) return first[0];
                        if (typeof first === 'string') return first;
                    }

                    const message = error?.response?.message
                        || error?.response?.data?.message
                        || error?.message
                        || '';

                    if (message.includes('stock')) {
                        return message;
                    }

                    return message;
                },

                moneyInputValue(value) {
                    if (value === null || value === undefined || value === '') {
                        return Number.NaN;
                    }

                    if (typeof value === 'number') {
                        return value;
                    }

                    const normalized = String(value)
                        .replace(/\s/g, '')
                        .replace(/[^\d.,-]/g, '')
                        .replace(',', '.');

                    return Number(normalized);
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
                    return this.offlineCartItems().reduce((total, item) => total + Number(item.quantity || 0), 0);
                },

                offlineCartTotal() {
                    return this.offlineCartItems().reduce((total, item) => total + Number(item.unit_price || 0) * Number(item.quantity || 0), 0);
                },

                pendingTotal() {
                    return this.pendingSales.reduce((total, sale) => total + Number(sale.total_amount || 0), 0);
                },

                syncingSalesCount() {
                    return this.pendingSales.filter(sale => sale.status === 'syncing').length;
                },

                offlineChangeDue() {
                    if (this.offlinePaymentMethod !== 'cash') return 0;

                    return Number(this.offlineAmountGiven || 0) - this.offlineCartTotal();
                },

                selectedSaleCreatedAt() {
                    const selectedDate = this.onlineSaleDate || new Date().toISOString().slice(0, 10);
                    const [year, month, day] = selectedDate.split('-').map(value => Number.parseInt(value, 10));
                    const now = new Date();

                    if (!year || !month || !day) {
                        return now.toISOString();
                    }

                    return new Date(year, month - 1, day, now.getHours(), now.getMinutes(), now.getSeconds()).toISOString();
                },

                queueOfflineSale() {
                    if (this.offlineCartCount() === 0) return;
                    if (this.offlinePaymentMethod === 'cash' && this.offlineChangeDue() < 0) return;
                    if (!this.validateOfflineSale()) return;

                    const total = this.offlineCartTotal();
                    const sale = {
                        offline_uuid: this.uuid(),
                        offline_reference: this.nextOfflineReference(),
                        status: 'pending',
                        created_at: this.selectedSaleCreatedAt(),
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
                    this.pendingSales = this.pendingSales.map(sale => ({ ...sale, status: 'syncing' }));
                    this.savePendingSales();

                    try {
                        const salesToSync = this.pendingSales;
                        const response = await fetch(this.syncUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ sales: salesToSync }),
                        });

                        if (!response.ok) {
                            throw new Error('sync_failed');
                        }

                        const data = await response.json();
                        const syncedById = new Map((data.synced || []).map(item => [item.offline_uuid, item]));
                        const syncedIds = new Set(syncedById.keys());
                        const failedById = new Map((data.failed || []).map(item => [item.offline_uuid, item.message]));
                        const warningsById = new Map((data.warnings || []).map(item => [item.offline_uuid, item.message]));

                        this.failedSales = [
                            ...this.failedSales,
                            ...salesToSync
                                .filter(sale => failedById.has(sale.offline_uuid))
                                .map(sale => ({
                                    ...sale,
                                    status: 'failed',
                                    error_message: failedById.get(sale.offline_uuid),
                                })),
                        ];
                        this.syncedSales = [
                            ...salesToSync
                                .filter(sale => syncedIds.has(sale.offline_uuid))
                                .map(sale => ({
                                    ...sale,
                                    status: 'synced',
                                    receipt_number: syncedById.get(sale.offline_uuid)?.receipt_number || '',
                                    warning_message: warningsById.get(sale.offline_uuid) || '',
                                })),
                            ...this.syncedSales,
                        ].slice(0, 20);
                        this.pendingSales = salesToSync
                            .filter(sale => !syncedIds.has(sale.offline_uuid) && !failedById.has(sale.offline_uuid))
                            .map(sale => ({ ...sale, status: 'pending' }));
                        this.savePendingSales();
                        this.saveFailedSales();
                        this.saveSyncedSales();

                        if (data.catalog) {
                            this.catalog = data.catalog;
                            this.saveCatalog(data.catalog);
                        }

                        const syncedCount = syncedIds.size;
                        const failedCount = (data.failed || []).length;
                        this.lastSyncMessage = `${syncedCount} synchronisée(s)` + (failedCount > 0 ? `, ${failedCount} refusée(s)` : '.');
                        this.lastSyncAt = new Date().toLocaleString('fr-FR');
                        localStorage.setItem('mcberto:last-sync-at', this.lastSyncAt);

                        if (syncedCount > 0 && window.Livewire) {
                            window.Livewire.dispatch('offline-sales-synced');
                        }
                    } catch (error) {
                        this.pendingSales = this.pendingSales.map(sale => ({ ...sale, status: 'pending' }));
                        this.savePendingSales();
                        this.lastSyncMessage = 'Synchronisation impossible pour le moment.';
                    } finally {
                        this.syncing = false;
                    }
                },

                removeFailedSale(offlineUuid) {
                    this.failedSales = this.failedSales.filter(sale => sale.offline_uuid !== offlineUuid);
                    this.saveFailedSales();
                },

                retryFailedSale(offlineUuid) {
                    const sale = this.failedSales.find(sale => sale.offline_uuid === offlineUuid);

                    if (!sale) return;

                    this.failedSales = this.failedSales.filter(failedSale => failedSale.offline_uuid !== offlineUuid);
                    this.pendingSales.push({
                        ...sale,
                        status: 'pending',
                        error_message: undefined,
                    });
                    this.saveFailedSales();
                    this.savePendingSales();

                    if (this.online) {
                        this.syncPending();
                    }
                },

                offlineReference(sale) {
                    const date = new Date(sale.created_at);
                    const time = Number.isNaN(date.getTime())
                        ? ''
                        : date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

                    return `${sale.offline_reference || 'Hors ligne'} ${time}`;
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

                loadFailedSales() {
                    try {
                        return JSON.parse(localStorage.getItem(this.failedStorageKey) || '[]');
                    } catch (error) {
                        return [];
                    }
                },

                saveFailedSales() {
                    localStorage.setItem(this.failedStorageKey, JSON.stringify(this.failedSales));
                },

                loadSyncedSales() {
                    try {
                        return JSON.parse(localStorage.getItem(this.syncedStorageKey) || '[]');
                    } catch (error) {
                        return [];
                    }
                },

                saveSyncedSales() {
                    localStorage.setItem(this.syncedStorageKey, JSON.stringify(this.syncedSales));
                },

                loadCatalog() {
                    if (navigator.onLine) {
                        return this.normalizeCatalog(catalog);
                    }

                    try {
                        const stored = JSON.parse(localStorage.getItem(this.catalogStorageKey) || 'null');

                        if (stored && Array.isArray(stored.categories)) {
                            return this.normalizeCatalog(stored);
                        }
                    } catch (error) {}

                    return this.normalizeCatalog(catalog);
                },

                saveCatalog(nextCatalog) {
                    localStorage.setItem(this.catalogStorageKey, JSON.stringify(this.normalizeCatalog(nextCatalog)));
                },

                normalizeCatalog(nextCatalog) {
                    return {
                        ...nextCatalog,
                        categories: (nextCatalog.categories || []).map(category => ({
                            ...category,
                            products: (category.products || [])
                                .map(product => this.normalizeProduct(product))
                                .filter(Boolean),
                        })),
                    };
                },

                normalizeProduct(product) {
                    const id = Number(product.id ?? product.product_id);
                    const price = Number(product.price ?? product.unit_price ?? 0);

                    if (!Number.isFinite(id) || id <= 0) return null;

                    return {
                        id,
                        name: product.name ?? product.product_name ?? '',
                        emoji: product.emoji ?? '',
                        price: Number.isFinite(price) ? price : 0,
                        service_area: product.service_area ?? this.onlineServiceArea,
                    };
                },

                validateOfflineSale() {
                    const methods = (this.catalog.paymentMethods || []).map(method => method.value);
                    const areas = (this.catalog.serviceAreas || []).map(area => area.value);
                    const items = this.offlineCartItems();

                    if (!methods.includes(this.offlinePaymentMethod) || !areas.includes(this.serviceArea)) {
                        this.lastSyncMessage = 'Paramètres de vente hors ligne invalides.';
                        return false;
                    }

                    const validItems = items.every(item => {
                        return Number(item.product_id) > 0
                            && String(item.product_name || '').trim() !== ''
                            && Number(item.unit_price) >= 0
                            && Number.isInteger(Number(item.quantity))
                            && Number(item.quantity) > 0
                            && Number(item.quantity) <= 999;
                    });

                    if (!validItems) {
                        this.lastSyncMessage = 'Panier hors ligne invalide.';
                        return false;
                    }

                    return true;
                },

                nextOfflineReference() {
                    const date = new Date();
                    const day = date.toISOString().slice(0, 10).replaceAll('-', '');
                    const key = `mcberto:offline-sequence:${day}`;
                    const next = Number(localStorage.getItem(key) || 0) + 1;
                    localStorage.setItem(key, String(next));

                    return `OFF-${day}-${String(next).padStart(4, '0')}`;
                },

                async detectServiceWorker() {
                    if (!('serviceWorker' in navigator)) {
                        this.serviceWorkerReady = false;
                        return;
                    }

                    try {
                        const registration = await navigator.serviceWorker.ready;
                        this.serviceWorkerReady = Boolean(registration.active);
                    } catch (error) {
                        this.serviceWorkerReady = false;
                    }
                },

                uuid() {
                    if (window.crypto && crypto.randomUUID) {
                        return crypto.randomUUID();
                    }

                    return `offline-${Date.now()}-${Math.random().toString(16).slice(2)}`;
                },

                formatMoney(amount) {
                    const value = Number(amount);

                    return `${Number.isFinite(value) ? value.toLocaleString('fr-FR') : '0'} FCFA`;
                },

                formatDisplayDate(value) {
                    if (!value) return 'Aujourd’hui';

                    const [year, month, day] = String(value).split('-');

                    if (!year || !month || !day) return value;

                    return `${day}/${month}/${year}`;
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
