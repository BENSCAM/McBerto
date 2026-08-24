<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BugLog;
use App\Models\CashRegisterClosing;
use App\Models\Expense;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialStockMovement;
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
                'raw_material_purchases' => RawMaterialPurchase::count(),
                'raw_material_stock_movements' => RawMaterialStockMovement::count(),
                'activity_logs' => ActivityLog::count(),
                'bug_logs' => BugLog::count(),
            ];

            RawMaterialStockMovement::withoutEvents(fn () => RawMaterialStockMovement::query()->delete());
            RawMaterialPurchase::withoutEvents(fn () => RawMaterialPurchase::query()->delete());
            SaleItem::withoutEvents(fn () => SaleItem::query()->delete());
            Sale::withoutEvents(fn () => Sale::query()->delete());
            CashRegisterClosing::withoutEvents(fn () => CashRegisterClosing::query()->delete());
            Expense::withoutEvents(fn () => Expense::query()->delete());
            RawMaterial::withoutEvents(fn () => RawMaterial::query()->update(['current_quantity' => 0]));
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
