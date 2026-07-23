<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Clôture de caisse — {{ now()->translatedFormat('d/m/Y') }}</h2>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
            @if ($existingClosing)
                <div class="rounded-md bg-green-50 dark:bg-green-900 p-3 text-green-800 dark:text-green-200 text-sm">
                    Caisse déjà clôturée aujourd'hui par {{ $existingClosing->closedBy->name }} à {{ $existingClosing->created_at->format('H:i') }}.
                </div>

                <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">Espèces</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($existingClosing->total_cash, 0, ',', ' ') }} FCFA</dd>
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
            @else
                <p class="text-gray-600 dark:text-gray-400 text-sm">Récapitulatif des ventes non encore clôturées aujourd'hui :</p>

                <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">Espèces</dt>
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

                <button
                    wire:click="close"
                    wire:confirm="Confirmer la clôture de caisse du jour ? Cette action est définitive."
                    class="w-full bg-brand-600 text-white rounded-md py-2 font-medium"
                    @disabled($pendingCount === 0)
                >
                    Clôturer la caisse
                </button>
            @endif
        </div>
    </div>
</div>
