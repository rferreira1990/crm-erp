<?php

namespace App\Http\Controllers;

use App\Actions\Budgets\AddItemToBudgetAction;
use App\Actions\Budgets\RecalculateBudgetTotalsAction;
use App\Actions\Budgets\UpdateBudgetItemAction;
use App\Http\Requests\Budgets\AddBudgetItemRequest;
use App\Http\Requests\Budgets\UpdateBudgetItemRequest;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Item;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function searchItems(Request $request): JsonResponse
    {
        abort_unless(
            ($request->user()?->can('budgets.create') ?? false)
            || ($request->user()?->can('budgets.update') ?? false)
            || ($request->user()?->can('budgets.view') ?? false),
            403
        );

        $term = trim((string) $request->query('q', ''));
        $page = max((int) $request->query('page', 1), 1);
        $perPage = 20;

        if (mb_strlen($term) < 2) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
        }

        $search = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';
        $prefix = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';

        $paginator = Item::query()
            ->select([
                'items.id',
                'items.code',
                'items.name',
                'items.description',
                'items.unit_id',
                'items.supplier_reference',
                'items.barcode',
                'items.type',
            ])
            ->with('unit:id,code,name')
            ->leftJoin('item_families', 'item_families.id', '=', 'items.family_id')
            ->where('items.is_active', true)
            ->where(function ($query) use ($search) {
                $query->where('items.code', 'like', $search)
                    ->orWhere('items.name', 'like', $search)
                    ->orWhere('items.description', 'like', $search)
                    ->orWhere('items.supplier_reference', 'like', $search)
                    ->orWhere('items.barcode', 'like', $search)
                    ->orWhere('item_families.name', 'like', $search);
            })
            ->orderByRaw(
                'CASE
                    WHEN UPPER(items.code) = UPPER(?) THEN 0
                    WHEN items.code LIKE ? THEN 1
                    WHEN items.name LIKE ? THEN 2
                    ELSE 3
                END',
                [$term, $prefix, $prefix]
            )
            ->orderBy('items.name')
            ->paginate($perPage, ['items.id', 'items.code', 'items.name', 'items.description', 'items.unit_id', 'items.type'], 'page', $page);

        $results = $paginator->getCollection()->map(function (Item $item) {
            $unitCode = $item->unit?->code ?: '-';
            $typeLabel = $item->type === 'service' ? 'Servico' : 'Produto';

            return [
                'id' => (int) $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'description' => $item->description,
                'unit_code' => $item->unit?->code,
                'unit_name' => $item->unit?->name,
                'type' => $item->type,
                'type_label' => $typeLabel,
                'text' => $item->code . ' - ' . $item->name . ' (' . $unitCode . ')',
            ];
        })->values();

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function update(
        UpdateBudgetItemRequest $request,
        Budget $budget,
        BudgetItem $budgetItem
    ): RedirectResponse {
        Gate::authorize('update', $budget);

        if ((int) $budgetItem->budget_id !== (int) $budget->id) {
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

        if ((int) $budgetItem->budget_id !== (int) $budget->id) {
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
