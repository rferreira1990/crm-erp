<?php

namespace App\Http\Controllers;

use App\Actions\Budgets\AddItemToBudgetAction;
use App\Actions\Budgets\RecalculateBudgetTotalsAction;
use App\Actions\Budgets\UpdateBudgetItemAction;
use App\Http\Requests\Budgets\AddBudgetItemRequest;
use App\Http\Requests\Budgets\UpdateBudgetItemRequest;
use App\Models\Budget;
use App\Models\BudgetItem;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class BudgetItemController extends Controller
{
    public function __construct(
        protected AddItemToBudgetAction $addItemToBudgetAction,
        protected UpdateBudgetItemAction $updateBudgetItemAction,
        protected RecalculateBudgetTotalsAction $recalculateBudgetTotalsAction
    ) {
    }

    /**
     * Adiciona uma linha ao orçamento.
     */
    public function store(AddBudgetItemRequest $request, Budget $budget): RedirectResponse
    {
        try {
            $this->addItemToBudgetAction->execute($budget, $request->validatedData());

            return redirect()
                ->route('budgets.show', $budget)
                ->with('success', 'Artigo adicionado ao orçamento com sucesso.');
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('budgets.show', $budget)
                ->withInput()
                ->withErrors([
                    'item_id' => $exception->getMessage(),
                ]);
        }
    }

    /**
     * Atualiza uma linha do orçamento.
     */
    public function update(
        UpdateBudgetItemRequest $request,
        Budget $budget,
        BudgetItem $budgetItem
    ): RedirectResponse {
        if ($budgetItem->budget_id !== $budget->id) {
            abort(404);
        }

        $this->updateBudgetItemAction->execute($budgetItem, $request->validatedData());

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Linha do orçamento atualizada com sucesso.');
    }

    /**
     * Remove uma linha do orçamento.
     */
    public function destroy(Budget $budget, BudgetItem $budgetItem): RedirectResponse
    {
        if ($budgetItem->budget_id !== $budget->id) {
            abort(404);
        }

        $budgetItem->delete();

        $this->recalculateBudgetTotalsAction->execute($budget);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Linha removida do orçamento com sucesso.');
    }
}
