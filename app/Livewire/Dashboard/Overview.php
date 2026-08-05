<?php

namespace App\Livewire\Dashboard;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\CashRegisterClosing;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Overview extends Component
{
    public string $dashboardPeriod = 'day';

    public string $period = '7d';

    public function updatedDashboardPeriod(): void
    {
        $this->dispatch('period-breakdown-updated', chart: $this->periodBreakdown());
    }

    public function updatedPeriod(): void
    {
        $this->dispatch('chart-updated', chart: $this->chartData());
    }

    #[Computed]
    public function todayRevenue(): int
    {
        return $this->revenueForDay(now());
    }

    #[Computed]
    public function todayOrdersCount(): int
    {
        return $this->ordersCountForDay(now());
    }

    #[Computed]
    public function averageTicket(): int
    {
        return $this->todayOrdersCount > 0
            ? (int) round($this->todayRevenue / $this->todayOrdersCount)
            : 0;
    }

    #[Computed]
    public function todayExpenses(): int
    {
        return (int) Expense::whereDate('expense_date', now())->sum('amount');
    }

    #[Computed]
    public function todayNetProfit(): int
    {
        return $this->todayRevenue - $this->todayExpenses;
    }

    #[Computed]
    public function yesterdayRevenue(): int
    {
        return $this->revenueForDay(now()->subDay());
    }

    #[Computed]
    public function yesterdayOrdersCount(): int
    {
        return $this->ordersCountForDay(now()->subDay());
    }

    #[Computed]
    public function yesterdayNetProfit(): int
    {
        $revenue = $this->revenueForDay(now()->subDay());
        $expenses = (int) Expense::whereDate('expense_date', now()->subDay())->sum('amount');

        return $revenue - $expenses;
    }

    #[Computed]
    public function revenueChangePercent(): ?float
    {
        return $this->percentChange($this->todayRevenue, $this->yesterdayRevenue);
    }

    #[Computed]
    public function ordersChangePercent(): ?float
    {
        return $this->percentChange($this->todayOrdersCount, $this->yesterdayOrdersCount);
    }

    #[Computed]
    public function netProfitChangePercent(): ?float
    {
        return $this->percentChange($this->todayNetProfit, $this->yesterdayNetProfit);
    }

    #[Computed]
    public function periodLabel(): string
    {
        [$start, $end] = $this->dashboardRange();

        return match ($this->dashboardPeriod) {
            'month' => $start->translatedFormat('F Y'),
            'year' => $start->format('Y'),
            default => $start->format('d/m/Y'),
        };
    }

    #[Computed]
    public function periodRevenue(): int
    {
        [$start, $end] = $this->dashboardRange();

        return $this->revenueForRange($start, $end);
    }

    #[Computed]
    public function periodOrdersCount(): int
    {
        [$start, $end] = $this->dashboardRange();

        return $this->ordersCountForRange($start, $end);
    }

    #[Computed]
    public function periodAverageTicket(): int
    {
        return $this->periodOrdersCount > 0
            ? (int) round($this->periodRevenue / $this->periodOrdersCount)
            : 0;
    }

