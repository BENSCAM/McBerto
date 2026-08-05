<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Réinitialisation des données</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Remet les données d'exploitation à zéro avant le lancement réel.</p>
        </div>

        @if ($resetDone)
            <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 p-4 text-green-800 dark:text-green-100">
                Les données du dashboard ont été réinitialisées.
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-5">
            <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 p-4">
                <h3 class="font-semibold text-amber-900 dark:text-amber-100">Données qui seront supprimées</h3>
                <p class="text-sm text-amber-800 dark:text-amber-100 mt-1">Les utilisateurs, catégories et produits seront conservés.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($counts as $label => $count)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                        <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($count, 0, ',', ' ') }}</div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
                <label for="confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tapez REINITIALISER pour confirmer
                </label>
                <input
                    id="confirmation"
                    type="text"
                    wire:model="confirmation"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500"
                >
                <x-input-error :messages="$errors->get('confirmation')" class="mt-2" />
            </div>

            <button
                type="button"
                x-on:click="$store.confirmModal.open('Confirmer la réinitialisation des données du dashboard ? Cette action supprimera les ventes, dépenses, clôtures et historiques de test.', () => $wire.resetData())"
                wire:loading.attr="disabled"
                wire:target="resetData"
                class="w-full bg-red-600 text-white rounded-md py-3 font-semibold disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="resetData">Réinitialiser les données du dashboard</span>
                <span wire:loading wire:target="resetData">Réinitialisation...</span>
            </button>
        </div>
    </div>
</div>
