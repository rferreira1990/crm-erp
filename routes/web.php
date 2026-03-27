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

// Redireciona a raiz da app para o dashboard.
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Todas as rotas dentro deste grupo exigem autenticacao.
Route::middleware(['auth'])->group(function () {
    // Mostra a pagina principal apos login.
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');

    // Lista clientes.
    Route::get('/customers', [CustomerController::class, 'index'])
        ->middleware('permission:customers.view')
        ->name('customers.index');

    // Mostra formulario para criar cliente.
    Route::get('/customers/create', [CustomerController::class, 'create'])
        ->middleware('permission:customers.create')
        ->name('customers.create');

    // Guarda novo cliente.
    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware('permission:customers.create')
        ->name('customers.store');

    // Mostra detalhe de um cliente.
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])
        ->middleware('permission:customers.view')
        ->name('customers.show');

    // Mostra formulario para editar cliente.
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])
        ->middleware('permission:customers.edit')
        ->name('customers.edit');

    // Atualiza dados de um cliente.
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:customers.edit')
        ->name('customers.update');

    // Remove um cliente.
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('permission:customers.delete')
        ->name('customers.destroy');

    // Lista orcamentos.
    Route::get('/budgets', [BudgetController::class, 'index'])
        ->middleware('permission:budgets.view')
        ->name('budgets.index');

    // Mostra formulario para criar orcamento.
    Route::get('/budgets/create', [BudgetController::class, 'create'])
        ->middleware('permission:budgets.create')
        ->name('budgets.create');

    // Guarda novo orcamento.
    Route::post('/budgets', [BudgetController::class, 'store'])
        ->middleware('permission:budgets.create')
        ->name('budgets.store');

    // Mostra detalhe de um orcamento.
    Route::get('/budgets/{budget}', [BudgetController::class, 'show'])
        ->middleware('permission:budgets.view')
        ->name('budgets.show');

    // Atualiza dados de um orcamento.
    Route::put('/budgets/{budget}', [BudgetController::class, 'update'])
        ->middleware('permission:budgets.update')
        ->name('budgets.update');

    // Altera o estado de um orcamento (ex.: rascunho, enviado, aprovado).
    Route::patch('/budgets/{budget}/change-status', [BudgetController::class, 'changeStatus'])
        ->middleware('permission:budgets.update')
        ->name('budgets.change-status');

    // Envia orcamento por email.
    Route::post('/budgets/{budget}/send-email', [BudgetController::class, 'sendEmail'])
        ->middleware('permission:budgets.update')
        ->name('budgets.send-email');

    // Remove um orcamento.
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])
        ->middleware('permission:budgets.delete')
        ->name('budgets.destroy');

    // Adiciona item a um orcamento.
    Route::post('/budgets/{budget}/items', [BudgetItemController::class, 'store'])
        ->middleware('permission:budgets.update')
        ->name('budgets.items.store');

    // Atualiza item de um orcamento.
    Route::put('/budgets/{budget}/items/{budgetItem}', [BudgetItemController::class, 'update'])
        ->middleware('permission:budgets.update')
        ->name('budgets.items.update');

    // Remove item de um orcamento.
    Route::delete('/budgets/{budget}/items/{budgetItem}', [BudgetItemController::class, 'destroy'])
        ->middleware('permission:budgets.update')
        ->name('budgets.items.destroy');

    // Gera/mostra PDF do orcamento.
    Route::get('/budgets/{budget}/pdf', [BudgetController::class, 'pdf'])
        ->middleware('permission:budgets.view')
        ->name('budgets.pdf');

    // Rotas de configuracao geral (acesso restrito a gestao de settings).
    Route::middleware(['permission:settings.manage'])->group(function () {
        // Mostra dados do perfil da empresa.
        Route::get('/company-profile', [CompanyProfileController::class, 'show'])->name('company-profile.show');
        // Mostra formulario de edicao do perfil da empresa.
        Route::get('/company-profile/edit', [CompanyProfileController::class, 'edit'])->name('company-profile.edit');
        // Atualiza dados do perfil da empresa.
        Route::put('/company-profile', [CompanyProfileController::class, 'update'])->name('company-profile.update');
        // Envia email de teste com as configuracoes atuais.
        Route::post('/company-profile/test-email', [CompanyProfileController::class, 'sendTestEmail'])->name('company-profile.test-email');

        // Lista prazos de pagamento.
        Route::get('/payment-terms', [PaymentTermController::class, 'index'])->name('payment-terms.index');
        // Mostra formulario para criar prazo de pagamento.
        Route::get('/payment-terms/create', [PaymentTermController::class, 'create'])->name('payment-terms.create');
        // Guarda novo prazo de pagamento.
        Route::post('/payment-terms', [PaymentTermController::class, 'store'])->name('payment-terms.store');
        // Mostra formulario para editar prazo de pagamento.
        Route::get('/payment-terms/{paymentTerm}/edit', [PaymentTermController::class, 'edit'])->name('payment-terms.edit');
        // Atualiza prazo de pagamento.
        Route::put('/payment-terms/{paymentTerm}', [PaymentTermController::class, 'update'])->name('payment-terms.update');
        // Remove prazo de pagamento.
        Route::delete('/payment-terms/{paymentTerm}', [PaymentTermController::class, 'destroy'])->name('payment-terms.destroy');

        // CRUD de series documentais (sem rota show).
        Route::resource('document-series', DocumentSeriesController::class)
            ->except(['show']);

        // Lista familias de artigos.
        Route::get('/item-families', [ItemFamilyController::class, 'index'])->name('item-families.index');
        // Mostra formulario para criar familia de artigos.
        Route::get('/item-families/create', [ItemFamilyController::class, 'create'])->name('item-families.create');
        // Guarda nova familia de artigos.
        Route::post('/item-families', [ItemFamilyController::class, 'store'])->name('item-families.store');
        // Mostra formulario para editar familia de artigos.
        Route::get('/item-families/{item_family}/edit', [ItemFamilyController::class, 'edit'])->name('item-families.edit');
        // Atualiza familia de artigos.
        Route::put('/item-families/{item_family}', [ItemFamilyController::class, 'update'])->name('item-families.update');

        // Lista marcas.
        Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
        // Mostra formulario para criar marca.
        Route::get('/brands/create', [BrandController::class, 'create'])->name('brands.create');
        // Guarda nova marca.
        Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
        // Mostra formulario para editar marca.
        Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');
        // Atualiza marca.
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');

        // Lista unidades.
        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        // Mostra formulario para criar unidade.
        Route::get('/units/create', [UnitController::class, 'create'])->name('units.create');
        // Guarda nova unidade.
        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        // Mostra formulario para editar unidade.
        Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
        // Atualiza unidade.
        Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');

        // Lista taxas de imposto.
        Route::get('/tax-rates', [TaxRateController::class, 'index'])->name('tax-rates.index');
        // Mostra formulario para criar taxa de imposto.
        Route::get('/tax-rates/create', [TaxRateController::class, 'create'])->name('tax-rates.create');
        // Guarda nova taxa de imposto.
        Route::post('/tax-rates', [TaxRateController::class, 'store'])->name('tax-rates.store');
        // Mostra formulario para editar taxa de imposto.
        Route::get('/tax-rates/{tax_rate}/edit', [TaxRateController::class, 'edit'])->name('tax-rates.edit');
        // Atualiza taxa de imposto.
        Route::put('/tax-rates/{tax_rate}', [TaxRateController::class, 'update'])->name('tax-rates.update');

        // Lista motivos de isencao de imposto.
        Route::get('/tax-exemption-reasons', [TaxExemptionReasonController::class, 'index'])->name('tax-exemption-reasons.index');
        // Mostra formulario para criar motivo de isencao.
        Route::get('/tax-exemption-reasons/create', [TaxExemptionReasonController::class, 'create'])->name('tax-exemption-reasons.create');
        // Guarda novo motivo de isencao.
        Route::post('/tax-exemption-reasons', [TaxExemptionReasonController::class, 'store'])->name('tax-exemption-reasons.store');
        // Mostra formulario para editar motivo de isencao.
        Route::get('/tax-exemption-reasons/{tax_exemption_reason}/edit', [TaxExemptionReasonController::class, 'edit'])->name('tax-exemption-reasons.edit');
        // Atualiza motivo de isencao.
        Route::put('/tax-exemption-reasons/{tax_exemption_reason}', [TaxExemptionReasonController::class, 'update'])->name('tax-exemption-reasons.update');
    });

    // Mostra pagina de obras.
    Route::get('/obras', function () {
        return view('jobs.index');
    })
        ->middleware('permission:jobs.view')
        ->name('jobs.index');

    // Mostra pagina de stock.
    Route::get('/stock', function () {
        return view('stock.index');
    })
        ->middleware('permission:stock.view')
        ->name('stock.index');

    // Mostra pagina de utilizadores.
    Route::get('/utilizadores', function () {
        return view('users.index');
    })
        ->middleware('permission:users.view')
        ->name('users.index');

    // Lista artigos.
    Route::get('/items', [ItemController::class, 'index'])
        ->middleware('permission:items.view')
        ->name('items.index');

    // Mostra formulario para criar artigo.
    Route::get('/items/create', [ItemController::class, 'create'])
        ->middleware('permission:items.create')
        ->name('items.create');

    // Guarda novo artigo.
    Route::post('/items', [ItemController::class, 'store'])
        ->middleware('permission:items.create')
        ->name('items.store');

    // Mostra formulario para editar artigo.
    Route::get('/items/{item}/edit', [ItemController::class, 'edit'])
        ->middleware('permission:items.edit')
        ->name('items.edit');

    // Atualiza artigo.
    Route::put('/items/{item}', [ItemController::class, 'update'])
        ->middleware('permission:items.edit')
        ->name('items.update');

    // Faz upload de ficheiro associado ao artigo.
    Route::post('/items/{item}/files', [ItemFileController::class, 'store'])
        ->middleware('permission:items.edit')
        ->name('items.files.store');

    // Remove ficheiro associado ao artigo.
    Route::delete('/items/{item}/files/{file}', [ItemFileController::class, 'destroy'])
        ->middleware('permission:items.edit')
        ->name('items.files.destroy');

    // Define ficheiro como principal do artigo.
    Route::patch('/items/{item}/files/{file}/primary', [ItemFileController::class, 'setPrimary'])
        ->middleware('permission:items.edit')
        ->name('items.files.primary');

    // Mostra/download de ficheiro associado ao artigo.
    Route::get('/items/{item}/files/{file}', [ItemFileController::class, 'show'])
        ->middleware('permission:items.view')
        ->name('items.files.show');

    // Mostra detalhe de artigo.
    Route::get('/items/{item}', [ItemController::class, 'show'])
        ->middleware('permission:items.view')
        ->name('items.show');

    // Mostra formulario de perfil do utilizador autenticado.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Atualiza dados do perfil do utilizador autenticado.
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Apaga conta/perfil do utilizador autenticado.
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Carrega rotas de autenticacao (login, registo, reset password, etc.).
require __DIR__ . '/auth.php';
