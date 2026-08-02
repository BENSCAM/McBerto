<div class="py-6">
    <div wire:loading.class="opacity-100" class="fixed top-0 left-0 right-0 h-1 bg-brand-600 z-50 opacity-0 transition-opacity duration-150"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 lg:pb-0">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Caisse</h2>

            @unless ($this->todayClosing)
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
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
        <div class="grid grid-cols-1 lg:grid-cols-[200px_1fr_360px] gap-4 lg:h-[calc(100vh-15rem)]">
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
                                <div class="flex items-center justify-between py-2 text-sm" wire:key="recent-sale-{{ $sale->id }}">
                                    <span class="text-gray-500 dark:text-gray-400 w-12 shrink-0">{{ $sale->created_at->format('H:i') }}</span>
                                    <span class="text-gray-700 dark:text-gray-300 flex-1 truncate">{{ $sale->user->name }}</span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 shrink-0">{{ $sale->service_area->label() }}</span>
                                    <span class="text-gray-900 dark:text-gray-100 font-medium shrink-0">{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</span>
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
                            <p class="text-xs text-gray-500 dark:text-gray-400">Vente #{{ $lastSaleReceipt['id'] }}</p>
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
