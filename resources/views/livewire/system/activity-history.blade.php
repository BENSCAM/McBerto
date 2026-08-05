<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Historique système</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Actions enregistrées par les utilisateurs connectés.</p>
            </div>
            <button
                type="button"
                wire:click="clearFilters"
                class="text-sm text-brand-600 dark:text-brand-400 underline"
            >Réinitialiser</button>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-[1fr_180px_220px] gap-3">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Rechercher une action, un module, une IP..."
                    class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500"
                >

                <select
                    wire:model.live="action"
                    class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500"
                >
                    <option value="">Toutes les actions</option>
                    @foreach ($actions as $availableAction)
                        <option value="{{ $availableAction }}">{{ __("activity.{$availableAction}") === "activity.{$availableAction}" ? ucfirst($availableAction) : __("activity.{$availableAction}") }}</option>
                    @endforeach
                </select>

                <select
                    wire:model.live="userId"
                    class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500"
                >
                    <option value="">Tous les utilisateurs</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Utilisateur</th>
                            <th class="px-6 py-3">Action</th>
                            <th class="px-6 py-3">Détail</th>
                            <th class="px-6 py-3">Changements</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($logs as $log)
                            <tr wire:key="activity-{{ $log->id }}" class="align-top">
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                    @if ($log->ip_address)
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ $log->ip_address }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    {{ $log->user?->name ?? 'Système' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $log->action === 'created' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100' : ($log->action === 'deleted' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-100' : 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-100') }}">
                                        {{ ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression'][$log->action] ?? ucfirst($log->action) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $log->description }}
                                    @if ($log->subject_type)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 min-w-72">
                                    @if ($log->action === 'updated' && $log->new_values)
                                        <div class="space-y-1">
                                            @foreach ($log->new_values as $field => $value)
                                                <div>
                                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ str_replace('_', ' ', $field) }}</span> :
                                                    <span class="text-gray-500 dark:text-gray-400">{{ data_get($log->old_values, $field, '—') }}</span>
                                                    <span class="text-gray-400">→</span>
                                                    <span>{{ is_bool($value) ? ($value ? 'Oui' : 'Non') : $value }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif ($log->new_values)
                                        <div class="text-xs leading-relaxed">
                                            @foreach (array_slice($log->new_values, 0, 5) as $field => $value)
                                                <span class="inline-block mr-2"><span class="font-medium">{{ str_replace('_', ' ', $field) }}</span>: {{ is_bool($value) ? ($value ? 'Oui' : 'Non') : $value }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400" colspan="5">Aucune activité enregistrée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $logs->links() }}
    </div>
</div>
