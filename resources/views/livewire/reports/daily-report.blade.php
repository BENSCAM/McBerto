<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Rapport journalier</h2>

            @if (auth()->user()->isAtLeastManager())
                <input type="date" wire:model.live="date" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" />
            @else
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</span>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Recettes ({{ $this->salesCount }} ventes)</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->revenue, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Dépenses</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->expensesTotal, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Bénéfice net</div>
                <div class="text-2xl font-semibold {{ $this->netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($this->netProfit, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <div class="px-6 py-3 font-medium text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700">Dépenses du jour</div>
            <table class="w-full text-left">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->expensesForDay as $expense)
                        <tr wire:key="report-expense-{{ $expense->id }}">
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ \App\Models\Expense::CATEGORIES[$expense->category] }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $expense->description }}</td>
                            <td class="px-6 py-3 text-gray-900 dark:text-gray-100 text-right">{{ number_format($expense->amount, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400" colspan="3">Aucune dépense ce jour.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
