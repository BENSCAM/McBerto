<div class="py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Tableau de bord</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Vue : {{ $this->periodLabel }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1">
                    <button type="button" wire:click="$set('dashboardPeriod', 'day')" class="px-3 py-1.5 rounded text-sm font-medium {{ $dashboardPeriod === 'day' ? 'bg-brand-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">Jour</button>
                    <button type="button" wire:click="$set('dashboardPeriod', 'month')" class="px-3 py-1.5 rounded text-sm font-medium {{ $dashboardPeriod === 'month' ? 'bg-brand-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">Mois</button>
                    <button type="button" wire:click="$set('dashboardPeriod', 'cycle')" class="px-3 py-1.5 rounded text-sm font-medium {{ $dashboardPeriod === 'cycle' ? 'bg-brand-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">Cycle</button>
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

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-6 gap-4">
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
                <div class="text-sm text-gray-500 dark:text-gray-400">Dépenses générales</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->periodExpenses, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Salaires nets à payer</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->periodPayrollTotal, 0, ',', ' ') }} FCFA</div>
                @if ($dashboardPeriod === 'day')
                    <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">Pris en compte au mois / année</div>
                @else
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Brut: {{ number_format($this->periodUserPayrollGross + $this->periodStaffPayrollGross, 0, ',', ' ') }} · Retenues: {{ number_format($this->periodPayrollDeductions, 0, ',', ' ') }}
                    </div>
                @endif
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Bénéfice net réel</div>
                <div class="text-2xl font-semibold {{ $this->periodNetProfit >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-red-600 dark:text-red-400' }}">
                    {{ number_format($this->periodNetProfit, 0, ',', ' ') }} FCFA
                </div>
                <x-dashboard.change-badge :percent="$this->periodNetProfitChangePercent" />
            </div>
        </div>

        @if ($dashboardPeriod !== 'day')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Comptes système à payer</div>
                    <div class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->periodUserPayroll, 0, ',', ' ') }} FCFA</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Brut {{ number_format($this->periodUserPayrollGross, 0, ',', ' ') }} · Retenues {{ number_format($this->periodUserPayrollDeductions, 0, ',', ' ') }}
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Personnel sans accès à payer</div>
                    <div class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->periodStaffPayroll, 0, ',', ' ') }} FCFA</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Brut {{ number_format($this->periodStaffPayrollGross, 0, ',', ' ') }} · Retenues {{ number_format($this->periodStaffPayrollDeductions, 0, ',', ' ') }}
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Dépenses opérationnelles</div>
                    <div class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->periodOperatingExpenses, 0, ',', ' ') }} FCFA</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Dépenses + salaires nets
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-medium text-gray-800 dark:text-gray-200">Alertes stock</div>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->criticalStockCount }} critique(s) · {{ $this->watchStockCount }} à surveiller
                    </div>
                </div>

                <a href="{{ route('raw-materials.index') }}" wire:navigate class="inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Voir les stocks
                </a>
            </div>

            @if ($this->stockAlerts->isEmpty())
                <div class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">Aucune matière première critique ou proche du seuil.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-5 py-3">Matière</th>
                                <th class="px-5 py-3">Stock actuel</th>
                                <th class="px-5 py-3">Seuil</th>
                                <th class="px-5 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($this->stockAlerts as $alert)
                                <tr>
                                    <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $alert['name'] }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ number_format($alert['current_quantity'], 3, ',', ' ') }} {{ $alert['unit'] }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ number_format($alert['threshold'], 3, ',', ' ') }} {{ $alert['unit'] }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $alert['status'] === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-100' : 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-100' }}">
                                            {{ $alert['label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 2xl:grid-cols-2 gap-4">
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
                    class="h-[320px] xl:h-[380px]"
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
                                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
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
                    @elseif ($dashboardPeriod === 'cycle')
                        Ventes par jour du cycle
                    @elseif ($dashboardPeriod === 'month')
                        Ventes par jour du mois
                    @else
                        Ventes du jour par heure
                    @endif
                </div>

                <div
                    wire:ignore
                    class="h-[320px] xl:h-[380px]"
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
                                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
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

    </div>
</div>
