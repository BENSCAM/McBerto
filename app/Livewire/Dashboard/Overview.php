<?php

namespace App\Livewire\Dashboard;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\CashRegisterClosing;
use App\Models\DisciplinarySanction;
use App\Models\Expense;
use App\Models\RawMaterial;
use App\Models\RawMaterialStockMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Overview extends Component
{
    public string $dashboardPeriod = 'day';

    public string $period = '7d';

    public string $deleteStartDate = '';

    public string $deleteEndDate = '';

    public string $deleteConfirmation = '';

    public ?array $lastDeletedOrders = null;

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
            'cycle' => $start->format('d/m/Y').' - '.$end->format('d/m/Y'),
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
    public function periodUserPayrollGross(): int
    {
        return $this->payrollAmountFor($this->dashboardPeriod, (int) User::where('is_active', true)->sum('monthly_salary'));
    }

    #[Computed]
    public function periodStaffPayrollGross(): int
    {
        return $this->payrollAmountFor($this->dashboardPeriod, (int) StaffMember::where('is_active', true)->sum('monthly_salary'));
    }

    #[Computed]
    public function periodUserPayrollDeductions(): int
    {
        return $this->payrollDeductionsFor('user');
    }

    #[Computed]
    public function periodStaffPayrollDeductions(): int
    {
        return $this->payrollDeductionsFor('staff');
    }

    #[Computed]
    public function periodPayrollDeductions(): int
    {
        return $this->periodUserPayrollDeductions + $this->periodStaffPayrollDeductions;
    }

    #[Computed]
    public function periodUserPayroll(): int
    {
        return max(0, $this->periodUserPayrollGross - $this->periodUserPayrollDeductions);
    }

    #[Computed]
    public function periodStaffPayroll(): int
    {
        return max(0, $this->periodStaffPayrollGross - $this->periodStaffPayrollDeductions);
    }

    #[Computed]
    public function periodPayrollTotal(): int
    {
        return $this->periodUserPayroll + $this->periodStaffPayroll;
    }

    #[Computed]
    public function periodOperatingExpenses(): int
    {
        return $this->periodExpenses + $this->periodPayrollTotal;
    }

    #[Computed]
    public function periodNetProfit(): int
    {
        return $this->periodRevenue - $this->periodOperatingExpenses;
    }

    #[Computed]
    public function periodCanceledOrdersCount(): int
    {
        [$start, $end] = $this->dashboardRange();

        return $this->canceledSalesQuery($start, $end)->count();
    }

    #[Computed]
    public function periodCanceledOrdersTotal(): int
    {
        [$start, $end] = $this->dashboardRange();

        return (int) $this->canceledSalesQuery($start, $end)->sum('total_amount');
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
        $grossPayroll = $this->payrollAmountFor($this->dashboardPeriod);
        $payrollDeductions = $this->payrollDeductionsBetween($start, $end, 'user') + $this->payrollDeductionsBetween($start, $end, 'staff');

        return $this->revenueForRange($start, $end) - $expenses - max(0, $grossPayroll - $payrollDeductions);
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
    public function recentCanceledOrders()
    {
        [$start, $end] = $this->dashboardRange();

        return $this->canceledSalesQuery($start, $end)
            ->with(['user', 'canceledBy'])
            ->latest('canceled_at')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function stockAlerts()
    {
        return RawMaterial::query()
            ->where('is_active', true)
            ->where('low_stock_threshold', '>', 0)
            ->whereColumn('current_quantity', '<=', 'low_stock_threshold')
            ->orWhere(function ($query) {
                $query->where('is_active', true)
                    ->where('low_stock_threshold', '>', 0)
                    ->whereRaw('current_quantity <= low_stock_threshold * 2');
            })
            ->orderByRaw('current_quantity / low_stock_threshold asc')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(function (RawMaterial $material) {
                $currentQuantity = (float) $material->current_quantity;
                $threshold = (float) $material->low_stock_threshold;
                $isCritical = $currentQuantity <= $threshold;

                return [
                    'name' => $material->name,
                    'current_quantity' => $currentQuantity,
                    'threshold' => $threshold,
                    'unit' => RawMaterial::UNITS[$material->unit] ?? $material->unit,
                    'status' => $isCritical ? 'critical' : 'watch',
                    'label' => $isCritical ? 'Critique' : 'À surveiller',
                ];
            });
    }

    #[Computed]
    public function criticalStockCount(): int
    {
        return $this->stockAlerts->where('status', 'critical')->count();
    }

    #[Computed]
    public function watchStockCount(): int
    {
        return $this->stockAlerts->where('status', 'watch')->count();
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
            'cycle' => $this->currentOperationCycleDailySeries(),
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

    public function deleteOrdersForPeriod(): void
    {
        $this->validate([
            'deleteStartDate' => ['required', 'date_format:Y-m-d'],
            'deleteEndDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:deleteStartDate'],
            'deleteConfirmation' => ['required', 'in:SUPPRIMER'],
        ], [
            'deleteStartDate.required' => 'Choisissez la date de début.',
            'deleteStartDate.date_format' => 'La date de début est invalide.',
            'deleteEndDate.required' => 'Choisissez la date de fin.',
            'deleteEndDate.date_format' => 'La date de fin est invalide.',
            'deleteEndDate.after_or_equal' => 'La date de fin doit être supérieure ou égale à la date de début.',
            'deleteConfirmation.in' => 'Tapez SUPPRIMER pour confirmer.',
        ]);

        $start = Carbon::createFromFormat('Y-m-d', $this->deleteStartDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $this->deleteEndDate)->endOfDay();

        $this->lastDeletedOrders = DB::transaction(function () use ($start, $end) {
            $sales = Sale::query()
                ->with('rawMaterialStockMovements')
                ->whereBetween('created_at', [$start, $end])
                ->get();

            $saleIds = $sales->pluck('id');
            $closingCount = $this->cashRegisterClosingsBetween($start, $end)->count();
            $totalAmount = (int) $sales->sum('total_amount');
            $ordersCount = $sales->count();
            $itemsCount = SaleItem::whereIn('sale_id', $saleIds)->count();

            foreach ($sales->where('sale_status', SaleStatus::Completed) as $sale) {
                $this->restoreStockConsumedBySale($sale);
            }

            RawMaterialStockMovement::withoutEvents(fn () => RawMaterialStockMovement::whereIn('sale_id', $saleIds)->delete());
            SaleItem::withoutEvents(fn () => SaleItem::whereIn('sale_id', $saleIds)->delete());
            Sale::withoutEvents(fn () => Sale::whereIn('id', $saleIds)->delete());
            CashRegisterClosing::withoutEvents(fn () => $this->cashRegisterClosingsBetween($start, $end)->delete());

            return [
                'orders' => $ordersCount,
                'items' => $itemsCount,
                'closings' => $closingCount,
                'amount' => $totalAmount,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ];
        });

        $this->deleteConfirmation = '';
        unset($this->todayClosing);
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
            'cycle' => $this->operationCycleRange(now()),
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    protected function previousDashboardRange(): array
    {
        return match ($this->dashboardPeriod) {
            'month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'cycle' => $this->previousOperationCycleRange(now()),
            'year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
        };
    }

    protected function restoreStockConsumedBySale(Sale $sale): void
    {
        $movements = $sale->rawMaterialStockMovements
            ->where('type', 'sale_consumption');

        foreach ($movements as $movement) {
            $material = RawMaterial::whereKey($movement->raw_material_id)->lockForUpdate()->first();

            if (! $material) {
                continue;
            }

            $material->update([
                'current_quantity' => (float) $material->current_quantity + (float) $movement->quantity_out,
            ]);
        }
    }

    protected function operationCycleRange(Carbon $date): array
    {
        $startDay = $this->operationCycleStartDay();
        $start = $date->copy()->startOfDay();

        if ($start->day >= $startDay) {
            $start->day($startDay);
        } else {
            $start->subMonthNoOverflow()->day($startDay);
        }

        $end = $start->copy()->addMonthNoOverflow()->subDay()->endOfDay();

        return [$start, $end];
    }

    protected function previousOperationCycleRange(Carbon $date): array
    {
        [$start] = $this->operationCycleRange($date);
        $previousStart = $start->copy()->subMonthNoOverflow()->startOfDay();
        $previousEnd = $start->copy()->subDay()->endOfDay();

        return [$previousStart, $previousEnd];
    }

    protected function operationCycleStartDay(): int
    {
        return min(max((int) config('mcberto.operations.cycle_start_day', 14), 1), 28);
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
        $closedTotal = (int) $this->cashRegisterClosingsBetween($start, $end)->sum('total_amount');

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
        $closedCount = (int) $this->cashRegisterClosingsBetween($start, $end)->sum('total_orders_count');

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

    protected function cashRegisterClosingsBetween(Carbon $start, Carbon $end)
    {
        return CashRegisterClosing::query()
            ->whereDate('closing_date', '>=', $start->toDateString())
            ->whereDate('closing_date', '<=', $end->toDateString());
    }

    protected function payrollAmountFor(string $period, ?int $monthlyPayroll = null): int
    {
        $monthlyPayroll ??= (int) User::where('is_active', true)->sum('monthly_salary')
            + (int) StaffMember::where('is_active', true)->sum('monthly_salary');

        return match ($period) {
            'month' => $monthlyPayroll,
            'cycle' => $monthlyPayroll,
            'year' => $monthlyPayroll * 12,
            default => 0,
        };
    }

    protected function payrollDeductionsFor(string $employeeType): int
    {
        if ($this->dashboardPeriod === 'day') {
            return 0;
        }

        [$start, $end] = $this->dashboardRange();

        return $this->payrollDeductionsBetween($start, $end, $employeeType);
    }

    protected function payrollDeductionsBetween(Carbon $start, Carbon $end, string $employeeType): int
    {
        if (! in_array($employeeType, ['user', 'staff'], true)) {
            return 0;
        }

        $activeEmployeeIds = $employeeType === 'user'
            ? User::where('is_active', true)->pluck('id')
            : StaffMember::where('is_active', true)->pluck('id');

        if ($activeEmployeeIds->isEmpty()) {
            return 0;
        }

        return (int) DisciplinarySanction::where('employee_type', $employeeType)
            ->whereIn('employee_id', $activeEmployeeIds)
            ->where('status', 'validated')
            ->whereBetween('fault_date', [$start->toDateString(), $end->toDateString()])
            ->sum('deduction_amount');
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

    protected function canceledSalesQuery(Carbon $start, Carbon $end)
    {
        return Sale::query()
            ->where('sale_status', SaleStatus::Canceled)
            ->whereBetween('canceled_at', [$start, $end]);
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
            $closedTotal = (int) $this->cashRegisterClosingsBetween($monthStart, $monthEnd)->sum('total_amount');
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

    protected function currentOperationCycleDailySeries(): array
    {
        [$start, $end] = $this->operationCycleRange(now());
        $days = $start->diffInDays($end) + 1;

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
