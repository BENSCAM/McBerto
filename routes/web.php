<?php

use App\Http\Controllers\Pos\OfflineSaleSyncController;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Pos\CashRegisterClosing;
use App\Livewire\Pos\ClosingHistory;
use App\Livewire\Pos\Terminal;
use App\Livewire\Reports\DailyReport;
use App\Livewire\System\ActivityHistory;
use App\Livewire\System\BugHistory;
use App\Livewire\System\DataReset;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {

    Route::middleware('role:cashier,manager,owner')->group(function () {
        Route::get('/pos', Terminal::class)->name('pos.terminal');
        Route::post('/pos/offline-sales/sync', OfflineSaleSyncController::class)->name('pos.offline-sales.sync');
        Route::get('/pos/cloture', CashRegisterClosing::class)->name('pos.closing');
        Route::get('/reports/daily', DailyReport::class)->name('reports.daily');
    });

    Route::middleware('role:manager,owner')->group(function () {
        Route::get('/dashboard', Overview::class)->name('dashboard');
        Volt::route('/categories', 'categories.index')->name('categories.index');
        Volt::route('/products', 'products.index')->name('products.index');
        Volt::route('/expenses', 'expenses.index')->name('expenses.index');
        Route::get('/pos/cloture/historique', ClosingHistory::class)->name('pos.closing.history');
        Route::get('/system/history', ActivityHistory::class)->name('system.history');
        Route::get('/system/bugs', BugHistory::class)->name('system.bugs');
        Route::get('/users', UserManagement::class)->name('users.index');
    });

    Route::middleware('role:owner')->group(function () {
        Route::get('/system/reset', DataReset::class)->name('system.reset');
    });
});

require __DIR__.'/auth.php';
