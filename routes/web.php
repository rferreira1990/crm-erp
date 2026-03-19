<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BudgetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');

   Route::get('/customers', [CustomerController::class, 'index'])
        ->middleware('permission:customers.view')
        ->name('customers.index');

    Route::get('/customers/create', [CustomerController::class, 'create'])
        ->middleware('permission:customers.create')
        ->name('customers.create');

    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware('permission:customers.create')
        ->name('customers.store');

    Route::get('/customers/{customer}', [CustomerController::class, 'show'])
        ->middleware('permission:customers.view')
        ->name('customers.show');

    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])
        ->middleware('permission:customers.update')
        ->name('customers.edit');

    Route::put('/customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:customers.update')
        ->name('customers.update');

    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('permission:customers.delete')
        ->name('customers.destroy');

    Route::resource('budgets', BudgetController::class)
    ->middleware('auth');
     Route::get('/budgets', [BudgetController::class, 'index'])
        ->middleware('permission:budgets.view')
        ->name('budgets.index');

    Route::get('/budgets/create', [BudgetController::class, 'create'])
        ->middleware('permission:budgets.create')
        ->name('budgets.create');

    Route::post('/budgets', [BudgetController::class, 'store'])
        ->middleware('permission:budgets.create')
        ->name('budgets.store');

    Route::get('/obras', function () {
        return view('jobs.index');
    })->name('jobs.index');

    Route::get('/orcamentos', function () {
        return view('budgets.index');
    })->name('budgets.index');

    Route::get('/stock', function () {
        return view('stock.index');
    })->name('stock.index');

    Route::get('/utilizadores', function () {
        return view('users.index');
    })->name('users.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
