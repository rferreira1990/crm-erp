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
use App\Http\Controllers\ItemImportController;
use App\Http\Controllers\PaymentTermController;
use App\Http\Controllers\PurchaseQuoteController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierFileController;
use App\Http\Controllers\SupplierContactController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxExemptionReasonController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkController;
use App\Http\Controllers\WorkChecklistController;
use App\Http\Controllers\WorkChecklistItemController;
use App\Http\Controllers\WorkDailyReportController;
use App\Http\Controllers\WorkExpenseController;
use App\Http\Controllers\WorkFileController;
use App\Http\Controllers\WorkMaterialController;
use App\Http\Controllers\WorkTaskAssignmentController;
use App\Http\Controllers\WorkTaskController;
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
    | FORNECEDORES                                                 |
    | CRUD de fornecedores e gestao de contactos.                 |
    +--------------------------------------------------------------+
    */
    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->middleware('permission:suppliers.view')
        ->name('suppliers.index');

    Route::get('/suppliers/create', [SupplierController::class, 'create'])
        ->middleware('permission:suppliers.create')
        ->name('suppliers.create');

    Route::post('/suppliers', [SupplierController::class, 'store'])
        ->middleware('permission:suppliers.create')
        ->name('suppliers.store');

    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])
        ->middleware('permission:suppliers.view')
        ->name('suppliers.show');

    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])
        ->middleware('permission:suppliers.update')
        ->name('suppliers.edit');

    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:suppliers.update')
        ->name('suppliers.update');

    Route::patch('/suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])
        ->middleware('permission:suppliers.update')
        ->name('suppliers.toggle-active');

    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:suppliers.delete')
        ->name('suppliers.destroy');

    Route::post('/suppliers/{supplier}/contacts', [SupplierContactController::class, 'store'])
        ->middleware('permission:suppliers.update')
        ->name('suppliers.contacts.store');

    Route::put('/suppliers/{supplier}/contacts/{contact}', [SupplierContactController::class, 'update'])
        ->middleware('permission:suppliers.update')
        ->name('suppliers.contacts.update');

    Route::delete('/suppliers/{supplier}/contacts/{contact}', [SupplierContactController::class, 'destroy'])
        ->middleware('permission:suppliers.update')
        ->name('suppliers.contacts.destroy');

    Route::post('/suppliers/{supplier}/logo', [SupplierFileController::class, 'storeLogo'])
        ->middleware('permission:suppliers.update')
        ->name('suppliers.logo.store');

    Route::delete('/suppliers/{supplier}/logo', [SupplierFileController::class, 'destroyLogo'])
        ->middleware('permission:suppliers.update')
        ->name('suppliers.logo.destroy');

    Route::post('/suppliers/{supplier}/files', [SupplierFileController::class, 'store'])
        ->middleware('permission:suppliers.update')
        ->name('suppliers.files.store');

    Route::get('/suppliers/{supplier}/files/{file}', [SupplierFileController::class, 'show'])
        ->middleware('permission:suppliers.view')
        ->name('suppliers.files.show');

    Route::delete('/suppliers/{supplier}/files/{file}', [SupplierFileController::class, 'destroy'])
        ->middleware('permission:suppliers.update')
        ->name('suppliers.files.destroy');

   /*
    +--------------------------------------------------------------+
    | COMPRAS / RFQ                                                |
    | Pedido de cotacao e comparacao de propostas.                |
    +--------------------------------------------------------------+
    */
    Route::get('/api/items/search', [PurchaseRequestController::class, 'searchItems'])
        ->middleware('permission:purchases.view|purchases.create|purchases.update')
        ->name('api.items.search');

    Route::get('/api/budgets/items/search', [BudgetItemController::class, 'searchItems'])
        ->middleware('permission:budgets.view|budgets.create|budgets.update')
        ->name('api.budgets.items.search');

    Route::get('/api/works/items/search', [WorkDailyReportController::class, 'searchItems'])
        ->middleware('permission:works.view|works.update')
        ->name('api.works.items.search');

    Route::get('/purchase-requests', [PurchaseRequestController::class, 'index'])
        ->middleware('permission:purchases.view')
        ->name('purchase-requests.index');

    Route::get('/purchase-requests/create', [PurchaseRequestController::class, 'create'])
        ->middleware('permission:purchases.create')
        ->name('purchase-requests.create');

    Route::post('/purchase-requests', [PurchaseRequestController::class, 'store'])
        ->middleware('permission:purchases.create')
        ->name('purchase-requests.store');

    Route::get('/purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'show'])
        ->middleware('permission:purchases.view')
        ->name('purchase-requests.show');

    Route::get('/purchase-requests/{purchaseRequest}/edit', [PurchaseRequestController::class, 'edit'])
        ->middleware('permission:purchases.update')
        ->name('purchase-requests.edit');

    Route::put('/purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'update'])
        ->middleware('permission:purchases.update')
        ->name('purchase-requests.update');

    Route::patch('/purchase-requests/{purchaseRequest}/change-status', [PurchaseRequestController::class, 'changeStatus'])
        ->middleware('permission:purchases.update')
        ->name('purchase-requests.change-status');

    Route::get('/purchase-requests/{purchaseRequest}/pdf', [PurchaseRequestController::class, 'pdf'])
        ->middleware('permission:purchases.view')
        ->name('purchase-requests.pdf');

    Route::post('/purchase-requests/{purchaseRequest}/send-email', [PurchaseRequestController::class, 'sendEmail'])
        ->middleware('permission:purchases.update')
        ->name('purchase-requests.send-email');

    Route::delete('/purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'destroy'])
        ->middleware('permission:purchases.delete')
        ->name('purchase-requests.destroy');

    Route::post('/purchase-requests/{purchaseRequest}/quotes', [PurchaseQuoteController::class, 'store'])
        ->middleware('permission:purchases.update')
        ->name('purchase-requests.quotes.store');

    Route::post('/purchase-requests/{purchaseRequest}/award', [PurchaseRequestController::class, 'award'])
        ->middleware('permission:purchases.award')
        ->name('purchase-requests.award');

    Route::get('/purchase-requests/{purchaseRequest}/awards/{award}/pdf', [PurchaseRequestController::class, 'awardPdf'])
        ->middleware('permission:purchases.view')
        ->name('purchase-requests.awards.pdf');

    Route::post('/purchase-requests/{purchaseRequest}/awards/{award}/send-email', [PurchaseRequestController::class, 'sendAwardEmail'])
        ->middleware('permission:purchases.update')
        ->name('purchase-requests.awards.send-email');

    Route::get('/purchase-requests/{purchaseRequest}/supplier-orders/{order}/pdf', [PurchaseRequestController::class, 'supplierOrderPdf'])
        ->middleware('permission:purchases.view')
        ->name('purchase-requests.supplier-orders.pdf');

    Route::put('/purchase-requests/{purchaseRequest}/quotes/{quote}', [PurchaseQuoteController::class, 'update'])
        ->middleware('permission:purchases.update')
        ->name('purchase-requests.quotes.update');

    Route::get('/purchase-requests/{purchaseRequest}/quotes/{quote}/pdf', [PurchaseQuoteController::class, 'showPdf'])
        ->middleware('permission:purchases.view')
        ->name('purchase-requests.quotes.pdf');

    Route::delete('/purchase-requests/{purchaseRequest}/quotes/{quote}/pdf', [PurchaseQuoteController::class, 'removePdf'])
        ->middleware('permission:purchases.update')
        ->name('purchase-requests.quotes.remove-pdf');

    Route::delete('/purchase-requests/{purchaseRequest}/quotes/{quote}', [PurchaseQuoteController::class, 'destroy'])
        ->middleware('permission:purchases.update')
        ->name('purchase-requests.quotes.destroy');

    Route::patch('/purchase-requests/{purchaseRequest}/quotes/{quote}/select', [PurchaseQuoteController::class, 'select'])
        ->middleware('permission:purchases.update')
        ->name('purchase-requests.quotes.select');

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

    Route::get('/works/{work}/checklists', [WorkChecklistController::class, 'index'])
        ->middleware('permission:works.view')
        ->name('works.checklists.index');

    Route::post('/works/{work}/checklists', [WorkChecklistController::class, 'store'])
        ->middleware('permission:works.update')
        ->name('works.checklists.store');

    Route::post('/works/{work}/checklists/templates/apply', [WorkChecklistController::class, 'applyTemplate'])
        ->middleware('permission:works.update')
        ->name('works.checklists.templates.apply');

    Route::delete('/works/{work}/checklists/{checklist}', [WorkChecklistController::class, 'destroy'])
        ->middleware('permission:works.update')
        ->name('works.checklists.destroy');

    Route::post('/works/{work}/checklists/{checklist}/items', [WorkChecklistItemController::class, 'store'])
        ->middleware('permission:works.update')
        ->name('works.checklists.items.store');

    Route::patch('/works/{work}/checklists/{checklist}/items/{item}/toggle', [WorkChecklistItemController::class, 'toggle'])
        ->middleware('permission:works.update')
        ->name('works.checklists.items.toggle');

    Route::delete('/works/{work}/checklists/{checklist}/items/{item}', [WorkChecklistItemController::class, 'destroy'])
        ->middleware('permission:works.update')
        ->name('works.checklists.items.destroy');

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

    Route::get('/works/{work}/daily-reports', [WorkDailyReportController::class, 'index'])
        ->middleware('permission:works.view')
        ->name('works.daily-reports.index');

    Route::get('/works/{work}/daily-reports/create', [WorkDailyReportController::class, 'create'])
        ->middleware('permission:works.update')
        ->name('works.daily-reports.create');

    Route::post('/works/{work}/daily-reports', [WorkDailyReportController::class, 'store'])
        ->middleware('permission:works.update')
        ->name('works.daily-reports.store');

    Route::get('/works/{work}/daily-reports/{dailyReport}', [WorkDailyReportController::class, 'show'])
        ->middleware('permission:works.view')
        ->name('works.daily-reports.show');

    Route::get('/works/{work}/daily-reports/{dailyReport}/edit', [WorkDailyReportController::class, 'edit'])
        ->middleware('permission:works.update')
        ->name('works.daily-reports.edit');

    Route::put('/works/{work}/daily-reports/{dailyReport}', [WorkDailyReportController::class, 'update'])
        ->middleware('permission:works.update')
        ->name('works.daily-reports.update');

    Route::delete('/works/{work}/daily-reports/{dailyReport}', [WorkDailyReportController::class, 'destroy'])
        ->middleware('permission:works.update')
        ->name('works.daily-reports.destroy');

    Route::get('/works/{work}/files', [WorkFileController::class, 'index'])
        ->middleware('permission:works.view')
        ->name('works.files.index');

    Route::post('/works/{work}/files', [WorkFileController::class, 'store'])
        ->middleware('permission:works.update')
        ->name('works.files.store');

    Route::get('/works/{work}/files/{file}', [WorkFileController::class, 'download'])
        ->middleware('permission:works.view')
        ->name('works.files.download');

    Route::delete('/works/{work}/files/{file}', [WorkFileController::class, 'destroy'])
        ->middleware('permission:works.update')
        ->name('works.files.destroy');

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

    Route::post('/budgets/{budget}/duplicate', [BudgetController::class, 'duplicate'])
        ->middleware('permission:budgets.create')
        ->name('budgets.duplicate');

    Route::post('/budgets/{budget}/versions', [BudgetController::class, 'createVersion'])
        ->middleware('permission:budgets.create')
        ->name('budgets.versions.store');

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
        Route::delete('/item-families/{item_family}', [ItemFamilyController::class, 'destroy'])->name('item-families.destroy');

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
        ->middleware(['permission:users.create', 'throttle:10,1'])
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

    Route::post('/users/{user}/send-password-reset', [UserController::class, 'sendPasswordReset'])
        ->middleware(['permission:users.edit', 'throttle:5,1'])
        ->name('users.send-password-reset');

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

    Route::get('/items/export/csv', [ItemController::class, 'exportCsv'])
        ->middleware('permission:items.view')
        ->name('items.export.csv');

    Route::get('/items/import', [ItemImportController::class, 'show'])
        ->middleware(['permission:items.create', 'permission:items.edit'])
        ->name('items.import.form');

    Route::get('/items/import/template', [ItemImportController::class, 'templateCsv'])
        ->middleware(['permission:items.create', 'permission:items.edit'])
        ->name('items.import.template');

    Route::post('/items/import/preview', [ItemImportController::class, 'preview'])
        ->middleware(['permission:items.create', 'permission:items.edit'])
        ->name('items.import.preview');

    Route::post('/items/import/confirm', [ItemImportController::class, 'confirm'])
        ->middleware(['permission:items.create', 'permission:items.edit'])
        ->name('items.import.confirm');

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
