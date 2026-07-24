<?php

namespace App\Livewire\Pos;

use App\Models\CashRegisterClosing;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ClosingHistory extends Component
{
    use WithPagination;

    public function closings()
    {
        return CashRegisterClosing::with('closedBy')
            ->orderByDesc('closing_date')
            ->paginate(15);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pos.closing-history', [
            'closings' => $this->closings(),
        ]);
    }
}
