<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetItemController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentSeriesController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemFamilyController;
use App\Http\Controllers\ItemFileController;
use App\Http\Controllers\PaymentTermController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaxExemptionReasonController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkController;
use App\Http\Controllers\WorkExpenseController;
use App\Http\Controllers\WorkMaterialController;
use App\Http\Controllers\WorkTaskAssignmentController;
use App\Http\Controllers\WorkTaskController;
use App\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

/*
+------------------------------------------------------------------+
| ENTRADA DA APLICACAO                                              |
| Redireciona a raiz para o dashboard.                             |
+------------------------------------------------------------------+
*/
Route::get('/', function () {
    return redirect()->route('dashboard');
});

/*
+------------------------------------------------------------------+
| AREA AUTENTICADA                                                  |
| Todas as rotas dentro deste grupo exigem login.                  |
+------------------------------------------------------------------+
*/
Route::middleware(['auth'])->group(function () {
    /*
    +--------------------------------------------------------------+
    | DASHBOARD                                                    |
    +--------------------------------------------------------------+
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('/dashboard/works', [DashboardController::class, 'works'])
        ->middleware('permission:works.view')
        ->name('dashboard.works');

    Route::get('/dashboard/stock', [DashboardController::class, 'stock'])
        ->middleware('permission:stock.view')
        ->name('dashboard.stock');

    /*
    +--------------------------------------------------------------+
    | CLIENTES                                                     |
    | CRUD de clientes com controlo de permissoes.                |
    +--------------------------------------------------------------+
    */
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
        ->middleware('permission:customers.edit')
        ->name('customers.edit');

    Route::put('/customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:customers.edit')
        ->name('customers.update');

    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('permission:customers.delete')
        ->name('customers.destroy');

   /*
    +--------------------------------------------------------------+
    | OBRAS                                                        |
    | CRUD de obras com controlo de permissoes.                   |
    +--------------------------------------------------------------+
    */
    Route::get('/works', [WorkController::class, 'index'])
        ->middleware('permission:works.view')
        ->name('works.index');

    Route::get('/works/create', [WorkController::class, 'create'])
        ->middleware('permission:works.create')
        ->name('works.create');

    Route::post('/works', [WorkController::class, 'store'])
        ->middleware('permission:works.create')
        ->name('works.store');

    Route::get('/works/{work}', [WorkController::class, 'show'])
        ->middleware('permission:works.view')
        ->name('works.show');

    Route::get('/works/{work}/edit', [WorkController::class, 'edit'])
        ->middleware('permission:works.update')
        ->name('works.edit');

    Route::put('/works/{work}', [WorkController::class, 'update'])
        ->middleware('permission:works.update')
        ->name('works.update');

    Route::patch('/works/{work}/change-status', [WorkController::class, 'changeStatus'])
        ->middleware('permission:works.update')
        ->name('works.change-status');

    Route::post('/works/{work}/tasks', [WorkTaskController::class, 'store'])
        ->middleware('permission:works.update')
        ->name('works.tasks.store');

    Route::put('/works/{work}/tasks/{task}', [WorkTaskController::class, 'update'])
        ->middleware('permission:works.update')
        ->name('works.tasks.update');

    Route::patch('/works/{work}/tasks/{task}/complete', [WorkTaskController::class, 'complete'])
        ->middleware('permission:works.update')
        ->name('works.tasks.complete');

    Route::delete('/works/{work}/tasks/{task}', [WorkTaskController::class, 'destroy'])
        ->middleware('permission:works.update')
        ->name('works.tasks.destroy');

    Route::post('/works/{work}/tasks/{task}/assignments', [WorkTaskAssignmentController::class, 'store'])
        ->middleware('permission:works.update')
        ->name('works.tasks.assignments.store');

    Route::put('/works/{work}/tasks/{task}/assignments/{assignment}', [WorkTaskAssignmentController::class, 'update'])
        ->middleware('permission:works.update')
        ->name('works.tasks.assignments.update');

    Route::delete('/works/{work}/tasks/{task}/assignments/{assignment}', [WorkTaskAssignmentController::class, 'destroy'])
        ->middleware('permission:works.update')
        ->name('works.tasks.assignments.destroy');

    Route::post('/works/{work}/materials', [WorkMaterialController::class, 'store'])
        ->middleware('permission:works.update')
        ->name('works.materials.store');

    Route::put('/works/{work}/materials/{material}', [WorkMaterialController::class, 'update'])
        ->middleware('permission:works.update')
        ->name('works.materials.update');

    Route::delete('/works/{work}/materials/{material}', [WorkMaterialController::class, 'destroy'])
        ->middleware('permission:works.update')
        ->name('works.materials.destroy');

    Route::post('/works/{work}/expenses', [WorkExpenseController::class, 'store'])
        ->middleware('permission:works.update')
        ->name('works.expenses.store');

    Route::put('/works/{work}/expenses/{expense}', [WorkExpenseController::class, 'update'])
        ->middleware('permission:works.update')
        ->name('works.expenses.update');

    Route::delete('/works/{work}/expenses/{expense}', [WorkExpenseController::class, 'destroy'])
        ->middleware('permission:works.update')
        ->name('works.expenses.destroy');

    Route::delete('/works/{work}', [WorkController::class, 'destroy'])
        ->middleware('permission:works.delete')
        ->name('works.destroy');

    /*
    +--------------------------------------------------------------+
    | ORCAMENTOS                                                   |
    | CRUD de orcamentos, estados, email, PDF e itens.            |
    +--------------------------------------------------------------+
    */
    Route::get('/budgets', [BudgetController::class, 'index'])
        ->middleware('permission:budgets.view')
        ->name('budgets.index');

    Route::get('/budgets/create', [BudgetController::class, 'create'])
        ->middleware('permission:budgets.create')
        ->name('budgets.create');

    Route::post('/budgets', [BudgetController::class, 'store'])
        ->middleware('permission:budgets.create')
        ->name('budgets.store');

    Route::get('/budgets/{budget}', [BudgetController::class, 'show'])
        ->middleware('permission:budgets.view')
        ->name('budgets.show');

    Route::put('/budgets/{budget}', [BudgetController::class, 'update'])
        ->middleware('permission:budgets.update')
        ->name('budgets.update');

    Route::patch('/budgets/{budget}/change-status', [BudgetController::class, 'changeStatus'])
        ->middleware('permission:budgets.update')
        ->name('budgets.change-status');

    Route::post('/budgets/{budget}/send-email', [BudgetController::class, 'sendEmail'])
        ->middleware('permission:budgets.update')
        ->name('budgets.send-email');

    Route::post('/budgets/{budget}/works', [WorkController::class, 'storeFromBudget'])
        ->middleware('permission:works.create')
        ->name('budgets.works.store');

    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])
        ->middleware('permission:budgets.delete')
        ->name('budgets.destroy');

    Route::post('/budgets/{budget}/items', [BudgetItemController::class, 'store'])
        ->middleware('permission:budgets.update')
        ->name('budgets.items.store');

    Route::put('/budgets/{budget}/items/{budgetItem}', [BudgetItemController::class, 'update'])
        ->middleware('permission:budgets.update')
        ->name('budgets.items.update');

    Route::delete('/budgets/{budget}/items/{budgetItem}', [BudgetItemController::class, 'destroy'])
        ->middleware('permission:budgets.update')
        ->name('budgets.items.destroy');

    Route::get('/budgets/{budget}/pdf', [BudgetController::class, 'pdf'])
        ->middleware('permission:budgets.view')
        ->name('budgets.pdf');

    /*
    +--------------------------------------------------------------+
    | LOGS DE ATIVIDADE                                            |
    | Visualizacao de logs de atividade com filtros e paginacao.   |
    +--------------------------------------------------------------+
    */
    Route::middleware(['auth', 'permission:activity-logs.view'])
        ->get('/activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index');

    /*
    +--------------------------------------------------------------+
    | CONFIGURACOES                                                |
    | Bloco restrito a utilizadores com settings.manage.           |
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
        | Familias, marcas, unidades, taxas e motivos de isencao. |
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
    | Rotas simples que devolvem paginas por permissao.            |
    +--------------------------------------------------------------+
    */
    Route::get('/stock', [StockMovementController::class, 'index'])
        ->middleware('permission:stock.view')
        ->name('stock.index');

    Route::post('/stock/movements', [StockMovementController::class, 'storeManual'])
        ->middleware('permission:stock.create')
        ->name('stock.movements.store');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('users.index');

    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('permission:users.create')
        ->name('users.create');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:users.create')
        ->name('users.store');

    Route::get('/users/{user}', [UserController::class, 'show'])
        ->middleware('permission:users.view')
        ->name('users.show');

    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.edit')
        ->name('users.edit');

    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.edit')
        ->name('users.update');

    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');

    Route::get('/utilizadores', fn () => redirect()->route('users.index'))
        ->middleware('permission:users.view');

    /*
    +--------------------------------------------------------------+
    | ARTIGOS                                                      |
    | CRUD de artigos e gestao de ficheiros associados.            |
    +--------------------------------------------------------------+
    */
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

    /*
    +--------------------------------------------------------------+
    | PERFIL UTILIZADOR                                            |
    | Edicao, atualizacao e remocao da conta autenticada.          |
    +--------------------------------------------------------------+
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
+------------------------------------------------------------------+
| AUTENTICACAO                                                     |
| Carrega as rotas de login, registo, password reset, etc.         |
+------------------------------------------------------------------+
*/
require __DIR__ . '/auth.php';
