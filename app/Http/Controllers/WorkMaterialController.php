<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\StoreWorkMaterialRequest;
use App\Http\Requests\Works\UpdateWorkMaterialRequest;
use App\Models\Item;
use App\Models\Work;
use App\Models\WorkMaterial;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class WorkMaterialController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(StoreWorkMaterialRequest $request, Work $work): RedirectResponse
    {
        $this->authorize('update', $work);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        $validated = $request->validated();
        $item = Item::query()->with('unit')->findOrFail((int) $validated['item_id']);

        $qty = round((float) $validated['qty'], 3);
        $unitCost = round((float) ($validated['unit_cost'] ?? $item->cost_price ?? 0), 2);
        $totalCost = round($qty * $unitCost, 2);

        $material = $work->materials()->create([
            'item_id' => $item->id,
            'description_snapshot' => $item->name,
            'unit_snapshot' => $item->unit?->name ?: null,
            'qty' => $qty,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work_material',
            entityId: $material->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'item_id' => $item->id,
                'description_snapshot' => $material->description_snapshot,
                'qty' => $material->qty,
                'unit_cost' => $material->unit_cost,
                'total_cost' => $material->total_cost,
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Material adicionado com sucesso.');
    }

    public function update(UpdateWorkMaterialRequest $request, Work $work, WorkMaterial $material): RedirectResponse
    {
        $this->authorize('update', $work);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        if ($material->work_id !== $work->id) {
            abort(404);
        }

        $validated = $request->validated();
        $item = Item::query()->with('unit')->findOrFail((int) $validated['item_id']);

        $qty = round((float) $validated['qty'], 3);
        $unitCost = round((float) $validated['unit_cost'], 2);
        $totalCost = round($qty * $unitCost, 2);

        $oldData = $material->only([
            'item_id',
            'description_snapshot',
            'unit_snapshot',
            'qty',
            'unit_cost',
            'total_cost',
            'notes',
        ]);

        $material->update([
            'item_id' => $item->id,
            'description_snapshot' => $item->name,
            'unit_snapshot' => $item->unit?->name ?: null,
            'qty' => $qty,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'notes' => $validated['notes'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'work_material',
            entityId: $material->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'old' => $oldData,
                'new' => $material->only([
                    'item_id',
                    'description_snapshot',
                    'unit_snapshot',
                    'qty',
                    'unit_cost',
                    'total_cost',
                    'notes',
                ]),
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Material atualizado com sucesso.');
    }

    public function destroy(Work $work, WorkMaterial $material): RedirectResponse
    {
        $this->authorize('update', $work);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        if ($material->work_id !== $work->id) {
            abort(404);
        }

        $payload = [
            'work_id' => $work->id,
            'work_code' => $work->code,
            'item_id' => $material->item_id,
            'description_snapshot' => $material->description_snapshot,
            'qty' => $material->qty,
            'unit_cost' => $material->unit_cost,
            'total_cost' => $material->total_cost,
        ];

        $material->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'work_material',
            entityId: $material->id,
            payload: $payload,
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Material removido com sucesso.');
    }

    private function nonEditableResponse(Work $work): RedirectResponse
    {
        return redirect()
            ->route('works.show', $work)
            ->with('error', 'Obra concluida ou cancelada. Nao e permitido alterar registos operacionais.');
    }
}
