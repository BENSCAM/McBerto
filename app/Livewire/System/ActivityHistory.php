<?php

namespace App\Livewire\System;

use App\Enums\PaymentMethod;
use App\Models\ActivityLog;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\CashRegisterClosing;
use App\Models\RawMaterialStockMovement;
use App\Services\RawMaterialStockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityHistory extends Component
{
    use WithPagination;

    public string $search = '';

    public string $action = '';

    public string $userId = '';

    public string $orderDate = '';

    public string $exportStartDate = '';

    public string $exportEndDate = '';

    public string $deleteStartDate = '';

    public string $deleteEndDate = '';

    public string $deleteConfirmation = '';

    public ?int $selectedOrderId = null;

    public ?string $orderNotice = null;

    public ?array $lastDeletedOrders = null;

    public function mount(): void
    {
        $this->exportStartDate = now()->toDateString();
        $this->exportEndDate = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function updatedUserId(): void
    {
        $this->resetPage();
    }

    public function updatedOrderDate(): void
    {
        if ($this->orderDate !== '') {
            $this->exportStartDate = $this->orderDate;
            $this->exportEndDate = $this->orderDate;
        }

        unset($this->orderHistory);
        $this->selectedOrderId = null;
    }

    public function updatedExportStartDate(): void
    {
        $this->refreshOrderHistorySelection();
    }

    public function updatedExportEndDate(): void
    {
        $this->refreshOrderHistorySelection();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'action', 'userId']);
        $this->resetPage();
    }

    public function clearOrderDate(): void
    {
        $this->orderDate = '';
        $this->exportStartDate = '';
        $this->exportEndDate = '';
        $this->refreshOrderHistorySelection();
    }

    public function showTodayOrders(): void
    {
        $this->orderDate = now()->toDateString();
        $this->exportStartDate = $this->orderDate;
        $this->exportEndDate = $this->orderDate;
        $this->refreshOrderHistorySelection();
    }

    public function orderHistoryHasDateFilter(): bool
    {
        return $this->exportStartDate !== '' || $this->exportEndDate !== '';
    }

    private function refreshOrderHistorySelection(): void
    {
        $this->selectedOrderId = null;
        unset($this->orderHistory);
    }

    public function orderHistoryPdfUrl(): string
    {
        $startDate = $this->exportStartDate !== '' ? $this->exportStartDate : now()->toDateString();
        $endDate = $this->exportEndDate !== '' ? $this->exportEndDate : $startDate;

        return route('system.history.orders.pdf', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    #[Computed]
    public function orderHistory()
    {
        $query = Sale::completed()
            ->with(['items' => fn ($query) => $query->orderBy('id'), 'user']);

        if ($this->exportStartDate !== '') {
            try {
                $start = Carbon::createFromFormat('Y-m-d', $this->exportStartDate)->startOfDay();

                if ($start->format('Y-m-d') === $this->exportStartDate) {
                    $query->where('created_at', '>=', $start);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } catch (\Throwable) {
                $query->whereRaw('1 = 0');
            }
        }

        if ($this->exportEndDate !== '') {
            try {
                $end = Carbon::createFromFormat('Y-m-d', $this->exportEndDate)->endOfDay();

                if ($end->format('Y-m-d') === $this->exportEndDate) {
                    $query->where('created_at', '<=', $end);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } catch (\Throwable) {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->orderBy('created_at')
            ->orderBy('id')
            ->limit($this->orderHistoryHasDateFilter() ? 200 : 20)
            ->get();
    }

    #[Computed]
    public function selectedOrderTicket(): ?Sale
    {
        if (! $this->selectedOrderId) {
            return null;
        }

        return Sale::completed()
            ->with(['items' => fn ($query) => $query->orderBy('id'), 'user'])
            ->find($this->selectedOrderId);
    }

    public function viewOrderTicket(int $saleId): void
    {
        $this->selectedOrderId = $saleId;
    }

    public function deleteOrder(int $saleId): void
    {
        $sale = Sale::completed()
            ->with(['closing', 'items'])
            ->findOrFail($saleId);

        DB::transaction(function () use ($sale) {
            app(RawMaterialStockService::class)->restoreForCanceledSale($sale, Auth::user());
            $this->adjustClosingAfterDeletedSale($sale);
            $sale->delete();
        });

        if ($this->selectedOrderId === $saleId) {
            $this->selectedOrderId = null;
        }

        $this->orderNotice = 'Commande supprimée.';
        unset($this->orderHistory, $this->selectedOrderTicket);
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
            $sales = Sale::completed()
                ->with(['rawMaterialStockMovements', 'items'])
                ->whereBetween('created_at', [$start, $end])
                ->get();

            $saleIds = $sales->pluck('id');
            $closingCount = $this->cashRegisterClosingsBetween($start, $end)->count();
            $totalAmount = (int) $sales->sum('total_amount');
            $ordersCount = $sales->count();
            $itemsCount = SaleItem::whereIn('sale_id', $saleIds)->count();

            foreach ($sales as $sale) {
                app(RawMaterialStockService::class)->restoreForCanceledSale($sale, Auth::user());
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
        $this->selectedOrderId = null;
        $this->orderNotice = 'Commandes de la période supprimées.';
        unset($this->orderHistory, $this->selectedOrderTicket);
    }

    public function closeOrderTicket(): void
    {
        $this->selectedOrderId = null;
    }

    private function adjustClosingAfterDeletedSale(Sale $sale): void
    {
        $closing = $sale->closing;

        if (! $closing) {
            return;
        }

        $paymentColumn = match ($sale->payment_method) {
            PaymentMethod::Cash => 'total_cash',
            PaymentMethod::OrangeMoney => 'total_orange_money',
            PaymentMethod::MtnMomo => 'total_mtn_momo',
            PaymentMethod::Other => 'total_other',
        };

        $nextPaymentTotal = max(0, (int) $closing->{$paymentColumn} - (int) $sale->total_amount);
        $nextTotal = max(0, (int) $closing->total_amount - (int) $sale->total_amount);
        $nextOrdersCount = max(0, (int) $closing->total_orders_count - 1);

        $data = [
            $paymentColumn => $nextPaymentTotal,
            'total_amount' => $nextTotal,
            'total_orders_count' => $nextOrdersCount,
        ];

        if ($closing->counted_cash !== null) {
            $nextCashTotal = $paymentColumn === 'total_cash'
                ? $nextPaymentTotal
                : (int) $closing->total_cash;

            $data['variance'] = (int) $closing->counted_cash - $nextCashTotal;
        }

        $closing->update($data);
    }

    private function cashRegisterClosingsBetween(Carbon $start, Carbon $end)
    {
        return CashRegisterClosing::query()
            ->whereDate('closing_date', '>=', $start->toDateString())
            ->whereDate('closing_date', '<=', $end->toDateString());
    }

    public function formatActivityValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        if (is_array($value)) {
            if ($value === []) {
                return 'Aucun';
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Donnée non lisible';
        }

        return (string) $value;
    }

    public function formatActivityAction(mixed $action): string
    {
        $action = $this->formatActivityValue($action);

        return ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression'][$action] ?? ucfirst($action);
    }

    public function formatActivityField(mixed $field): string
    {
        return str_replace('_', ' ', $this->formatActivityValue($field));
    }

    public function activityDisplayDate(ActivityLog $log): Carbon
    {
        if ($log->subject instanceof Sale) {
            return $log->subject->created_at;
        }

        if ($log->subject instanceof SaleItem && $log->subject->sale) {
            return $log->subject->sale->created_at;
        }

        return $log->created_at;
    }

    public function activityDateIsSaleDate(ActivityLog $log): bool
    {
        return $this->activityDisplayDate($log)->ne($log->created_at);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $logs = ActivityLog::query()
            ->with(['user', 'subject'])
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('description', 'like', '%'.$this->search.'%')
                    ->orWhere('subject_type', 'like', '%'.$this->search.'%')
                    ->orWhere('ip_address', 'like', '%'.$this->search.'%');
            }))
            ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
            ->when($this->userId !== '', fn ($query) => $query->where('user_id', $this->userId))
            ->latest()
            ->paginate(20);

        $logs->getCollection()->loadMorph('subject', [
            SaleItem::class => ['sale'],
        ]);

        return view('livewire.system.activity-history', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'actions' => ActivityLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
        ]);
    }
}
