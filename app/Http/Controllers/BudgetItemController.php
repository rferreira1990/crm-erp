<?php

namespace App\Http\Controllers;

use App\Actions\Budgets\AddItemToBudgetAction;
use App\Actions\Budgets\RecalculateBudgetTotalsAction;
use App\Actions\Budgets\UpdateBudgetItemAction;
use App\Http\Requests\Budgets\AddBudgetItemRequest;
use App\Http\Requests\Budgets\UpdateBudgetItemRequest;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BudgetItemController extends Controller
{
    public function __construct(
        protected AddItemToBudgetAction $addItemToBudgetAction,
        protected UpdateBudgetItemAction $updateBudgetItemAction,
        protected RecalculateBudgetTotalsAction $recalculateBudgetTotalsAction,
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(AddBudgetItemRequest $request, Budget $budget): RedirectResponse
    {
        Gate::authorize('update', $budget);

        if ($redirect = $this->ensureLatestVersionForItemMutation($budget)) {
            return $redirect;
        }

        try {
            $budgetItem = $this->addItemToBudgetAction->execute($budget, $request->validatedData());

            $this->activityLogService->log(
                action: ActivityActions::CREATED,
                entity: 'budget_item',
                entityId: $budgetItem->id,
                payload: [
                    'budget_id' => $budget->id,
                    'budget_code' => $budget->code,
                    'item_id' => $budgetItem->item_id,
                    'item_code' => $budgetItem->item_code,
                    'item_name' => $budgetItem->item_name,
                    'item_type' => $budgetItem->item_type,
                    'description' => $budgetItem->description,
                    'unit_name' => $budgetItem->unit_name,
                    'quantity' => $budgetItem->quantity,
                    'unit_price' => $budgetItem->unit_price,
                    'discount_percent' => $budgetItem->discount_percent,
                    'tax_rate_id' => $budgetItem->tax_rate_id,
                    'tax_rate_name' => $budgetItem->tax_rate_name,
                    'tax_percent' => $budgetItem->tax_percent,
                    'subtotal' => $budgetItem->subtotal,
                    'discount_total' => $budgetItem->discount_total,
                    'tax_total' => $budgetItem->tax_total,
                    'total' => $budgetItem->total,
                ],
                ownerId: $budget->owner_id,
                userId: Auth::id()
            );

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
        Gate::authorize('update', $budget);

        if ($budgetItem->budget_id !== $budget->id) {
            Log::warning('Budget item update rejected: item does not belong to budget.', [
                'route_budget_id' => (int) $budget->id,
                'route_budget_item_id' => (int) $budgetItem->id,
                'budget_item_budget_id' => (int) $budgetItem->budget_id,
                'url' => $request->fullUrl(),
                'user_id' => Auth::id(),
            ]);

            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'A linha selecionada nao pertence a este orcamento.');
        }

        if ($redirect = $this->ensureLatestVersionForItemMutation($budget)) {
            return $redirect;
        }

        try {
            $oldData = [
                'item_id' => $budgetItem->item_id,
                'item_code' => $budgetItem->item_code,
                'item_name' => $budgetItem->item_name,
                'item_type' => $budgetItem->item_type,
                'description' => $budgetItem->description,
                'unit_name' => $budgetItem->unit_name,
                'quantity' => $budgetItem->quantity,
                'unit_price' => $budgetItem->unit_price,
                'discount_percent' => $budgetItem->discount_percent,
                'tax_rate_id' => $budgetItem->tax_rate_id,
                'tax_rate_name' => $budgetItem->tax_rate_name,
                'tax_percent' => $budgetItem->tax_percent,
                'tax_exemption_reason_id' => $budgetItem->tax_exemption_reason_id,
                'tax_exemption_reason' => $budgetItem->tax_exemption_reason,
                'notes' => $budgetItem->notes,
                'subtotal' => $budgetItem->subtotal,
                'discount_total' => $budgetItem->discount_total,
                'tax_total' => $budgetItem->tax_total,
                'total' => $budgetItem->total,
            ];

            $budgetItem = $this->updateBudgetItemAction->execute($budgetItem, $request->validatedData());

            $newData = [
                'item_id' => $budgetItem->item_id,
                'item_code' => $budgetItem->item_code,
                'item_name' => $budgetItem->item_name,
                'item_type' => $budgetItem->item_type,
                'description' => $budgetItem->description,
                'unit_name' => $budgetItem->unit_name,
                'quantity' => $budgetItem->quantity,
                'unit_price' => $budgetItem->unit_price,
                'discount_percent' => $budgetItem->discount_percent,
                'tax_rate_id' => $budgetItem->tax_rate_id,
                'tax_rate_name' => $budgetItem->tax_rate_name,
                'tax_percent' => $budgetItem->tax_percent,
                'tax_exemption_reason_id' => $budgetItem->tax_exemption_reason_id,
                'tax_exemption_reason' => $budgetItem->tax_exemption_reason,
                'notes' => $budgetItem->notes,
                'subtotal' => $budgetItem->subtotal,
                'discount_total' => $budgetItem->discount_total,
                'tax_total' => $budgetItem->tax_total,
                'total' => $budgetItem->total,
            ];

            $this->activityLogService->log(
                action: ActivityActions::UPDATED,
                entity: 'budget_item',
                entityId: $budgetItem->id,
                payload: [
                    'budget_id' => $budget->id,
                    'budget_code' => $budget->code,
                    'old' => $oldData,
                    'new' => $newData,
                ],
                ownerId: $budget->owner_id,
                userId: Auth::id()
            );

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
        Gate::authorize('update', $budget);

        if ($budgetItem->budget_id !== $budget->id) {
            Log::warning('Budget item delete rejected: item does not belong to budget.', [
                'route_budget_id' => (int) $budget->id,
                'route_budget_item_id' => (int) $budgetItem->id,
                'budget_item_budget_id' => (int) $budgetItem->budget_id,
                'url' => request()->fullUrl(),
                'user_id' => Auth::id(),
            ]);

            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'A linha selecionada nao pertence a este orcamento.');
        }

        if ($redirect = $this->ensureLatestVersionForItemMutation($budget)) {
            return $redirect;
        }

        if (! $budget->isEditable()) {
            return redirect()
                ->route('budgets.show', $budget)
                ->withErrors([
                    'budget' => 'Este orçamento já não pode ser editado porque não está em rascunho.',
                ]);
        }

        $payload = [
            'budget_id' => $budget->id,
            'budget_code' => $budget->code,
            'item_id' => $budgetItem->item_id,
            'item_code' => $budgetItem->item_code,
            'item_name' => $budgetItem->item_name,
            'item_type' => $budgetItem->item_type,
            'description' => $budgetItem->description,
            'unit_name' => $budgetItem->unit_name,
            'quantity' => $budgetItem->quantity,
            'unit_price' => $budgetItem->unit_price,
            'discount_percent' => $budgetItem->discount_percent,
            'tax_rate_id' => $budgetItem->tax_rate_id,
            'tax_rate_name' => $budgetItem->tax_rate_name,
            'tax_percent' => $budgetItem->tax_percent,
            'tax_exemption_reason_id' => $budgetItem->tax_exemption_reason_id,
            'tax_exemption_reason' => $budgetItem->tax_exemption_reason,
            'notes' => $budgetItem->notes,
            'subtotal' => $budgetItem->subtotal,
            'discount_total' => $budgetItem->discount_total,
            'tax_total' => $budgetItem->tax_total,
            'total' => $budgetItem->total,
        ];

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'budget_item',
            entityId: $budgetItem->id,
            payload: $payload,
            ownerId: $budget->owner_id,
            userId: Auth::id()
        );

        $budgetItem->delete();

        $this->recalculateBudgetTotalsAction->execute($budget);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Linha removida do orçamento com sucesso.');
    }
    private function ensureLatestVersionForItemMutation(Budget $budget): ?RedirectResponse
    {
        if ($budget->isLatestVersion()) {
            return null;
        }

        $latestBudget = $budget->latestVersionInGroup();
        $latestBudgetLabel = $latestBudget
            ? $latestBudget->code . ' (' . $latestBudget->versionLabel() . ')'
            : 'a versao mais recente';

        return redirect()
            ->route('budgets.show', $budget)
            ->withErrors([
                'budget' => 'Esta versao do orcamento esta apenas para consulta. Usa ' . $latestBudgetLabel . ' para alterar linhas.',
            ]);
    }
}
