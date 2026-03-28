<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\StoreWorkMaterialRequest;
use App\Http\Requests\Works\UpdateWorkMaterialRequest;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Work;
use App\Models\WorkMaterial;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
        $shouldApplyStock = (bool) ($validated['apply_stock_movement'] ?? false);

        try {
            /** @var array{material: WorkMaterial, stock: array<string, mixed>} $result */
            $result = DB::transaction(function () use ($work, $validated, $shouldApplyStock) {
                $item = Item::query()
                    ->with('unit')
                    ->lockForUpdate()
                    ->findOrFail((int) $validated['item_id']);

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

                $stock = $this->syncStockMovement(
                    material: $material,
                    targetItem: $item,
                    targetQty: $qty,
                    shouldApplyStock: $shouldApplyStock,
                );

                return ['material' => $material, 'stock' => $stock];
            });
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('works.show', $work)
                ->with('error', $exception->getMessage());
        }

        $material = $result['material'];
        $stock = $result['stock'];

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work_material',
            entityId: $material->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'item_id' => $material->item_id,
                'description_snapshot' => $material->description_snapshot,
                'qty' => $material->qty,
                'unit_cost' => $material->unit_cost,
                'total_cost' => $material->total_cost,
                'stock' => $stock,
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
        $shouldApplyStock = (bool) ($validated['apply_stock_movement'] ?? false);

        $oldData = $material->loadMissing('stockMovement')->only([
            'item_id',
            'description_snapshot',
            'unit_snapshot',
            'qty',
            'unit_cost',
            'total_cost',
            'notes',
        ]);
        $oldData['stock_applied'] = (bool) $material->stockMovement;

        try {
            /** @var array{material: WorkMaterial, stock: array<string, mixed>} $result */
            $result = DB::transaction(function () use ($material, $validated, $shouldApplyStock) {
                $item = Item::query()
                    ->with('unit')
                    ->lockForUpdate()
                    ->findOrFail((int) $validated['item_id']);

                $qty = round((float) $validated['qty'], 3);
                $unitCost = round((float) $validated['unit_cost'], 2);
                $totalCost = round($qty * $unitCost, 2);

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

                $material->load('stockMovement');

                $stock = $this->syncStockMovement(
                    material: $material,
                    targetItem: $item,
                    targetQty: $qty,
                    shouldApplyStock: $shouldApplyStock,
                );

                return ['material' => $material, 'stock' => $stock];
            });
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('works.show', $work)
                ->with('error', $exception->getMessage());
        }

        $material = $result['material'];
        $stock = $result['stock'];

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
                'stock' => $stock,
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

        $material->load('stockMovement');

        $payload = [
            'work_id' => $work->id,
            'work_code' => $work->code,
            'item_id' => $material->item_id,
            'description_snapshot' => $material->description_snapshot,
            'qty' => $material->qty,
            'unit_cost' => $material->unit_cost,
            'total_cost' => $material->total_cost,
            'stock' => [
                'applied' => (bool) $material->stockMovement,
            ],
        ];

        try {
            DB::transaction(function () use ($material, &$payload) {
                $stock = $this->removeStockMovement($material);
                $payload['stock'] = $stock;

                $material->delete();
            });
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('works.show', $work)
                ->with('error', $exception->getMessage());
        }

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

    /**
     * @return array<string, mixed>
     */
    private function syncStockMovement(WorkMaterial $material, Item $targetItem, float $targetQty, bool $shouldApplyStock): array
    {
        $movement = $material->stockMovement()->lockForUpdate()->first();

        if (! $shouldApplyStock) {
            if (! $movement) {
                return ['applied' => false];
            }

            return $this->revertAndDeleteMovement($movement);
        }

        if (! $targetItem->tracks_stock) {
            throw new RuntimeException('O artigo selecionado nao controla stock.');
        }

        if (! $movement) {
            $before = round((float) $targetItem->current_stock, 3);
            $after = round($before - $targetQty, 3);
            if ($after < 0) {
                throw new RuntimeException('Stock insuficiente para o artigo selecionado.');
            }

            $targetItem->update(['current_stock' => $after]);

            $movement = StockMovement::query()->create([
                'item_id' => $targetItem->id,
                'work_material_id' => $material->id,
                'movement_type' => StockMovement::TYPE_WORK_MATERIAL,
                'direction' => StockMovement::DIRECTION_OUT,
                'quantity' => $targetQty,
                'stock_before' => $before,
                'stock_after' => $after,
                'occurred_at' => now(),
                'source_type' => 'work_material',
                'source_id' => $material->id,
                'notes' => 'Saida de stock por material de obra ' . $material->work?->code,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            return [
                'applied' => true,
                'movement_id' => $movement->id,
                'item_id' => $targetItem->id,
                'quantity' => $movement->quantity,
                'stock_before' => $before,
                'stock_after' => $after,
            ];
        }

        if ((int) $movement->item_id !== (int) $targetItem->id) {
            $oldItem = Item::query()->lockForUpdate()->findOrFail((int) $movement->item_id);
            $oldBefore = round((float) $oldItem->current_stock, 3);
            $oldAfter = round($oldBefore + (float) $movement->quantity, 3);
            $oldItem->update(['current_stock' => $oldAfter]);

            $newBefore = round((float) $targetItem->current_stock, 3);
            $newAfter = round($newBefore - $targetQty, 3);
            if ($newAfter < 0) {
                throw new RuntimeException('Stock insuficiente para o artigo selecionado.');
            }
            $targetItem->update(['current_stock' => $newAfter]);

            $movement->update([
                'item_id' => $targetItem->id,
                'quantity' => $targetQty,
                'stock_before' => $newBefore,
                'stock_after' => $newAfter,
                'occurred_at' => now(),
                'source_type' => 'work_material',
                'source_id' => $material->id,
                'updated_by' => Auth::id(),
            ]);

            return [
                'applied' => true,
                'movement_id' => $movement->id,
                'item_id' => $targetItem->id,
                'quantity' => $movement->quantity,
                'stock_before' => $newBefore,
                'stock_after' => $newAfter,
                'switched_item' => true,
                'reverted_previous_item_id' => $oldItem->id,
                'reverted_previous_stock_before' => $oldBefore,
                'reverted_previous_stock_after' => $oldAfter,
            ];
        }

        $currentStock = round((float) $targetItem->current_stock, 3);
        $oldQty = round((float) $movement->quantity, 3);
        $delta = round($targetQty - $oldQty, 3);
        $after = round($currentStock - $delta, 3);
        if ($after < 0) {
            throw new RuntimeException('Stock insuficiente para o artigo selecionado.');
        }

        $targetItem->update(['current_stock' => $after]);
        $movement->update([
            'quantity' => $targetQty,
            'stock_before' => $currentStock,
            'stock_after' => $after,
            'occurred_at' => now(),
            'source_type' => 'work_material',
            'source_id' => $material->id,
            'updated_by' => Auth::id(),
        ]);

        return [
            'applied' => true,
            'movement_id' => $movement->id,
            'item_id' => $targetItem->id,
            'quantity' => $movement->quantity,
            'stock_before' => $currentStock,
            'stock_after' => $after,
            'adjusted_delta' => $delta,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function removeStockMovement(WorkMaterial $material): array
    {
        $movement = $material->stockMovement()->lockForUpdate()->first();
        if (! $movement) {
            return ['applied' => false];
        }

        return $this->revertAndDeleteMovement($movement);
    }

    /**
     * @return array<string, mixed>
     */
    private function revertAndDeleteMovement(StockMovement $movement): array
    {
        $item = Item::query()->lockForUpdate()->findOrFail((int) $movement->item_id);
        $before = round((float) $item->current_stock, 3);
        $after = round($before + (float) $movement->quantity, 3);

        $item->update(['current_stock' => $after]);

        $snapshot = [
            'applied' => false,
            'reverted' => true,
            'movement_id' => $movement->id,
            'item_id' => $item->id,
            'quantity' => (float) $movement->quantity,
            'stock_before' => $before,
            'stock_after' => $after,
        ];

        $movement->delete();

        return $snapshot;
    }

    private function nonEditableResponse(Work $work): RedirectResponse
    {
        return redirect()
            ->route('works.show', $work)
            ->with('error', 'Obra concluida ou cancelada. Nao e permitido alterar registos operacionais.');
    }
}
