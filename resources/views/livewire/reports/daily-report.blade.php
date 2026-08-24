<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Rapport</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Résumé journalier et export management PDF.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                @if (auth()->user()->isAtLeastManager())
                    <select wire:model.live="period" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                        <option value="day">Jour</option>
                        <option value="month">Mois</option>
                        <option value="year">Année</option>
                    </select>
                    <input type="date" wire:model.live="date" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" />
                    <a href="{{ $this->managementReportPdfUrl() }}" target="_blank" class="inline-flex items-center justify-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Exporter PDF
                    </a>
                @else
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Chiffre d'affaires ({{ $this->salesCount }} ventes)</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->revenue, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Coût matière consommée</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->materialCost, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Marge brute</div>
                <div class="text-2xl font-semibold {{ $this->grossMargin >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($this->grossMargin, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Dépenses générales</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->operatingExpensesTotal, 0, ',', ' ') }} FCFA</div>
                @if ($this->payrollTotal > 0)
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">dont salaires: {{ number_format($this->payrollTotal, 0, ',', ' ') }} FCFA</div>
                @endif
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Bénéfice net réel</div>
                <div class="text-2xl font-semibold {{ $this->netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($this->netProfit, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Achats matières sur la période</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->rawMaterialPurchasesTotal, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Salaires automatiques</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->payrollTotal, 0, ',', ' ') }} FCFA</div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pris en compte sur les rapports mois et année.</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Alertes stock bas</div>
            <div class="text-xl font-semibold text-amber-700 dark:text-amber-300">{{ $this->lowStockMaterials->count() }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
            <div class="font-medium text-gray-800 dark:text-gray-200 mb-3">Recettes par zone</div>
            <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($this->serviceAreaOptions() as $serviceArea)
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-600 dark:text-gray-400">{{ $serviceArea->label() }} ({{ $this->serviceAreaSalesCount($serviceArea) }} ventes)</dt>
                        <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($this->serviceAreaRevenue($serviceArea), 0, ',', ' ') }} FCFA</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <div class="px-6 py-3 font-medium text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700">Dépenses générales</div>
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
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400" colspan="3">Aucune dépense générale sur la période.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (auth()->user()->isAtLeastManager())
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 font-medium text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700">Produits les plus rentables</div>
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($this->productMargins as $row)
                                <tr>
                                    <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $row->product_name }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ (int) $row->quantity }} vendu(s)</td>
                                    <td class="px-6 py-3 text-right text-gray-900 dark:text-gray-100">{{ number_format((int) $row->revenue - (int) $row->material_cost, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @empty
                                <tr><td class="px-6 py-3 text-gray-500 dark:text-gray-400" colspan="3">Aucune vente sur la période.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 font-medium text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700">Matières les plus consommées</div>
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($this->consumedMaterials as $row)
                                <tr>
                                    <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $row->name }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ number_format((float) $row->quantity, 3, ',', ' ') }} {{ \App\Models\RawMaterial::UNITS[$row->unit] ?? $row->unit }}</td>
                                    <td class="px-6 py-3 text-right text-gray-900 dark:text-gray-100">{{ number_format((int) $row->total_cost, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @empty
                                <tr><td class="px-6 py-3 text-gray-500 dark:text-gray-400" colspan="3">Aucune consommation calculée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-3 font-medium text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700">Alertes stock bas</div>
                <table class="w-full text-left">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->lowStockMaterials as $material)
                            <tr>
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $material->name }}</td>
                                <td class="px-6 py-3 text-amber-700 dark:text-amber-300">{{ number_format((float) $material->current_quantity, 3, ',', ' ') }} {{ \App\Models\RawMaterial::UNITS[$material->unit] }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">Seuil: {{ number_format((float) $material->low_stock_threshold, 3, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-3 text-gray-500 dark:text-gray-400" colspan="3">Aucune alerte stock bas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
