<?php

namespace App\Livewire\Reports;

use App\Models\Expense;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class DailyReport extends Component
{
    public string $date = '';

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

    #[Computed]
    public function revenue(): int
    {
        return (int) Sale::whereDate('created_at', $this->effectiveDate())->sum('total_amount');
    }

    #[Computed]
    public function expensesTotal(): int
    {
        return (int) Expense::whereDate('expense_date', $this->effectiveDate())->sum('amount');
    }

    #[Computed]
    public function netProfit(): int
    {
        return $this->revenue - $this->expensesTotal;
    }

    #[Computed]
    public function salesCount(): int
    {
        return Sale::whereDate('created_at', $this->effectiveDate())->count();
    }

    #[Computed]
    public function expensesForDay()
    {
        return Expense::whereDate('expense_date', $this->effectiveDate())
            ->with('user')
            ->orderByDesc('id')
            ->get();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.reports.daily-report');
    }
}
