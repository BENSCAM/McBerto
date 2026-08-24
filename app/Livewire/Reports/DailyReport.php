<?php

namespace App\Livewire\Reports;

use App\Enums\ServiceArea;
use App\Models\Expense;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialStockMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class DailyReport extends Component
{
    public string $date = '';

    public string $period = 'day';

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function updatedDate(): void
    {
        if (! Auth::user()->isAtLeastManager()) {
            $this->date = now()->format('Y-m-d');
        }
    }

    public function updatedPeriod(): void
    {
        if (! in_array($this->period, ['day', 'month', 'year'], true)) {
            $this->period = 'day';
        }
    }

    /**
     * The date actually used for queries. Cashiers are locked to today
     * server-side, regardless of what the (hidden) date field holds.
     */
    protected function effectiveDate(): string
    {
        if (! Auth::user()->isAtLeastManager()) {
            return now()->format('Y-m-d');
        }

        return $this->date;
    }

    protected function effectiveRange(): array
    {
        $base = Carbon::createFromFormat('Y-m-d', $this->effectiveDate());

        if (! Auth::user()->isAtLeastManager()) {
            return [$base->copy()->startOfDay(), $base->copy()->endOfDay()];
        }

        return match ($this->period) {
            'month' => [$base->copy()->startOfMonth(), $base->copy()->endOfMonth()],
            'year' => [$base->copy()->startOfYear(), $base->copy()->endOfYear()],
            default => [$base->copy()->startOfDay(), $base->copy()->endOfDay()],
        };
    }

    #[Computed]
    public function revenue(): int
    {
        [$start, $end] = $this->effectiveRange();

        return (int) Sale::completed()->whereBetween('created_at', [$start, $end])->sum('total_amount');
    }

    #[Computed]
    public function expensesTotal(): int
    {
        return $this->operatingExpensesTotal();
    }

    #[Computed]
    public function generalExpensesTotal(): int
    {
        [$start, $end] = $this->effectiveRange();

        return (int) Expense::whereBetween('expense_date', [$start, $end])
            ->whereIn('category', array_keys(Expense::GENERAL_CATEGORIES))
            ->sum('amount');
    }

    #[Computed]
    public function materialCost(): int
    {
        [$start, $end] = $this->effectiveRange();

        return (int) RawMaterialStockMovement::whereBetween('occurred_at', [$start, $end])
            ->where('type', 'sale_consumption')
            ->sum('total_cost');
    }

    #[Computed]
    public function rawMaterialPurchasesTotal(): int
    {
        [$start, $end] = $this->effectiveRange();

        return (int) RawMaterialPurchase::whereBetween('purchase_date', [$start, $end])
            ->sum('total_price');
    }

    #[Computed]
    public function grossMargin(): int
    {
        return $this->revenue - $this->materialCost;
    }

    #[Computed]
    public function payrollTotal(): int
    {
        if (! Auth::user()->isAtLeastManager()) {
            return 0;
        }

        $monthlyPayroll = (int) User::where('is_active', true)->sum('monthly_salary')
            + (int) StaffMember::where('is_active', true)->sum('monthly_salary');

        return match ($this->period) {
            'month' => $monthlyPayroll,
            'year' => $monthlyPayroll * 12,
            default => 0,
        };
    }

    #[Computed]
    public function operatingExpensesTotal(): int
    {
        return $this->generalExpensesTotal + $this->payrollTotal;
    }

    #[Computed]
    public function netProfit(): int
    {
        return $this->grossMargin - $this->operatingExpensesTotal;
    }

    #[Computed]
    public function salesCount(): int
    {
        [$start, $end] = $this->effectiveRange();

        return Sale::completed()->whereBetween('created_at', [$start, $end])->count();
    }

    public function serviceAreaRevenue(ServiceArea $serviceArea): int
    {
        return (int) Sale::completed()
            ->whereBetween('created_at', $this->effectiveRange())
            ->where('service_area', $serviceArea)
            ->sum('total_amount');
    }

    public function serviceAreaSalesCount(ServiceArea $serviceArea): int
    {
        return Sale::completed()
            ->whereBetween('created_at', $this->effectiveRange())
            ->where('service_area', $serviceArea)
            ->count();
    }

    public function serviceAreaOptions(): array
    {
        return ServiceArea::cases();
    }

    public function managementReportPdfUrl(): string
    {
        return route('reports.management.pdf', [
            'period' => $this->period,
            'date' => $this->effectiveDate(),
        ]);
    }

    #[Computed]
    public function expensesForDay()
    {
        [$start, $end] = $this->effectiveRange();

        return Expense::whereBetween('expense_date', [$start, $end])
            ->whereIn('category', array_keys(Expense::GENERAL_CATEGORIES))
            ->with('user')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function lowStockMaterials()
    {
        return RawMaterial::whereColumn('current_quantity', '<=', 'low_stock_threshold')
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function consumedMaterials()
    {
        [$start, $end] = $this->effectiveRange();

        return RawMaterialStockMovement::query()
            ->join('raw_materials', 'raw_materials.id', '=', 'raw_material_stock_movements.raw_material_id')
            ->whereBetween('occurred_at', [$start, $end])
            ->where('type', 'sale_consumption')
            ->selectRaw('raw_materials.name, raw_materials.unit, SUM(quantity_out) as quantity, SUM(total_cost) as total_cost')
            ->groupBy('raw_materials.id', 'raw_materials.name', 'raw_materials.unit')
            ->orderByDesc('total_cost')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function productMargins()
    {
        [$start, $end] = $this->effectiveRange();

        $movementCosts = RawMaterialStockMovement::query()
            ->where('type', 'sale_consumption')
            ->selectRaw('sale_item_id, SUM(total_cost) as material_cost')
            ->groupBy('sale_item_id');

        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoinSub($movementCosts, 'movement_costs', fn ($join) => $join->on('movement_costs.sale_item_id', '=', 'sale_items.id'))
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.sale_status', 'completed')
            ->selectRaw('
                sale_items.product_name,
                SUM(sale_items.quantity) as quantity,
                SUM(sale_items.subtotal) as revenue,
                COALESCE(SUM(movement_costs.material_cost), 0) as material_cost
            ')
            ->groupBy('sale_items.product_name')
            ->orderByRaw('(SUM(sale_items.subtotal) - COALESCE(SUM(movement_costs.material_cost), 0)) desc')
            ->limit(8)
            ->get();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.reports.daily-report');
    }
}
