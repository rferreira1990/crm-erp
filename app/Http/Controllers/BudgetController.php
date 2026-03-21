<?php

namespace App\Http\Controllers;

use App\Http\Requests\Budgets\StoreBudgetRequest;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Item;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BudgetController extends Controller
{
    /**
     * Lista de orçamentos.
     */
    public function index(): View
    {
        $budgets = Budget::query()
            ->with(['customer', 'creator'])
            ->latest()
            ->paginate(15);

        return view('budgets.index', compact('budgets'));
    }

    /**
     * Formulário de criação de orçamento.
     */
    public function create(): View
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('budgets.create', compact('customers'));
    }

    /**
     * Guarda um novo orçamento.
     */
    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $budget = Budget::create([
            'customer_id' => $request->validated('customer_id'),
            'status' => $request->validated('status'),
            'notes' => $request->validated('notes'),
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', "Orçamento {$budget->code} criado com sucesso.");
    }

    /**
     * Mostra um orçamento e respetivas linhas.
     */
    public function show(Budget $budget): View
    {
        $budget->load([
            'customer',
            'creator',
            'updater',
            'items.item',
            'items.taxRate',
        ]);

        $availableItems = Item::query()
            ->with(['taxRate', 'unit'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'type',
                'description',
                'unit_id',
                'tax_rate_id',
                'sale_price',
                'is_active',
            ]);

        $taxRates = \App\Models\TaxRate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'value']);

        $taxExemptionReasons = \App\Models\TaxExemptionReason::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('budgets.show', compact(
            'budget',
            'availableItems',
            'taxRates',
            'taxExemptionReasons'
        ));
    }
}
