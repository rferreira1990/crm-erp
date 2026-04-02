<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\StoreWorkChecklistItemRequest;
use App\Http\Requests\Works\ToggleWorkChecklistItemRequest;
use App\Models\Work;
use App\Models\WorkChecklist;
use App\Models\WorkChecklistItem;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class WorkChecklistItemController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(StoreWorkChecklistItemRequest $request, Work $work, WorkChecklist $checklist): RedirectResponse
    {
        $this->ensureChecklistRouteScope($work, $checklist);
        $this->authorize('create', [WorkChecklistItem::class, $checklist, $work]);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        $validated = $request->validated();

        $item = WorkChecklistItem::query()->create([
            'owner_id' => $work->owner_id,
            'work_checklist_id' => $checklist->id,
            'description' => $validated['item_description'],
            'is_required' => (bool) $validated['item_is_required'],
            'is_completed' => false,
            'completed_by' => null,
            'completed_at' => null,
        ]);

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work_checklist_item',
            entityId: $item->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'work_checklist_id' => $checklist->id,
                'checklist_name' => $checklist->name,
                'description' => $item->description,
                'is_required' => (bool) $item->is_required,
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.checklists.index', $work)
            ->with('success', 'Item da checklist criado com sucesso.');
    }

    public function toggle(
        ToggleWorkChecklistItemRequest $request,
        Work $work,
        WorkChecklist $checklist,
        WorkChecklistItem $item
    ): JsonResponse|RedirectResponse {
        $this->ensureChecklistItemRouteScope($work, $checklist, $item);
        $this->authorize('update', $item);

        if (! $work->isEditable()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Obra concluida ou cancelada. Nao e permitido alterar registos operacionais.',
                ], 422);
            }

            return $this->nonEditableResponse($work);
        }

        $validated = $request->validated();
        $isCompleted = (bool) $validated['is_completed'];

        $item->update([
            'is_completed' => $isCompleted,
            'completed_by' => $isCompleted ? Auth::id() : null,
            'completed_at' => $isCompleted ? now() : null,
        ]);

        $checklist->refresh()->load('items');
        $item->refresh()->load('completedBy:id,name');

        $this->activityLogService->log(
            action: $isCompleted ? ActivityActions::COMPLETED : ActivityActions::UPDATED,
            entity: 'work_checklist_item',
            entityId: $item->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'work_checklist_id' => $checklist->id,
                'checklist_name' => $checklist->name,
                'description' => $item->description,
                'is_required' => (bool) $item->is_required,
                'is_completed' => (bool) $item->is_completed,
                'completed_by' => $item->completed_by,
                'completed_at' => optional($item->completed_at)->format('Y-m-d H:i:s'),
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'item' => [
                    'id' => $item->id,
                    'is_completed' => (bool) $item->is_completed,
                    'completed_by_name' => $item->completedBy?->name,
                    'completed_at' => $item->completed_at?->format('d/m/Y H:i'),
                ],
                'checklist' => [
                    'id' => $checklist->id,
                    'completed_items' => $checklist->completedItemsCount(),
                    'total_items' => $checklist->totalItemsCount(),
                    'pending_required_items' => $checklist->pendingRequiredItemsCount(),
                ],
            ]);
        }

        return redirect()
            ->route('works.checklists.index', $work)
            ->with('success', 'Estado do item atualizado com sucesso.');
    }

    public function destroy(Work $work, WorkChecklist $checklist, WorkChecklistItem $item): RedirectResponse
    {
        $this->ensureChecklistItemRouteScope($work, $checklist, $item);
        $this->authorize('delete', $item);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        $payload = [
            'work_id' => $work->id,
            'work_code' => $work->code,
            'work_checklist_id' => $checklist->id,
            'checklist_name' => $checklist->name,
            'description' => $item->description,
            'is_required' => (bool) $item->is_required,
            'is_completed' => (bool) $item->is_completed,
        ];

        $item->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'work_checklist_item',
            entityId: $item->id,
            payload: $payload,
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.checklists.index', $work)
            ->with('success', 'Item removido com sucesso.');
    }

    private function ensureWorkRouteScope(Work $work): void
    {
        abort_if((int) $work->owner_id !== (int) Auth::id(), 404);
    }

    private function ensureChecklistRouteScope(Work $work, WorkChecklist $checklist): void
    {
        if ((int) $checklist->work_id !== (int) $work->id) {
            abort(404);
        }

        if ((int) $checklist->owner_id !== (int) $work->owner_id) {
            abort(404);
        }

        $this->ensureWorkRouteScope($work);
    }

    private function ensureChecklistItemRouteScope(Work $work, WorkChecklist $checklist, WorkChecklistItem $item): void
    {
        $this->ensureChecklistRouteScope($work, $checklist);

        if ((int) $item->work_checklist_id !== (int) $checklist->id) {
            abort(404);
        }

        if ((int) $item->owner_id !== (int) $work->owner_id) {
            abort(404);
        }
    }

    private function nonEditableResponse(Work $work): RedirectResponse
    {
        return redirect()
            ->route('works.checklists.index', $work)
            ->with('error', 'Obra concluida ou cancelada. Nao e permitido alterar registos operacionais.');
    }
}
