<?php

namespace App\Livewire\Pos;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ServiceArea;
use App\Models\CashRegisterClosing as CashRegisterClosingModel;
use App\Models\Sale;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CashRegisterClosing extends Component
{
    public ?CashRegisterClosingModel $existingClosing = null;

    public array $pendingTotals = [];

    public array $pendingServiceAreaTotals = [];

    public int $pendingCount = 0;

    public int $pendingTotal = 0;

    #[Validate('required|integer|min:0')]
    public string $countedCash = '';

    public function mount(): void
    {
        $this->refreshState();
    }

    protected function refreshState(): void
    {
        $today = Carbon::today();

        $this->existingClosing = CashRegisterClosingModel::whereDate('closing_date', $today)->first();

        if ($this->existingClosing) {
            $this->pendingCount = 0;
            $this->pendingTotal = 0;
            $this->pendingTotals = [];
            $this->pendingServiceAreaTotals = [];

            return;
        }

        $pendingSales = Sale::whereNull('cash_register_closing_id')
            ->completed()
            ->whereDate('created_at', $today)
            ->get();

        $this->pendingCount = $pendingSales->count();
        $this->pendingTotal = $pendingSales->sum('total_amount');

        $this->pendingTotals = collect(PaymentMethod::cases())
            ->mapWithKeys(fn (PaymentMethod $method) => [
                $method->value => $pendingSales->where('payment_method', $method)->sum('total_amount'),
            ])
            ->all();

        $this->pendingServiceAreaTotals = collect(ServiceArea::cases())
            ->mapWithKeys(fn (ServiceArea $serviceArea) => [
                $serviceArea->value => [
                    'label' => $serviceArea->label(),
                    'count' => $pendingSales->where('service_area', $serviceArea)->count(),
                    'total' => $pendingSales->where('service_area', $serviceArea)->sum('total_amount'),
                ],
            ])
            ->all();
    }

    #[Computed]
    public function existingServiceAreaTotals(): array
    {
        if (! $this->existingClosing) {
            return [];
        }

        $sales = $this->existingClosing->sales->where('sale_status', SaleStatus::Completed);

        return collect(ServiceArea::cases())
            ->mapWithKeys(fn (ServiceArea $serviceArea) => [
                $serviceArea->value => [
                    'label' => $serviceArea->label(),
                    'count' => $sales->where('service_area', $serviceArea)->count(),
                    'total' => $sales->where('service_area', $serviceArea)->sum('total_amount'),
                ],
            ])
            ->all();
    }

    #[Computed]
    public function projectedVariance(): ?int
    {
        if ($this->countedCash === '' || ! is_numeric($this->countedCash)) {
            return null;
        }

        return (int) $this->countedCash - ($this->pendingTotals['cash'] ?? 0);
    }

    public function close(): void
    {
        if ($this->existingClosing) {
            return;
        }

        $this->validate();

        $today = Carbon::today();
        $counted = (int) $this->countedCash;

        try {
            DB::transaction(function () use ($today, $counted) {
                $existingClosing = CashRegisterClosingModel::whereDate('closing_date', $today)
                    ->lockForUpdate()
                    ->first();

                if ($existingClosing) {
                    return;
                }

                $pendingSales = Sale::whereNull('cash_register_closing_id')
                    ->completed()
                    ->whereDate('created_at', $today)
                    ->get();

                $cashTotal = $pendingSales->where('payment_method', PaymentMethod::Cash)->sum('total_amount');

                $closing = CashRegisterClosingModel::create([
                    'closing_date' => $today,
                    'closed_by' => Auth::id(),
                    'total_cash' => $cashTotal,
                    'counted_cash' => $counted,
                    'variance' => $counted - $cashTotal,
                    'total_orange_money' => $pendingSales->where('payment_method', PaymentMethod::OrangeMoney)->sum('total_amount'),
                    'total_mtn_momo' => $pendingSales->where('payment_method', PaymentMethod::MtnMomo)->sum('total_amount'),
                    'total_other' => $pendingSales->where('payment_method', PaymentMethod::Other)->sum('total_amount'),
                    'total_amount' => $pendingSales->sum('total_amount'),
                    'total_orders_count' => $pendingSales->count(),
                ]);

                Sale::whereNull('cash_register_closing_id')
                    ->completed()
                    ->whereDate('created_at', $today)
                    ->update(['cash_register_closing_id' => $closing->id]);
            });
        } catch (UniqueConstraintViolationException) {
            // Another request already closed the register for this date.
        }

        $this->reset('countedCash');
        $this->refreshState();
    }

    public function reopen(): void
    {
        if (! Auth::user()->isAtLeastManager() || ! $this->existingClosing) {
            return;
        }

        CashRegisterClosingModel::reopenToday();
        $this->refreshState();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pos.cash-register-closing');
    }
}
