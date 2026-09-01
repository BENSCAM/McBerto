<div class="py-12" wire:poll.visible.15s>
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Historique des clôtures</h2>
            <a href="{{ route('pos.closing') }}" wire:navigate class="text-sm text-brand-600 dark:text-brand-400 underline">Clôture du jour</a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Clôturé par</th>
                        <th class="px-6 py-3">Espèces (syst./compté)</th>
                        <th class="px-6 py-3">Écart</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Ventes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($closings as $closing)
                        <tr wire:key="closing-{{ $closing->id }}">
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $closing->closing_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $closing->closedBy->name }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">
                                {{ number_format($closing->total_cash, 0, ',', ' ') }} / {{ number_format($closing->counted_cash, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-6 py-3 font-medium {{ $closing->variance == 0 ? 'text-green-600' : ($closing->variance < 0 ? 'text-red-600' : 'text-blue-600') }}">
                                @if ($closing->variance == 0)
                                    —
                                @else
                                    {{ $closing->variance > 0 ? '+' : '' }}{{ number_format($closing->variance, 0, ',', ' ') }}
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ number_format($closing->total_amount, 0, ',', ' ') }} FCFA</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $closing->total_orders_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400" colspan="6">Aucune clôture enregistrée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $closings->links() }}
    </div>
</div>
