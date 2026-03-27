<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetItemController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DocumentSeriesController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemFamilyController;
use App\Http\Controllers\ItemFileController;
use App\Http\Controllers\PaymentTermController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaxExemptionReasonController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

/*
+------------------------------------------------------------------+
| ENTRADA DA APLICACAO                                             |
+------------------------------------------------------------------+
*/
Route::get('/', function () {
    return redirect()->route('dashboard');
});

/*
+------------------------------------------------------------------+
| AREA AUTENTICADA                                                 |
+------------------------------------------------------------------+
*/
Route::middleware(['auth'])->group(function () {
    /*
    +--------------------------------------------------------------+
    | DASHBOARD                                                    |
    +--------------------------------------------------------------+
    */
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');

    /*
    +--------------------------------------------------------------+
    | CLIENTES                                                     |
    +--------------------------------------------------------------+
    */
    Route::middleware('permission:customers.view')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    });

    Route::middleware('permission:customers.create')->group(function () {
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    });

    Route::middleware('permission:customers.edit')->group(function () {
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    });

    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('permission:customers.delete')
        ->name('customers.destroy');

    /*
    +--------------------------------------------------------------+
    | ORCAMENTOS                                                   |
    +--------------------------------------------------------------+
    */
    Route::middleware('permission:budgets.view')->group(function () {
        Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
        Route::get('/budgets/{budget}', [BudgetController::class, 'show'])->name('budgets.show');
        Route::get('/budgets/{budget}/pdf', [BudgetController::class, 'pdf'])->name('budgets.pdf');
    });

    Route::middleware('permission:budgets.create')->group(function () {
        Route::get('/budgets/create', [BudgetController::class, 'create'])->name('budgets.create');
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    });

    Route::middleware('permission:budgets.update')->group(function () {
        Route::put('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
        Route::patch('/budgets/{budget}/change-status', [BudgetController::class, 'changeStatus'])->name('budgets.change-status');
        Route::post('/budgets/{budget}/send-email', [BudgetController::class, 'sendEmail'])->name('budgets.send-email');

        Route::post('/budgets/{budget}/items', [BudgetItemController::class, 'store'])->name('budgets.items.store');
        Route::put('/budgets/{budget}/items/{budgetItem}', [BudgetItemController::class, 'update'])->name('budgets.items.update');
        Route::delete('/budgets/{budget}/items/{budgetItem}', [BudgetItemController::class, 'destroy'])->name('budgets.items.destroy');
    });

    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])
        ->middleware('permission:budgets.delete')
        ->name('budgets.destroy');

    /*
    +--------------------------------------------------------------+
    | CONFIGURACOES                                                |
    +--------------------------------------------------------------+
    */
    Route::middleware(['permission:settings.manage'])->group(function () {
        /*
        +----------------------------------------------------------+
        | PERFIL DA EMPRESA                                        |
        +----------------------------------------------------------+
        */
        Route::get('/company-profile', [CompanyProfileController::class, 'show'])->name('company-profile.show');
        Route::get('/company-profile/edit', [CompanyProfileController::class, 'edit'])->name('company-profile.edit');
        Route::put('/company-profile', [CompanyProfileController::class, 'update'])->name('company-profile.update');
        Route::post('/company-profile/test-email', [CompanyProfileController::class, 'sendTestEmail'])->name('company-profile.test-email');

        /*
        +----------------------------------------------------------+
        | PRAZOS DE PAGAMENTO                                      |
        +----------------------------------------------------------+
        */
        Route::get('/payment-terms', [PaymentTermController::class, 'index'])->name('payment-terms.index');
        Route::get('/payment-terms/create', [PaymentTermController::class, 'create'])->name('payment-terms.create');
        Route::post('/payment-terms', [PaymentTermController::class, 'store'])->name('payment-terms.store');
        Route::get('/payment-terms/{paymentTerm}/edit', [PaymentTermController::class, 'edit'])->name('payment-terms.edit');
        Route::put('/payment-terms/{paymentTerm}', [PaymentTermController::class, 'update'])->name('payment-terms.update');
        Route::delete('/payment-terms/{paymentTerm}', [PaymentTermController::class, 'destroy'])->name('payment-terms.destroy');

        /*
        +----------------------------------------------------------+
        | SERIES DOCUMENTAIS                                       |
        +----------------------------------------------------------+
        */
        Route::resource('document-series', DocumentSeriesController::class)
            ->except(['show']);

        /*
        +----------------------------------------------------------+
        | CATALOGOS AUXILIARES                                     |
        +----------------------------------------------------------+
        */
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

    /*
    +--------------------------------------------------------------+
    | VISTAS DIRETAS                                               |
    +--------------------------------------------------------------+
    */
    Route::get('/obras', function () {
        return view('jobs.index');
    })->middleware('permission:jobs.view')->name('jobs.index');

    Route::get('/stock', function () {
        return view('stock.index');
    })->middleware('permission:stock.view')->name('stock.index');

    Route::get('/utilizadores', function () {
        return view('users.index');
    })->middleware('permission:users.view')->name('users.index');

    /*
    +--------------------------------------------------------------+
    | ARTIGOS                                                      |
    +--------------------------------------------------------------+
    */
    Route::middleware('permission:items.view')->group(function () {
        Route::get('/items', [ItemController::class, 'index'])->name('items.index');
        Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
        Route::get('/items/{item}/files/{file}', [ItemFileController::class, 'show'])->name('items.files.show');
    });

    Route::middleware('permission:items.create')->group(function () {
        Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    });

    Route::middleware('permission:items.edit')->group(function () {
        Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');

        Route::post('/items/{item}/files', [ItemFileController::class, 'store'])->name('items.files.store');
        Route::delete('/items/{item}/files/{file}', [ItemFileController::class, 'destroy'])->name('items.files.destroy');
        Route::patch('/items/{item}/files/{file}/primary', [ItemFileController::class, 'setPrimary'])->name('items.files.primary');
    });

    /*
    +--------------------------------------------------------------+
    | PERFIL UTILIZADOR                                            |
    +--------------------------------------------------------------+
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
+------------------------------------------------------------------+
| AUTENTICACAO                                                     |
+------------------------------------------------------------------+
*/
require __DIR__ . '/auth.php';
