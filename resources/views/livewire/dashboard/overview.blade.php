<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Tableau de bord</h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Chiffre d'affaires du jour</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->todayRevenue, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Nombre de commandes</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->todayOrdersCount }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Ticket moyen</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->averageTicket, 0, ',', ' ') }} FCFA</div>
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
                                    borderColor: 'rgb(79, 70, 229)',
                                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
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
    </div>
</div>
