<?php

namespace App\Http\Controllers;

use App\Http\Requests\Budgets\StoreBudgetRequest;
use App\Models\Budget;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(): View
    {
        $budgets = Budget::with(['customer', 'creator'])
            ->latest()
            ->paginate(15);

        return view('budgets.index', compact('budgets'));
    }

    public function create(): View
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('budgets.create', compact('customers'));
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $budget = Budget::create([
            'customer_id' => $request->validated('customer_id'),
            'status' => $request->validated('status'),
            'notes' => $request->validated('notes'),
            'total' => 0,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('budgets.index')
            ->with('success', "Orçamento {$budget->code} criado com sucesso.");
    }
}
