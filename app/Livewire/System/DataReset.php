<?php

namespace App\Livewire\System;

use App\Models\ActivityLog;
use App\Models\BugLog;
use App\Models\CashRegisterClosing;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\OperationalDataResetter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class DataReset extends Component
{
    #[Validate('required|in:REINITIALISER')]
    public string $confirmation = '';

    public ?array $lastResetCounts = null;

    public bool $resetDone = false;

    public function resetData(OperationalDataResetter $resetter): void
    {
        $this->validate();

        $this->lastResetCounts = $resetter->reset(Auth::user());
        $this->resetDone = true;
        $this->confirmation = '';
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.system.data-reset', [
            'counts' => [
                'Lignes de vente' => SaleItem::count(),
                'Ventes' => Sale::count(),
                'Clôtures de caisse' => CashRegisterClosing::count(),
                'Dépenses' => Expense::count(),
                'Historique système' => ActivityLog::count(),
                'Historique des bugs' => BugLog::count(),
            ],
        ]);
    }
}
