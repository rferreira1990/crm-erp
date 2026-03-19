<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');

    Route::get('/clientes', function () {
        return view('customers.index');
    })->name('customers.index');

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

require __DIR__.'/auth.php';
