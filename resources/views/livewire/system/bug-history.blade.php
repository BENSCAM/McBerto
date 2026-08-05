<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Historique des bugs</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Erreurs techniques capturées automatiquement par la plateforme.</p>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <span class="rounded-full bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-100 px-3 py-1">{{ $openCount }} ouvert(s)</span>
                <span class="rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100 px-3 py-1">{{ $resolvedCount }} résolu(s)</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-[1fr_180px] gap-3">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Rechercher message, exception, URL, fichier..."
                    class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500"
                >

                <select
                    wire:model.live="status"
                    class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500"
                >
                    <option value="open">Bugs ouverts</option>
                    <option value="resolved">Bugs résolus</option>
                    <option value="all">Tous les bugs</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_420px] gap-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Erreur</th>
                                <th class="px-6 py-3">Utilisateur</th>
                                <th class="px-6 py-3">Statut</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($logs as $bug)
                                <tr wire:key="bug-{{ $bug->id }}" class="align-top">
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ $bug->created_at->format('d/m/Y H:i') }}
                                        @if ($bug->ip_address)
                                            <div class="text-xs text-gray-400 dark:text-gray-500">{{ $bug->ip_address }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ class_basename($bug->exception_class) }}</div>
                                        <div class="text-gray-600 dark:text-gray-400 max-w-xl truncate">{{ $bug->message }}</div>
                                        @if ($bug->url)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">{{ $bug->method }} {{ $bug->url }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ $bug->user?->name ?? 'Visiteur/Système' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($bug->resolved_at)
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100">Résolu</span>
                                        @else
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-100">Ouvert</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            type="button"
                                            wire:click="selectBug({{ $bug->id }})"
                                            class="text-sm text-brand-600 dark:text-brand-400 underline"
                                        >Détails</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400" colspan="5">Aucun bug enregistré.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5 xl:sticky xl:top-6 self-start">
                @if ($selectedBug)
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ class_basename($selectedBug->exception_class) }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bug #{{ $selectedBug->id }}</p>
                        </div>
                        <button type="button" wire:click="closeDetails" class="text-sm text-gray-500 dark:text-gray-400 underline">Fermer</button>
                    </div>

                    <div class="mt-4 space-y-3 text-sm">
                        <div>
                            <div class="font-medium text-gray-700 dark:text-gray-300">Message</div>
                            <div class="text-gray-900 dark:text-gray-100 break-words">{{ $selectedBug->message }}</div>
                        </div>

                        <div>
                            <div class="font-medium text-gray-700 dark:text-gray-300">Emplacement</div>
                            <div class="text-gray-600 dark:text-gray-400 break-words">{{ $selectedBug->file }}:{{ $selectedBug->line }}</div>
                        </div>

                        <div>
                            <div class="font-medium text-gray-700 dark:text-gray-300">Requête</div>
                            <div class="text-gray-600 dark:text-gray-400 break-words">{{ $selectedBug->method }} {{ $selectedBug->url }}</div>
                        </div>

                        <div>
                            <div class="font-medium text-gray-700 dark:text-gray-300">Utilisateur</div>
                            <div class="text-gray-600 dark:text-gray-400">{{ $selectedBug->user?->name ?? 'Visiteur/Système' }}</div>
                        </div>

                        @if ($selectedBug->resolved_at)
                            <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-3 text-green-800 dark:text-green-100">
                                Résolu le {{ $selectedBug->resolved_at->format('d/m/Y H:i') }} par {{ $selectedBug->resolvedBy?->name ?? 'Utilisateur supprimé' }}.
                                @if ($selectedBug->resolution_note)
                                    <div class="mt-1">{{ $selectedBug->resolution_note }}</div>
                                @endif
                            </div>
                            <button
                                type="button"
                                wire:click="reopenBug({{ $selectedBug->id }})"
                                class="w-full border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-md py-2 font-medium"
                            >Réouvrir</button>
                        @else
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Note de résolution</label>
                                <textarea
                                    wire:model="resolutionNote"
                                    rows="3"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500"
                                ></textarea>
                            </div>
                            <button
                                type="button"
                                wire:click="resolveBug({{ $selectedBug->id }})"
                                class="w-full bg-brand-600 text-white rounded-md py-2 font-medium"
                            >Marquer comme résolu</button>
                        @endif

                        <details class="rounded-md border border-gray-200 dark:border-gray-700 p-3">
                            <summary class="cursor-pointer font-medium text-gray-700 dark:text-gray-300">Trace technique</summary>
                            <pre class="mt-3 max-h-80 overflow-auto text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ $selectedBug->trace }}</pre>
                        </details>
                    </div>
                @else
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sélectionnez un bug pour voir les détails techniques et le marquer comme résolu.</div>
                @endif
            </div>
        </div>

        {{ $logs->links() }}
    </div>
</div>
