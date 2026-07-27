<?php

use App\Livewire\Admin\UserManagement;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Pos\CashRegisterClosing;
use App\Livewire\Pos\ClosingHistory;
use App\Livewire\Pos\Terminal;
use App\Livewire\Reports\DailyReport;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// TEMP DEBUG ROUTE — remove after visual verification.
Route::get('/_debug-login/{role}', function (string $role) {
    \Illuminate\Support\Facades\Auth::login(\App\Models\User::where('role', $role)->first());

    return redirect('/_debug-confirm');
});
Route::view('/_debug-confirm', 'debug-confirm')->middleware('auth');

Route::middleware(['auth'])->group(function () {

    Route::middleware('role:cashier,manager,owner')->group(function () {
        Route::get('/pos', Terminal::class)->name('pos.terminal');
        Route::get('/pos/cloture', CashRegisterClosing::class)->name('pos.closing');
        Route::get('/reports/daily', DailyReport::class)->name('reports.daily');
    });

    Route::middleware('role:manager,owner')->group(function () {
        Route::get('/dashboard', Overview::class)->name('dashboard');
        Volt::route('/categories', 'categories.index')->name('categories.index');
        Volt::route('/products', 'products.index')->name('products.index');
        Volt::route('/expenses', 'expenses.index')->name('expenses.index');
        Route::get('/pos/cloture/historique', ClosingHistory::class)->name('pos.closing.history');
    });

    Route::middleware('role:owner')->group(function () {
        Route::get('/users', UserManagement::class)->name('users.index');
    });
});

require __DIR__.'/auth.php';
