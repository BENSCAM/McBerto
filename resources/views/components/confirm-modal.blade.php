<div
    x-data
    x-show="$store.confirmModal.show"
    x-cloak
    x-on:keydown.escape.window="$store.confirmModal.cancel()"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-[100]"
>
    <div
        x-on:click.outside="$store.confirmModal.cancel()"
        class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-sm shadow-xl mx-4"
    >
        <p class="text-gray-800 dark:text-gray-200 mb-6" x-text="$store.confirmModal.message"></p>
        <div class="flex gap-2">
            <button
                type="button"
                x-on:click="$store.confirmModal.cancel()"
                class="flex-1 border border-gray-300 dark:border-gray-600 rounded-md py-2 text-gray-700 dark:text-gray-300 font-medium"
            >Annuler</button>
            <button
                type="button"
                x-on:click="$store.confirmModal.confirm()"
                class="flex-1 bg-brand-600 text-white rounded-md py-2 font-medium"
            >Confirmer</button>
        </div>
    </div>
</div>
