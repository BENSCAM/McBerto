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
                        <option value="{{ $this->formatActivityValue($availableAction) }}">{{ $this->formatActivityAction($availableAction) }}</option>
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

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                <div>
                    <h3 class="font-medium text-gray-800 dark:text-gray-200">Tickets de caisse</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Dernières commandes enregistrées avec accès au ticket détaillé.</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $this->orderHistory->count() }} ticket(s)</span>
            </div>

            @if ($this->orderHistory->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Aucun ticket enregistré.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Ticket</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Caissier</th>
                                <th class="px-4 py-3">Zone</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($this->orderHistory as $sale)
                                <tr wire:key="history-ticket-{{ $sale->id }}">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $sale->receipt_number ?? '#'.$sale->id }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $sale->user?->name ?? 'Utilisateur supprimé' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $sale->service_area->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100 text-right whitespace-nowrap">{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            type="button"
                                            wire:click="viewOrderTicket({{ $sale->id }})"
                                            class="inline-flex items-center rounded-md bg-brand-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-700"
                                        >Voir</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
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
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ $this->formatActivityValue($log->ip_address) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    {{ $this->formatActivityValue($log->user?->name ?? 'Système') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $log->action === 'created' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100' : ($log->action === 'deleted' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-100' : 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-100') }}">
                                        {{ $this->formatActivityAction($log->action) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $this->formatActivityValue($log->description) }}
                                    @if ($log->subject_type)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $this->formatActivityValue(class_basename($log->subject_type)) }} #{{ $this->formatActivityValue($log->subject_id) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 min-w-72">
                                    @if ($log->action === 'updated' && $log->new_values)
                                        <div class="space-y-1">
                                            @foreach ($log->new_values as $field => $value)
                                                <div>
                                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $this->formatActivityField($field) }}</span> :
                                                    <span class="text-gray-500 dark:text-gray-400 break-words [overflow-wrap:anywhere]">{{ $this->formatActivityValue(data_get($log->old_values, $field)) }}</span>
                                                    <span class="text-gray-400">→</span>
                                                    <span class="break-words [overflow-wrap:anywhere]">{{ $this->formatActivityValue($value) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif ($log->new_values)
                                        <div class="text-xs leading-relaxed">
                                            @foreach (array_slice($log->new_values, 0, 5) as $field => $value)
                                                <span class="inline-block mr-2 break-words [overflow-wrap:anywhere]"><span class="font-medium">{{ $this->formatActivityField($field) }}</span>: {{ $this->formatActivityValue($value) }}</span>
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

    @if ($this->selectedOrderTicket)
        <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" wire:click.self="closeOrderTicket">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between gap-4 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">Ticket détaillé</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->selectedOrderTicket->receipt_number ?? '#'.$this->selectedOrderTicket->id }}</p>
                    </div>
                    <button type="button" wire:click="closeOrderTicket" class="text-sm text-gray-500 dark:text-gray-400 underline">Fermer</button>
                </div>

                <div class="p-6">
                    <div class="font-mono">
                        <div class="text-center mb-4">
                            <h4 class="font-bold text-lg text-gray-900 dark:text-gray-100">McBerto</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Ticket {{ $this->selectedOrderTicket->receipt_number ?? '#'.$this->selectedOrderTicket->id }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Zone : {{ $this->selectedOrderTicket->service_area->label() }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $this->selectedOrderTicket->created_at->format('d/m/Y H:i') }} - {{ $this->selectedOrderTicket->user?->name ?? 'Utilisateur supprimé' }}</p>
                        </div>

                        <div class="divide-y divide-gray-200 dark:divide-gray-700 border-y border-gray-200 dark:border-gray-700 mb-4">
                            @foreach ($this->selectedOrderTicket->items as $item)
                                <div class="py-2 text-sm">
                                    <div class="flex justify-between gap-3">
                                        <span class="min-w-0 text-gray-700 dark:text-gray-300 break-words [overflow-wrap:anywhere]">{{ $item->quantity }} x {{ $item->product_name }}</span>
                                        <span class="text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA / unité</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-100">
                                <span>Total</span>
                                <span>{{ number_format($this->selectedOrderTicket->total_amount, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                <span>Paiement</span>
                                <span>{{ $this->selectedOrderTicket->payment_method->label() }}</span>
                            </div>
                            @if ($this->selectedOrderTicket->amount_given !== null)
                                <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                    <span>Montant donné</span>
                                    <span>{{ number_format($this->selectedOrderTicket->amount_given, 0, ',', ' ') }} FCFA</span>
                                </div>
                                <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                    <span>Monnaie rendue</span>
                                    <span>{{ number_format($this->selectedOrderTicket->change_due, 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endif
                        </div>

                        <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-4">Merci de votre visite !</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
