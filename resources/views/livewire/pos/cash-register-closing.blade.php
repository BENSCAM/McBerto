<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Clôture de caisse — {{ now()->translatedFormat('d/m/Y') }}</h2>
            @if (auth()->user()->isAtLeastManager())
                <a href="{{ route('pos.closing.history') }}" wire:navigate class="text-sm text-brand-600 dark:text-brand-400 underline">Historique</a>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
            @if ($existingClosing)
                <div class="rounded-md bg-green-50 dark:bg-green-900 p-3 text-green-800 dark:text-green-200 text-sm">
                    Caisse déjà clôturée aujourd'hui par {{ $existingClosing->closedBy->name }} à {{ $existingClosing->created_at->format('H:i') }}.
                </div>

                <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">Espèces (système)</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($existingClosing->total_cash, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">Espèces comptées</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($existingClosing->counted_cash, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">Écart</dt>
                        <dd class="font-semibold {{ $existingClosing->variance == 0 ? 'text-green-600' : ($existingClosing->variance < 0 ? 'text-red-600' : 'text-blue-600') }}">
                            @if ($existingClosing->variance == 0)
                                Aucun écart
                            @else
                                {{ $existingClosing->variance > 0 ? '+' : '' }}{{ number_format($existingClosing->variance, 0, ',', ' ') }} FCFA
                                {{ $existingClosing->variance < 0 ? '(manque)' : '(surplus)' }}
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">Orange Money</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($existingClosing->total_orange_money, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">MTN MoMo</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($existingClosing->total_mtn_momo, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">Autre</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($existingClosing->total_other, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between py-2 font-semibold">
                        <dt class="text-gray-900 dark:text-gray-100">Total ({{ $existingClosing->total_orders_count }} ventes)</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($existingClosing->total_amount, 0, ',', ' ') }} FCFA</dd>
                    </div>
                </dl>

                @if (auth()->user()->isAtLeastManager())
                    <button
                        x-on:click="$store.confirmModal.open('Réouvrir la caisse ? Les caissiers pourront de nouveau encaisser des ventes aujourd\'hui, et il faudra refaire une clôture complète plus tard.', () => $wire.reopen())"
                        wire:loading.attr="disabled"
                        wire:target="reopen"
                        class="w-full border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 rounded-md py-2 font-medium disabled:opacity-50"
                    >Réouvrir la caisse</button>
                @endif
            @else
                <p class="text-gray-600 dark:text-gray-400 text-sm">Récapitulatif des ventes non encore clôturées aujourd'hui :</p>

                <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">Espèces (système)</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($pendingTotals['cash'] ?? 0, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">Orange Money</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($pendingTotals['orange_money'] ?? 0, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">MTN MoMo</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($pendingTotals['mtn_momo'] ?? 0, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">Autre</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($pendingTotals['other'] ?? 0, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between py-2 font-semibold">
                        <dt class="text-gray-900 dark:text-gray-100">Total ({{ $pendingCount }} ventes)</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($pendingTotal, 0, ',', ' ') }} FCFA</dd>
                    </div>
                </dl>

                @if ($pendingCount > 0)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <x-input-label for="countedCash" value="Montant en espèces réellement compté dans le tiroir" />
                        <x-text-input
                            wire:model.live="countedCash"
                            id="countedCash"
                            class="block mt-1 w-full"
                            type="number"
                            min="0"
                            placeholder="0"
                        />
                        <x-input-error :messages="$errors->get('countedCash')" class="mt-2" />

                        @if ($this->projectedVariance !== null)
                            <p class="mt-2 text-sm {{ $this->projectedVariance == 0 ? 'text-green-600' : ($this->projectedVariance < 0 ? 'text-red-600' : 'text-blue-600') }}">
                                @if ($this->projectedVariance == 0)
                                    Aucun écart par rapport au système.
                                @else
                                    Écart : {{ $this->projectedVariance > 0 ? '+' : '' }}{{ number_format($this->projectedVariance, 0, ',', ' ') }} FCFA
                                    {{ $this->projectedVariance < 0 ? '(manque)' : '(surplus)' }}
                                @endif
                            </p>
                        @endif
                    </div>
                @endif

                <button
                    x-on:click="$store.confirmModal.open('Confirmer la clôture de caisse du jour ? Cette action est définitive.', () => $wire.close())"
                    wire:loading.attr="disabled"
                    wire:target="close"
                    class="w-full bg-brand-600 text-white rounded-md py-2 font-medium disabled:opacity-50"
                    @disabled($pendingCount === 0)
                >
                    Clôturer la caisse
                </button>
            @endif
        </div>
    </div>
</div>
