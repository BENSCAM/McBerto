<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BugLog;
use App\Models\CashRegisterClosing;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OperationalDataResetter
{
    public function reset(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $counts = [
                'sale_items' => SaleItem::count(),
                'sales' => Sale::count(),
                'cash_register_closings' => CashRegisterClosing::count(),
                'expenses' => Expense::count(),
                'activity_logs' => ActivityLog::count(),
                'bug_logs' => BugLog::count(),
            ];

            SaleItem::withoutEvents(fn () => SaleItem::query()->delete());
            Sale::withoutEvents(fn () => Sale::query()->delete());
            CashRegisterClosing::withoutEvents(fn () => CashRegisterClosing::query()->delete());
            Expense::withoutEvents(fn () => Expense::query()->delete());
            ActivityLog::query()->delete();
            BugLog::query()->delete();

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'reset',
                'description' => 'Données du dashboard réinitialisées pour le lancement',
                'new_values' => $counts,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $counts;
        });
    }
}