    #[Computed]
    public function periodExpenses(): int
    {
        [$start, $end] = $this->dashboardRange();

        return (int) Expense::whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])->sum('amount');
    }

    #[Computed]
    public function periodNetProfit(): int
    {
        return $this->periodRevenue - $this->periodExpenses;
    }

    #[Computed]
    public function previousPeriodRevenue(): int
    {
        [$start, $end] = $this->previousDashboardRange();

        return $this->revenueForRange($start, $end);
    }

    #[Computed]
    public function previousPeriodOrdersCount(): int
    {
        [$start, $end] = $this->previousDashboardRange();

        return $this->ordersCountForRange($start, $end);
    }

    #[Computed]
    public function previousPeriodNetProfit(): int
    {
        [$start, $end] = $this->previousDashboardRange();
        $expenses = (int) Expense::whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])->sum('amount');

        return $this->revenueForRange($start, $end) - $expenses;
    }

    #[Computed]
    public function periodRevenueChangePercent(): ?float
    {
        return $this->percentChange($this->periodRevenue, $this->previousPeriodRevenue);
    }

    #[Computed]
    public function periodOrdersChangePercent(): ?float
    {
        return $this->percentChange($this->periodOrdersCount, $this->previousPeriodOrdersCount);
    }

    #[Computed]
    public function periodNetProfitChangePercent(): ?float
    {
        return $this->percentChange($this->periodNetProfit, $this->previousPeriodNetProfit);
    }

    /**
     * Null means "no baseline to compare against" (yesterday was zero),
     * rendered as "Nouveau" rather than a misleading infinite percentage.
     */
    protected function percentChange(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    #[Computed]
    public function todayClosing(): ?CashRegisterClosing
    {
        return CashRegisterClosing::whereDate('closing_date', now())->with('closedBy')->first();
    }

    #[Computed]
    public function paymentMethodBreakdown(): array
    {
        [$start, $end] = $this->dashboardRange();
        $sales = $this->effectiveSalesQuery($start, $end)->get();
        $total = $sales->sum('total_amount');

        return collect(PaymentMethod::cases())
            ->map(function (PaymentMethod $method) use ($sales, $total) {
                $amount = (int) $sales->where('payment_method', $method)->sum('total_amount');

                return [
                    'method' => $method,
                    'amount' => $amount,
                    'percent' => $total > 0 ? round($amount / $total * 100, 1) : 0,
                ];
            })
            ->filter(fn ($row) => $row['amount'] > 0)
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    #[Computed]
    public function topProducts()
    {
        [$start, $end] = $this->dashboardRange();

        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.sale_status', SaleStatus::Completed)
            ->where(function ($query) {
                $query->whereNotNull('sales.cash_register_closing_id')
                    ->orWhereNotExists(function ($subQuery) {
                        $subQuery->selectRaw('1')
                            ->from('cash_register_closings')
                            ->whereRaw('date(cash_register_closings.closing_date) = date(sales.created_at)');
                    });
            })
            ->selectRaw('sale_items.product_name, SUM(sale_items.quantity) as total_quantity, SUM(sale_items.subtotal) as total_revenue')
            ->groupBy('sale_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function hourlySales(): array
    {
        $sales = $this->effectiveSalesQuery()
            ->whereDate('created_at', now())
            ->get();

        $labels = [];
        $values = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $labels[] = sprintf('%dh', $hour);
            $values[] = (int) $sales
                ->filter(fn ($sale) => (int) $sale->created_at->format('G') === $hour)
                ->sum('total_amount');
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function periodBreakdown(): array
    {
        return match ($this->dashboardPeriod) {
            'month' => $this->currentMonthDailySeries(),
            'year' => $this->currentYearMonthlySeries(),
            default => $this->hourlySales(),
        };
    }

    public function chartData(): array
    {
        return match ($this->period) {
            '30d' => $this->dailySeries(30),
            '12m' => $this->monthlySeries(12),
            default => $this->dailySeries(7),
        };
    }

    protected function periodStart(): Carbon
    {
        return match ($this->period) {
            '30d' => now()->subDays(29)->startOfDay(),
            '12m' => now()->subMonths(11)->startOfMonth(),
            default => now()->subDays(6)->startOfDay(),
        };
    }

    protected function dashboardRange(): array
    {
        return match ($this->dashboardPeriod) {
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    protected function previousDashboardRange(): array
    {
        return match ($this->dashboardPeriod) {
            'month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
        };
    }

    protected function revenueForDay(Carbon $day): int
    {
        $closing = CashRegisterClosing::whereDate('closing_date', $day)->first();

        if ($closing) {
            return $closing->total_amount;
        }

        return (int) Sale::completed()
            ->whereNull('cash_register_closing_id')
            ->whereDate('created_at', $day)
            ->sum('total_amount');
    }

    protected function ordersCountForDay(Carbon $day): int
    {
        $closing = CashRegisterClosing::whereDate('closing_date', $day)->first();

        if ($closing) {
            return $closing->total_orders_count;
        }

        return Sale::completed()
            ->whereNull('cash_register_closing_id')
            ->whereDate('created_at', $day)
            ->count();
    }

    protected function revenueForRange(Carbon $start, Carbon $end): int
    {
        $closedTotal = (int) CashRegisterClosing::whereBetween('closing_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_amount');

        $openSalesTotal = (int) Sale::completed()
            ->whereNull('cash_register_closing_id')
            ->whereNotExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('cash_register_closings')
                    ->whereRaw('date(cash_register_closings.closing_date) = date(sales.created_at)');
            })
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        return $closedTotal + $openSalesTotal;
    }

    protected function ordersCountForRange(Carbon $start, Carbon $end): int
    {
        $closedCount = (int) CashRegisterClosing::whereBetween('closing_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_orders_count');

        $openSalesCount = Sale::completed()
            ->whereNull('cash_register_closing_id')
            ->whereNotExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('cash_register_closings')
                    ->whereRaw('date(cash_register_closings.closing_date) = date(sales.created_at)');
            })
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return $closedCount + $openSalesCount;
    }

    protected function effectiveSalesQuery(?Carbon $start = null, ?Carbon $end = null)
    {
        $query = Sale::completed()
            ->where(function ($query) {
                $query->whereNotNull('cash_register_closing_id')
                    ->orWhereNotExists(function ($subQuery) {
                        $subQuery->selectRaw('1')
                            ->from('cash_register_closings')
                            ->whereRaw('date(cash_register_closings.closing_date) = date(sales.created_at)');
                    });
            });

        if ($start) {
            $query->where('created_at', '>=', $start);
        }

        if ($end) {
            $query->where('created_at', '<=', $end);
        }

        return $query;
    }

    protected function dailySeries(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->format('d/m');
            $values[] = $this->revenueForDay($date);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function monthlySeries(int $months): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $labels = [];
        $values = [];

        for ($i = 0; $i < $months; $i++) {
            $date = $start->copy()->addMonths($i);
            $labels[] = $date->translatedFormat('M Y');
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            $closedTotal = (int) CashRegisterClosing::whereBetween('closing_date', [$monthStart, $monthEnd])->sum('total_amount');
            $openSalesTotal = (int) Sale::completed()
                ->whereNull('cash_register_closing_id')
                ->whereNotExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('cash_register_closings')
                        ->whereRaw('date(cash_register_closings.closing_date) = date(sales.created_at)');
                })
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('total_amount');

            $values[] = $closedTotal + $openSalesTotal;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function currentMonthDailySeries(): array
    {
        $start = now()->startOfMonth();
        $days = now()->daysInMonth;

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->format('d/m');
            $values[] = $this->revenueForDay($date);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function currentYearMonthlySeries(): array
    {
        $labels = [];
        $values = [];

        for ($month = 1; $month <= 12; $month++) {
            $date = now()->month($month)->startOfMonth();
            $labels[] = $date->translatedFormat('M');
            $values[] = $this->revenueForRange($date->copy()->startOfMonth(), $date->copy()->endOfMonth());
        }

        return ['labels' => $labels, 'values' => $values];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.dashboard.overview', [
            'chart' => $this->chartData(),
            'periodBreakdown' => $this->periodBreakdown(),
        ]);
    }
}
