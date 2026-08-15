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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                    <div class="font-medium text-gray-800 dark:text-gray-200">Historique des commandes</div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Contenu des 20 dernières commandes de la période affichée.</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $this->periodLabel }}</span>
            </div>

            @if ($this->orderHistory->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Aucune commande sur cette période.</p>
            @else
                <div class="space-y-4">
                    @foreach ($this->orderHistory as $sale)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden" wire:key="dashboard-order-history-{{ $sale->id }}">
                            <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">Ticket {{ $sale->receipt_number ?? '#'.$sale->id }}</span>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $sale->service_area->label() }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $sale->created_at->format('d/m/Y H:i') }} · {{ $sale->payment_method->label() }} · {{ $sale->user?->name ?? 'Utilisateur supprimé' }}
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $sale->items->sum('quantity') }} article(s)</div>
                                </div>
                            </div>

                            <div class="px-4 py-3">
                                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-2">Constitution du ticket</div>

                                <div class="hidden sm:grid grid-cols-[minmax(0,1fr)_90px_60px_110px] gap-3 text-xs font-medium text-gray-500 dark:text-gray-400 pb-2 border-b border-gray-100 dark:border-gray-700">
                                    <div>Produit</div>
                                    <div class="text-right">PU</div>
                                    <div class="text-right">Qté</div>
                                    <div class="text-right">Sous-total</div>
                                </div>

                                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($sale->items as $item)
                                        <div class="py-2 grid grid-cols-[minmax(0,1fr)_auto] sm:grid-cols-[minmax(0,1fr)_90px_60px_110px] gap-3 text-sm">
                                            <div class="min-w-0 text-gray-700 dark:text-gray-300 break-words [overflow-wrap:anywhere]">
                                                {{ $item->product_name }}
                                            </div>
                                            <div class="text-right text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</div>
                                            <div class="text-right text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $item->quantity }}</div>
                                            <div class="text-right font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-3 border-t border-gray-100 dark:border-gray-700 pt-3 space-y-1 text-sm">
                                    <div class="flex justify-between gap-3">
                                        <span class="text-gray-500 dark:text-gray-400">Total ticket</span>
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</span>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <span class="text-gray-500 dark:text-gray-400">Paiement</span>
                                        <span class="text-gray-700 dark:text-gray-300">{{ $sale->payment_method->label() }}</span>
                                    </div>
                                    @if ($sale->amount_given !== null)
                                        <div class="flex justify-between gap-3">
                                            <span class="text-gray-500 dark:text-gray-400">Montant donné</span>
                                            <span class="text-gray-700 dark:text-gray-300">{{ number_format($sale->amount_given, 0, ',', ' ') }} FCFA</span>
                                        </div>
                                        <div class="flex justify-between gap-3">
                                            <span class="text-gray-500 dark:text-gray-400">Monnaie rendue</span>
                                            <span class="text-gray-700 dark:text-gray-300">{{ number_format($sale->change_due, 0, ',', ' ') }} FCFA</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
