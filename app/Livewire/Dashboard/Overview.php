<?php

namespace App\Livewire\Dashboard;

use App\Models\Sale;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Overview extends Component
{
    public string $period = '7d';

    public function updatedPeriod(): void
    {
        $this->dispatch('chart-updated', chart: $this->chartData());
    }

    #[Computed]
    public function todayRevenue(): int
    {
        return (int) Sale::whereDate('created_at', now())->sum('total_amount');
    }

    #[Computed]
    public function todayOrdersCount(): int
    {
        return Sale::whereDate('created_at', now())->count();
    }

    #[Computed]
    public function averageTicket(): int
    {
        return $this->todayOrdersCount > 0
            ? (int) round($this->todayRevenue / $this->todayOrdersCount)
            : 0;
    }

    public function chartData(): array
    {
        return match ($this->period) {
            '30d' => $this->dailySeries(30),
            '12m' => $this->monthlySeries(12),
            default => $this->dailySeries(7),
        };
    }

    protected function dailySeries(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $sales = Sale::where('created_at', '>=', $start)
            ->get()
            ->groupBy(fn ($sale) => $sale->created_at->format('Y-m-d'));

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
            $values[] = (int) ($sales->get($key)?->sum('total_amount') ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function monthlySeries(int $months): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $sales = Sale::where('created_at', '>=', $start)
            ->get()
            ->groupBy(fn ($sale) => $sale->created_at->format('Y-m'));

        $labels = [];
        $values = [];

        for ($i = 0; $i < $months; $i++) {
            $date = $start->copy()->addMonths($i);
            $key = $date->format('Y-m');
            $labels[] = $date->translatedFormat('M Y');
            $values[] = (int) ($sales->get($key)?->sum('total_amount') ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.dashboard.overview', [
            'chart' => $this->chartData(),
        ]);
    }
}
