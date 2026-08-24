<?php

use App\Http\Controllers\Pos\OfflineSaleSyncController;
use App\Http\Controllers\Reports\ManagementReportPdfController;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Hr\Attendance;
use App\Livewire\Hr\DisciplineHistory;
use App\Livewire\Hr\MonthlyReport;
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
        Volt::route('/raw-materials', 'raw-materials.index')->name('raw-materials.index');
        Volt::route('/raw-material-purchases', 'raw-material-purchases.index')->name('raw-material-purchases.index');
        Volt::route('/product-recipes', 'product-recipes.index')->name('product-recipes.index');
        Volt::route('/stock-movements', 'stock-movements.index')->name('stock-movements.index');
        Volt::route('/expenses', 'expenses.index')->name('expenses.index');
        Route::get('/pos/cloture/historique', ClosingHistory::class)->name('pos.closing.history');
        Route::get('/system/history', ActivityHistory::class)->name('system.history');
        Route::get('/system/bugs', BugHistory::class)->name('system.bugs');
        Route::get('/users', UserManagement::class)->name('users.index');
        Route::get('/hr/presence', Attendance::class)->name('hr.attendance');
        Route::get('/hr/discipline', DisciplineHistory::class)->name('hr.discipline');
        Route::get('/hr/rapport', MonthlyReport::class)->name('hr.report');
        Route::get('/reports/management/pdf', ManagementReportPdfController::class)->name('reports.management.pdf');
    });

    Route::middleware('role:owner')->group(function () {
        Route::get('/system/reset', DataReset::class)->name('system.reset');
    });
});

require __DIR__.'/auth.php';
