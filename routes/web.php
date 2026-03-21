<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ItemFamilyController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\TaxExemptionReasonController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemFileController;
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

    Route::get('/items', [ItemController::class, 'index'])
        ->middleware('permission:items.view')
        ->name('items.index');

    Route::get('/items/create', [ItemController::class, 'create'])
        ->middleware('permission:items.create')
        ->name('items.create');

    Route::post('/items', [ItemController::class, 'store'])
        ->middleware('permission:items.create')
        ->name('items.store');

    Route::get('/items/{item}/edit', [ItemController::class, 'edit'])
        ->middleware('permission:items.edit')
        ->name('items.edit');

    Route::put('/items/{item}', [ItemController::class, 'update'])
        ->middleware('permission:items.edit')
        ->name('items.update');

    Route::post('/items/{item}/files', [ItemFileController::class, 'store'])
        ->middleware('permission:items.edit')
        ->name('items.files.store');

    Route::delete('/items/{item}/files/{file}', [ItemFileController::class, 'destroy'])
        ->middleware('permission:items.edit')
        ->name('items.files.destroy');

    Route::patch('/items/{item}/files/{file}/primary', [ItemFileController::class, 'setPrimary'])
        ->middleware('permission:items.edit')
        ->name('items.files.primary');

    Route::get('/items/{item}/files/{file}', [ItemFileController::class, 'show'])
    ->middleware('permission:items.view')
    ->name('items.files.show');

    Route::get('/items/{item}', [ItemController::class, 'show'])
        ->middleware('permission:items.view')
        ->name('items.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['auth', 'permission:settings.manage'])->group(function () {
    Route::get('/item-families', [ItemFamilyController::class, 'index'])->name('item-families.index');
    Route::get('/item-families/create', [ItemFamilyController::class, 'create'])->name('item-families.create');
    Route::post('/item-families', [ItemFamilyController::class, 'store'])->name('item-families.store');
    Route::get('/item-families/{item_family}/edit', [ItemFamilyController::class, 'edit'])->name('item-families.edit');
    Route::put('/item-families/{item_family}', [ItemFamilyController::class, 'update'])->name('item-families.update');
    Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
    Route::get('/brands/create', [BrandController::class, 'create'])->name('brands.create');
    Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
    Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');
    Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::get('/units', [UnitController::class, 'index'])->name('units.index');
    Route::get('/units/create', [UnitController::class, 'create'])->name('units.create');
    Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
    Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::get('/tax-rates', [TaxRateController::class, 'index'])->name('tax-rates.index');
    Route::get('/tax-rates/create', [TaxRateController::class, 'create'])->name('tax-rates.create');
    Route::post('/tax-rates', [TaxRateController::class, 'store'])->name('tax-rates.store');
    Route::get('/tax-rates/{tax_rate}/edit', [TaxRateController::class, 'edit'])->name('tax-rates.edit');
    Route::put('/tax-rates/{tax_rate}', [TaxRateController::class, 'update'])->name('tax-rates.update');
    Route::get('/tax-exemption-reasons', [TaxExemptionReasonController::class, 'index'])->name('tax-exemption-reasons.index');
    Route::get('/tax-exemption-reasons/create', [TaxExemptionReasonController::class, 'create'])->name('tax-exemption-reasons.create');
    Route::post('/tax-exemption-reasons', [TaxExemptionReasonController::class, 'store'])->name('tax-exemption-reasons.store');
    Route::get('/tax-exemption-reasons/{tax_exemption_reason}/edit', [TaxExemptionReasonController::class, 'edit'])->name('tax-exemption-reasons.edit');
    Route::put('/tax-exemption-reasons/{tax_exemption_reason}', [TaxExemptionReasonController::class, 'update'])->name('tax-exemption-reasons.update');
});
});

require __DIR__ . '/auth.php';
