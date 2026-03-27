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
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class BudgetItemController extends Controller
{
    public function __construct(
        protected AddItemToBudgetAction $addItemToBudgetAction,
        protected UpdateBudgetItemAction $updateBudgetItemAction,
        protected RecalculateBudgetTotalsAction $recalculateBudgetTotalsAction
    ) {
    }

    public function store(AddBudgetItemRequest $request, Budget $budget): RedirectResponse
    {
        $this->authorizeBudget($budget);

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

    public function update(
        UpdateBudgetItemRequest $request,
        Budget $budget,
        BudgetItem $budgetItem
    ): RedirectResponse {
        $this->authorizeBudget($budget);

        if ($budgetItem->budget_id !== $budget->id) {
            abort(404);
        }

        try {
            $this->updateBudgetItemAction->execute($budgetItem, $request->validatedData());

            return redirect()
                ->route('budgets.show', $budget)
                ->with('success', 'Linha do orçamento atualizada com sucesso.');
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('budgets.show', $budget)
                ->withInput()
                ->withErrors([
                    'budget_item_' . $budgetItem->id => $exception->getMessage(),
                ]);
        }
    }

    public function destroy(Budget $budget, BudgetItem $budgetItem): RedirectResponse
    {
        $this->authorizeBudget($budget);

        if ($budgetItem->budget_id !== $budget->id) {
            abort(404);
        }

        if (! $budget->isEditable()) {
            return redirect()
                ->route('budgets.show', $budget)
                ->withErrors([
                    'budget' => 'Este orçamento já não pode ser editado porque não está em rascunho.',
                ]);
        }

        $budgetItem->delete();

        $this->recalculateBudgetTotalsAction->execute($budget);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Linha removida do orçamento com sucesso.');
    }

    private function authorizeBudget(Budget $budget): void
    {
        abort_unless((int) $budget->owner_id === (int) Auth::id(), 403);
    }
}
