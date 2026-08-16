<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Tableau de bord</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Vue : {{ $this->periodLabel }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1">
                    <button type="button" wire:click="$set('dashboardPeriod', 'day')" class="px-3 py-1.5 rounded text-sm font-medium {{ $dashboardPeriod === 'day' ? 'bg-brand-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">Jour</button>
                    <button type="button" wire:click="$set('dashboardPeriod', 'month')" class="px-3 py-1.5 rounded text-sm font-medium {{ $dashboardPeriod === 'month' ? 'bg-brand-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">Mois</button>
                    <button type="button" wire:click="$set('dashboardPeriod', 'year')" class="px-3 py-1.5 rounded text-sm font-medium {{ $dashboardPeriod === 'year' ? 'bg-brand-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">Année</button>
                </div>

                <a href="{{ route('pos.closing') }}" wire:navigate>
                    @if ($this->todayClosing)
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            🔒 Caisse clôturée
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">
                            ● Caisse ouverte
                        </span>
                    @endif
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Chiffre d'affaires</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->periodRevenue, 0, ',', ' ') }} FCFA</div>
                <x-dashboard.change-badge :percent="$this->periodRevenueChangePercent" />
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Nombre de commandes</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->periodOrdersCount }}</div>
                <x-dashboard.change-badge :percent="$this->periodOrdersChangePercent" />
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Ticket moyen</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->periodAverageTicket, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Bénéfice net</div>
                <div class="text-2xl font-semibold {{ $this->periodNetProfit >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-red-600 dark:text-red-400' }}">
                    {{ number_format($this->periodNetProfit, 0, ',', ' ') }} FCFA
                </div>
                <x-dashboard.change-badge :percent="$this->periodNetProfitChangePercent" />
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Commandes annulées</div>
                <div class="text-2xl font-semibold text-red-600 dark:text-red-400">{{ $this->periodCanceledOrdersCount }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($this->periodCanceledOrdersTotal, 0, ',', ' ') }} FCFA annulés</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <div class="font-medium text-gray-800 dark:text-gray-200">Évolution des ventes</div>
                <select wire:model.live="period" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                    <option value="7d">7 jours</option>
                    <option value="30d">30 jours</option>
                    <option value="12m">12 mois</option>
                </select>
            </div>

            <div
                wire:ignore
                x-data="(() => {
                    let chart = null;
                    let listener = null;

                    return {
                        init() {
                            const canvas = this.$refs.canvas;
                            Chart.getChart(canvas)?.destroy();

                            chart = new Chart(canvas, {
                                type: 'line',
                                data: {
                                    labels: @js($chart['labels']),
                                    datasets: [{
                                        label: 'Ventes (FCFA)',
                                        data: @js($chart['values']),
                                        borderColor: 'rgb(216, 15, 15)',
                                        backgroundColor: 'rgba(216, 15, 15, 0.1)',
                                        tension: 0.3,
                                        fill: true,
                                    }],
                                },
                                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } } },
                            });

                            listener = (event) => {
                                if (!chart || !event.detail?.chart) return;

                                chart.data.labels = [...event.detail.chart.labels];
                                chart.data.datasets[0].data = [...event.detail.chart.values];
                                chart.update('none');
                            };

                            window.addEventListener('chart-updated', listener);
                        },
                        destroy() {
                            if (listener) window.removeEventListener('chart-updated', listener);
                            if (chart) chart.destroy();
                            listener = null;
                            chart = null;
                        },
                    };
                })()"
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
            <div class="font-medium text-gray-800 dark:text-gray-200 mb-4">
                @if ($dashboardPeriod === 'year')
                    Ventes par mois
                @elseif ($dashboardPeriod === 'month')
                    Ventes par jour du mois
                @else
                    Ventes du jour par heure
                @endif
            </div>

            <div
                wire:ignore
                x-data="(() => {
                    let chart = null;
                    let listener = null;

                    return {
                        init() {
                            const canvas = this.$refs.hourlyCanvas;
                            Chart.getChart(canvas)?.destroy();

                            chart = new Chart(canvas, {
                                type: 'bar',
                                data: {
                                    labels: @js($periodBreakdown['labels']),
                                    datasets: [{
                                        label: 'Ventes (FCFA)',
                                        data: @js($periodBreakdown['values']),
                                        backgroundColor: 'rgba(216, 15, 15, 0.7)',
                                        borderRadius: 3,
                                    }],
                                },
                                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } } },
                            });

                            listener = (event) => {
                                if (!chart || !event.detail?.chart) return;

                                chart.data.labels = [...event.detail.chart.labels];
                                chart.data.datasets[0].data = [...event.detail.chart.values];
                                chart.update('none');
                            };

                            window.addEventListener('period-breakdown-updated', listener);
                        },
                        destroy() {
                            if (listener) window.removeEventListener('period-breakdown-updated', listener);
                            if (chart) chart.destroy();
                            listener = null;
                            chart = null;
                        },
                    };
                })()"
            >
                <canvas x-ref="hourlyCanvas"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="font-medium text-gray-800 dark:text-gray-200 mb-3">Répartition par mode de paiement</div>

                @if (empty($this->paymentMethodBreakdown))
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucune vente sur cette période.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($this->paymentMethodBreakdown as $row)
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $row['method']->label() }}</span>
                                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($row['amount'], 0, ',', ' ') }} FCFA · {{ $row['percent'] }}%</span>
                                </div>
                                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-brand-600" style="width: {{ $row['percent'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="font-medium text-gray-800 dark:text-gray-200 mb-3">Top produits vendus</div>

                @if ($this->topProducts->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucune vente sur cette période.</p>
                @else
                    <ol class="space-y-2">
                        @foreach ($this->topProducts as $i => $product)
                            <li class="flex items-center justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300">
                                    <span class="text-gray-400 dark:text-gray-500 mr-2">{{ $i + 1 }}.</span>{{ $product->product_name }}
                                </span>
                                <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $product->total_quantity }} vendus · {{ number_format($product->total_revenue, 0, ',', ' ') }} FCFA</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
            <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                <div>
                    <div class="font-medium text-gray-800 dark:text-gray-200">Commandes annulées</div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Annulations enregistrées sur la période affichée.</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-100">{{ $this->periodLabel }}</span>
            </div>

            @if ($this->recentCanceledOrders->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Aucune commande annulée sur cette période.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Ticket</th>
                                <th class="px-4 py-3">Annulée le</th>
                                <th class="px-4 py-3">Caissier</th>
                                <th class="px-4 py-3">Annulée par</th>
                                <th class="px-4 py-3">Zone</th>
                                <th class="px-4 py-3 text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($this->recentCanceledOrders as $sale)
                                <tr wire:key="dashboard-canceled-order-{{ $sale->id }}">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                        {{ $sale->receipt_number ?? '#'.$sale->id }}
                                        @if ($sale->cancellation_reason)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $sale->cancellation_reason }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $sale->canceled_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $sale->user?->name ?? 'Utilisateur supprimé' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $sale->canceledBy?->name ?? 'Système' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $sale->service_area->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-red-600 dark:text-red-400 text-right whitespace-nowrap">{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</div>
