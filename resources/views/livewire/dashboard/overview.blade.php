<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Tableau de bord</h2>

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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Chiffre d'affaires du jour</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->todayRevenue, 0, ',', ' ') }} FCFA</div>
                <x-dashboard.change-badge :percent="$this->revenueChangePercent" />
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Nombre de commandes</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->todayOrdersCount }}</div>
                <x-dashboard.change-badge :percent="$this->ordersChangePercent" />
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Ticket moyen</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->averageTicket, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Bénéfice net du jour</div>
                <div class="text-2xl font-semibold {{ $this->todayNetProfit >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-red-600 dark:text-red-400' }}">
                    {{ number_format($this->todayNetProfit, 0, ',', ' ') }} FCFA
                </div>
                <x-dashboard.change-badge :percent="$this->netProfitChangePercent" />
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
                x-data="{
                    chartInstance: null,
                    init() {
                        this.chartInstance = new Chart(this.$refs.canvas, {
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
                            options: { responsive: true, plugins: { legend: { display: false } } },
                        });

                        window.addEventListener('chart-updated', (event) => {
                            this.chartInstance.data.labels = event.detail.chart.labels;
                            this.chartInstance.data.datasets[0].data = event.detail.chart.values;
                            this.chartInstance.update();
                        });
                    },
                }"
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
            <div class="font-medium text-gray-800 dark:text-gray-200 mb-4">Ventes d'aujourd'hui par heure</div>

            <div
                wire:ignore
                x-data="{
                    chartInstance: null,
                    init() {
                        this.chartInstance = new Chart(this.$refs.hourlyCanvas, {
                            type: 'bar',
                            data: {
                                labels: @js($this->hourlySales['labels']),
                                datasets: [{
                                    label: 'Ventes (FCFA)',
                                    data: @js($this->hourlySales['values']),
                                    backgroundColor: 'rgba(216, 15, 15, 0.7)',
                                    borderRadius: 3,
                                }],
                            },
                            options: { responsive: true, plugins: { legend: { display: false } } },
                        });
                    },
                }"
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
    </div>
</div>
