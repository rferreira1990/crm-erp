<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');

    Route::get('/clientes', [CustomerController::class, 'index'])
        ->name('customers.index');

    Route::get('/clientes/criar', [CustomerController::class, 'create'])
        ->name('customers.create');

    Route::post('/clientes', [CustomerController::class, 'store'])
        ->name('customers.store');

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
