<x-app-layout>
    <div class="py-12" x-data x-init="$nextTick(() => $store.confirmModal.open('Supprimer cette catégorie ?', () => {}))">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <p>Test page</p>
        </div>
    </div>
</x-app-layout>
