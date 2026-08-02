<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ServiceArea;
use App\Models\CashRegisterClosing;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Generates ~3 weeks of fictitious past sales/expenses so the Dashboard
 * chart and Daily Report have realistic data to display right after
 * `migrate:fresh --seed`. Demo data only — never run this against production.
 */
class DemoSalesSeeder
{
    protected const DAYS_OF_HISTORY = 21;

    public function run(Collection $cashiers, Collection $products, User $manager): void
    {
        for ($daysAgo = self::DAYS_OF_HISTORY; $daysAgo >= 1; $daysAgo--) {
            $day = Carbon::today()->subDays($daysAgo);

            if (CashRegisterClosing::whereDate('closing_date', $day)->exists()) {
                continue;
            }

            $sales = $this->seedSalesForDay($day, $cashiers, $products);
            $this->closeRegisterForDay($day, $sales, $cashiers->first());
            $this->maybeSeedExpenseForDay($day, $manager);
        }

        // Today stays open (no closing) so the closing flow can be exercised live.
        if (! Sale::whereDate('created_at', Carbon::today())->exists()) {
            $this->seedSalesForDay(Carbon::today(), $cashiers, $products);
            $this->maybeSeedExpenseForDay(Carbon::today(), $manager);
        }
    }

    protected function seedSalesForDay(Carbon $day, Collection $cashiers, Collection $products): Collection
    {
        $salesCount = random_int(8, 25);
        $sales = collect();

        for ($i = 0; $i < $salesCount; $i++) {
            $time = $day->copy()->setTime(random_int(7, 21), random_int(0, 59));
            $cashier = $cashiers->random();
            $serviceArea = ServiceArea::cases()[array_rand(ServiceArea::cases())];
            $availableProducts = $products
                ->filter(fn ($product) => $product->service_area === $serviceArea)
                ->values();

            if ($availableProducts->isEmpty()) {
                $availableProducts = $products;
            }

            $itemCount = random_int(1, 4);
            $lines = $availableProducts->random(min($itemCount, $availableProducts->count()));

            $sale = new Sale([
                'receipt_number' => Sale::nextReceiptNumber($time),
                'user_id' => $cashier->id,
                'payment_method' => $this->randomPaymentMethod(),
                'service_area' => $serviceArea,
                'sale_status' => SaleStatus::Completed,
                'total_amount' => 0,
            ]);
            $sale->created_at = $time;
            $sale->updated_at = $time;
            $sale->save();

            $total = 0;

            foreach ($lines as $product) {
                $quantity = random_int(1, 3);
                $subtotal = $product->price * $quantity;
                $total += $subtotal;

                $item = new SaleItem([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ]);
                $item->created_at = $time;
                $item->updated_at = $time;
                $item->save();
            }

            $sale->update(['total_amount' => $total]);
            $sales->push($sale);
        }

        return $sales;
    }

    protected function closeRegisterForDay(Carbon $day, Collection $sales, User $closedBy): void
    {
        if ($sales->isEmpty()) {
            return;
        }

        $totals = collect(PaymentMethod::cases())->mapWithKeys(
            fn (PaymentMethod $method) => [$method->value => $sales->where('payment_method', $method)->sum('total_amount')]
        );

        // Most days the count matches exactly; occasionally a small demo variance for realism.
        $variance = random_int(1, 100) <= 20 ? random_int(-2000, 2000) : 0;
        $countedCash = max(0, $totals['cash'] + $variance);

        $closing = new CashRegisterClosing([
            'closing_date' => $day->format('Y-m-d'),
            'closed_by' => $closedBy->id,
            'total_cash' => $totals['cash'],
            'counted_cash' => $countedCash,
            'variance' => $countedCash - $totals['cash'],
            'total_orange_money' => $totals['orange_money'],
            'total_mtn_momo' => $totals['mtn_momo'],
            'total_other' => $totals['other'],
            'total_amount' => $sales->sum('total_amount'),
            'total_orders_count' => $sales->count(),
        ]);
        $closingTime = $day->copy()->setTime(22, 0);
        $closing->created_at = $closingTime;
        $closing->updated_at = $closingTime;
        $closing->save();

        Sale::whereIn('id', $sales->pluck('id'))->update(['cash_register_closing_id' => $closing->id]);
    }

    protected function maybeSeedExpenseForDay(Carbon $day, User $manager): void
    {
        if (Expense::whereDate('expense_date', $day)->exists()) {
            return;
        }

        // ~60% of days get a recorded expense, to keep the demo data realistic without being uniform.
        if (random_int(1, 100) > 60) {
            return;
        }

        $category = array_rand(Expense::CATEGORIES);

        Expense::create([
            'user_id' => $manager->id,
            'category' => $category,
            'description' => match ($category) {
                'matieres_premieres' => 'Achat de matières premières',
                'charges' => 'Facture eau/électricité',
                'salaires' => 'Paiement salaire',
                default => 'Dépense diverse',
            },
            'amount' => random_int(3000, 60000),
            'expense_date' => $day->format('Y-m-d'),
        ]);
    }

    protected function randomPaymentMethod(): PaymentMethod
    {
        return PaymentMethod::cases()[array_rand(PaymentMethod::cases())];
    }
}
