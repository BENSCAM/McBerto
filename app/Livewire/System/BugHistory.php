<?php

namespace App\Livewire\System;

use App\Models\BugLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class BugHistory extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'open';

    public ?int $selectedBugId = null;

    public string $resolutionNote = '';

    public ?string $notice = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function selectBug(int $bugId): void
    {
        $this->selectedBugId = $bugId;
        $this->resolutionNote = '';
    }

    public function closeDetails(): void
    {
        $this->selectedBugId = null;
        $this->resolutionNote = '';
    }

    public function resolveBug(int $bugId): void
    {
        BugLog::findOrFail($bugId)->markResolved(Auth::user(), $this->resolutionNote ?: null);
        $this->closeDetails();
    }

    public function resolveAllOpenBugs(): void
    {
        $count = BugLog::whereNull('resolved_at')->update([
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
            'resolution_note' => 'Marqué résolu en lot depuis l’historique des bugs.',
            'updated_at' => now(),
        ]);

        $this->notice = $count > 0
            ? "{$count} bug(s) marqué(s) comme résolu(s)."
            : 'Aucun bug ouvert à résoudre.';

        $this->closeDetails();
        $this->resetPage();
    }

    public function reopenBug(int $bugId): void
    {
        BugLog::findOrFail($bugId)->reopen();
        $this->closeDetails();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $logs = BugLog::query()
            ->with(['user', 'resolvedBy'])
            ->when($this->status === 'open', fn ($query) => $query->whereNull('resolved_at'))
            ->when($this->status === 'resolved', fn ($query) => $query->whereNotNull('resolved_at'))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('message', 'like', '%'.$this->search.'%')
                    ->orWhere('exception_class', 'like', '%'.$this->search.'%')
                    ->orWhere('url', 'like', '%'.$this->search.'%')
                    ->orWhere('file', 'like', '%'.$this->search.'%');
            }))
            ->latest()
            ->paginate(15);

        return view('livewire.system.bug-history', [
            'logs' => $logs,
            'selectedBug' => $this->selectedBugId
                ? BugLog::with(['user', 'resolvedBy'])->find($this->selectedBugId)
                : null,
            'openCount' => BugLog::whereNull('resolved_at')->count(),
            'resolvedCount' => BugLog::whereNotNull('resolved_at')->count(),
        ]);
    }
}
