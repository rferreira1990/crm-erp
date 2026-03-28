<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\StoreWorkExpenseRequest;
use App\Http\Requests\Works\UpdateWorkExpenseRequest;
use App\Models\Work;
use App\Models\WorkExpense;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class WorkExpenseController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(StoreWorkExpenseRequest $request, Work $work): RedirectResponse
    {
        $this->authorize('update', $work);

        $validated = $request->validated();
        $costData = $this->resolveCostData($validated);

        $expense = $work->expenses()->create([
            'type' => $validated['type'],
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'],
            'user_id' => $validated['user_id'] ?? null,
            'supplier_name' => $validated['supplier_name'] ?? null,
            'receipt_number' => $validated['receipt_number'] ?? null,
            'qty' => $costData['qty'],
            'unit_cost' => $costData['unit_cost'],
            'total_cost' => $costData['total_cost'],
            'km' => $costData['km'],
            'from_location' => $validated['from_location'] ?? null,
            'to_location' => $validated['to_location'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work_expense',
            entityId: $expense->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'type' => $expense->type,
                'expense_date' => $expense->expense_date?->format('Y-m-d'),
                'description' => $expense->description,
                'total_cost' => $expense->total_cost,
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Custo adicional registado com sucesso.');
    }

    public function update(UpdateWorkExpenseRequest $request, Work $work, WorkExpense $expense): RedirectResponse
    {
        $this->authorize('update', $work);

        if ((int) $expense->work_id !== (int) $work->id) {
            abort(404);
        }

        $validated = $request->validated();
        $costData = $this->resolveCostData($validated);

        $oldData = $expense->only([
            'type',
            'expense_date',
            'description',
            'user_id',
            'supplier_name',
            'receipt_number',
            'qty',
            'unit_cost',
            'total_cost',
            'km',
            'from_location',
            'to_location',
            'notes',
        ]);

        $expense->update([
            'type' => $validated['type'],
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'],
            'user_id' => $validated['user_id'] ?? null,
            'supplier_name' => $validated['supplier_name'] ?? null,
            'receipt_number' => $validated['receipt_number'] ?? null,
            'qty' => $costData['qty'],
            'unit_cost' => $costData['unit_cost'],
            'total_cost' => $costData['total_cost'],
            'km' => $costData['km'],
            'from_location' => $validated['from_location'] ?? null,
            'to_location' => $validated['to_location'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'work_expense',
            entityId: $expense->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'old' => $oldData,
                'new' => $expense->only(array_keys($oldData)),
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Custo adicional atualizado com sucesso.');
    }

    public function destroy(Work $work, WorkExpense $expense): RedirectResponse
    {
        $this->authorize('update', $work);

        if ((int) $expense->work_id !== (int) $work->id) {
            abort(404);
        }

        $payload = [
            'work_id' => $work->id,
            'work_code' => $work->code,
            'type' => $expense->type,
            'description' => $expense->description,
            'total_cost' => $expense->total_cost,
        ];

        $expense->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'work_expense',
            entityId: $expense->id,
            payload: $payload,
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Custo adicional removido com sucesso.');
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{qty: ?float, unit_cost: ?float, total_cost: float, km: ?float}
     */
    private function resolveCostData(array $validated): array
    {
        $type = $validated['type'];
        $qty = isset($validated['qty']) ? round((float) $validated['qty'], 3) : null;
        $unitCost = isset($validated['unit_cost']) ? round((float) $validated['unit_cost'], 2) : null;
        $totalCostInput = isset($validated['total_cost']) ? round((float) $validated['total_cost'], 2) : null;
        $km = isset($validated['km']) ? round((float) $validated['km'], 3) : null;

        if ($type === WorkExpense::TYPE_TRAVEL_KM) {
            $totalCost = round(((float) ($km ?? 0)) * ((float) ($unitCost ?? 0)), 2);

            return [
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'km' => $km,
            ];
        }

        if ($totalCostInput !== null) {
            return [
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCostInput,
                'km' => $km,
            ];
        }

        $totalCost = round(((float) ($qty ?? 0)) * ((float) ($unitCost ?? 0)), 2);

        return [
            'qty' => $qty,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'km' => $km,
        ];
    }
}
