<?php

namespace App\Services\Reports;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ServiceArea;
use App\Models\CashRegisterClosing;
use App\Models\Expense;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialStockMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Carbon;

class ManagementReportService
{
    public function build(string $period, ?string $date = null): array
    {
        [$start, $end] = $this->range($period, $date);

        $sales = Sale::query()
            ->whereBetween('created_at', [$start, $end])
            ->with(['user', 'items', 'canceledBy'])
            ->orderByDesc('created_at')
            ->get();

        $completedSales = $sales->where('sale_status', SaleStatus::Completed);
        $canceledSales = $sales->where('sale_status', SaleStatus::Canceled);
        $expenses = Expense::query()
            ->whereBetween('expense_date', [$start, $end])
            ->whereIn('category', array_keys(Expense::GENERAL_CATEGORIES))
            ->with('user')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        $closings = CashRegisterClosing::query()
            ->whereBetween('closing_date', [$start->toDateString(), $end->toDateString()])
            ->with('closedBy')
            ->orderByDesc('closing_date')
            ->get();

        $revenue = (int) $completedSales->sum('total_amount');
        $expensesTotal = (int) $expenses->sum('amount');
        $materialCost = (int) RawMaterialStockMovement::whereBetween('occurred_at', [$start, $end])
            ->where('type', 'sale_consumption')
            ->sum('total_cost');
        $grossMargin = $revenue - $materialCost;
        $payrollTotal = $this->payrollTotal($period);
        $operatingExpensesTotal = $expensesTotal + $payrollTotal;
        $ordersCount = $completedSales->count();

        return [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'label' => $this->label($period, $start, $end),
            'summary' => [
                'revenue' => $revenue,
                'material_cost' => $materialCost,
                'gross_margin' => $grossMargin,
                'expenses' => $expensesTotal,
                'payroll_total' => $payrollTotal,
                'operating_expenses_total' => $operatingExpensesTotal,
                'net_profit' => $grossMargin - $operatingExpensesTotal,
                'raw_material_purchases_total' => (int) RawMaterialPurchase::whereBetween('purchase_date', [$start, $end])->sum('total_price'),
                'orders_count' => $ordersCount,
                'average_ticket' => $ordersCount > 0 ? (int) round($revenue / $ordersCount) : 0,
                'canceled_orders_count' => $canceledSales->count(),
                'canceled_orders_total' => (int) $canceledSales->sum('total_amount'),
            ],
            'service_areas' => collect(ServiceArea::cases())
                ->map(fn (ServiceArea $area) => [
                    'label' => $area->label(),
                    'orders_count' => $completedSales->where('service_area', $area)->count(),
                    'revenue' => (int) $completedSales->where('service_area', $area)->sum('total_amount'),
                ])
                ->all(),
            'payment_methods' => collect(PaymentMethod::cases())
                ->map(fn (PaymentMethod $method) => [
                    'label' => $method->label(),
                    'orders_count' => $completedSales->where('payment_method', $method)->count(),
                    'amount' => (int) $completedSales->where('payment_method', $method)->sum('total_amount'),
                    'percent' => $revenue > 0 ? round($completedSales->where('payment_method', $method)->sum('total_amount') / $revenue * 100, 1) : 0,
                ])
                ->all(),
            'top_products' => $this->topProducts($start, $end),
            'most_profitable_products' => $this->mostProfitableProducts($start, $end),
            'consumed_materials' => $this->consumedMaterials($start, $end),
            'low_stock_materials' => RawMaterial::whereColumn('current_quantity', '<=', 'low_stock_threshold')->orderBy('name')->limit(10)->get(),
            'sales' => $sales->take(120)->values(),
            'sales_total_count' => $sales->count(),
            'canceled_sales' => $canceledSales->values(),
            'expenses' => $expenses,
            'closings' => $closings,
        ];
    }

    public function range(string $period, ?string $date = null): array
    {
        $period = in_array($period, ['day', 'month', 'year'], true) ? $period : 'day';
        $base = $this->parseDate($date);

        return match ($period) {
            'month' => [$base->copy()->startOfMonth(), $base->copy()->endOfMonth()],
            'year' => [$base->copy()->startOfYear(), $base->copy()->endOfYear()],
            default => [$base->copy()->startOfDay(), $base->copy()->endOfDay()],
        };
    }

    protected function parseDate(?string $date): Carbon
    {
        if (! $date) {
            return now();
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date);

            return $parsed->format('Y-m-d') === $date ? $parsed : now();
        } catch (\Throwable) {
            return now();
        }
    }

    protected function label(string $period, Carbon $start, Carbon $end): string
    {
        return match ($period) {
            'month' => $start->translatedFormat('F Y'),
            'year' => $start->format('Y'),
            default => $start->format('d/m/Y'),
        };
    }

    protected function topProducts(Carbon $start, Carbon $end)
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.sale_status', SaleStatus::Completed)
            ->selectRaw("
                sale_items.product_name,
                COALESCE(categories.name, 'Sans catégorie') as category_name,
                sales.service_area,
                SUM(sale_items.quantity) as total_quantity,
                SUM(sale_items.subtotal) as total_revenue
            ")
            ->groupBy('sale_items.product_name', 'categories.name', 'sales.service_area')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
    }

    protected function payrollTotal(string $period): int
    {
        $monthlyPayroll = (int) User::where('is_active', true)->sum('monthly_salary');

        return match ($period) {
            'month' => $monthlyPayroll,
            'year' => $monthlyPayroll * 12,
            default => 0,
        };
    }

    protected function mostProfitableProducts(Carbon $start, Carbon $end)
    {
        $movementCosts = RawMaterialStockMovement::query()
            ->where('type', 'sale_consumption')
            ->selectRaw('sale_item_id, SUM(total_cost) as material_cost')
            ->groupBy('sale_item_id');

        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoinSub($movementCosts, 'movement_costs', fn ($join) => $join->on('movement_costs.sale_item_id', '=', 'sale_items.id'))
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.sale_status', SaleStatus::Completed)
            ->selectRaw('
                sale_items.product_name,
                SUM(sale_items.quantity) as total_quantity,
                SUM(sale_items.subtotal) as total_revenue,
                COALESCE(SUM(movement_costs.material_cost), 0) as material_cost
            ')
            ->groupBy('sale_items.product_name')
            ->orderByRaw('(SUM(sale_items.subtotal) - COALESCE(SUM(movement_costs.material_cost), 0)) desc')
            ->limit(10)
            ->get();
    }

    protected function consumedMaterials(Carbon $start, Carbon $end)
    {
        return RawMaterialStockMovement::query()
            ->join('raw_materials', 'raw_materials.id', '=', 'raw_material_stock_movements.raw_material_id')
            ->whereBetween('occurred_at', [$start, $end])
            ->where('type', 'sale_consumption')
            ->selectRaw('raw_materials.name, raw_materials.unit, SUM(quantity_out) as quantity, SUM(total_cost) as total_cost')
            ->groupBy('raw_materials.id', 'raw_materials.name', 'raw_materials.unit')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->get();
    }
}
