<?php

namespace App\Livewire\System;

use App\Models\ActivityLog;
use App\Models\Sale;
use App\Models\User;
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

    public ?int $selectedOrderId = null;

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

    public function clearFilters(): void
    {
        $this->reset(['search', 'action', 'userId']);
        $this->resetPage();
    }

    #[Computed]
    public function orderHistory()
    {
        return Sale::completed()
            ->with(['items' => fn ($query) => $query->orderBy('id'), 'user'])
            ->latest()
            ->limit(20)
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

    public function closeOrderTicket(): void
    {
        $this->selectedOrderId = null;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('description', 'like', '%'.$this->search.'%')
                    ->orWhere('subject_type', 'like', '%'.$this->search.'%')
                    ->orWhere('ip_address', 'like', '%'.$this->search.'%');
            }))
            ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
            ->when($this->userId !== '', fn ($query) => $query->where('user_id', $this->userId))
            ->latest()
            ->paginate(20);

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
