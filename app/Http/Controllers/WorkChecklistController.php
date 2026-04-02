<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\StoreWorkChecklistRequest;
use App\Models\Work;
use App\Models\WorkChecklist;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class WorkChecklistController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(StoreWorkChecklistRequest $request, Work $work): RedirectResponse
    {
        $this->ensureWorkRouteScope($work);
        $this->authorize('create', [WorkChecklist::class, $work]);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        $validated = $request->validated();

        $checklist = WorkChecklist::query()->create([
            'owner_id' => $work->owner_id,
            'work_id' => $work->id,
            'name' => $validated['checklist_name'],
            'description' => $validated['checklist_description'] ?? null,
        ]);

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work_checklist',
            entityId: $checklist->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'name' => $checklist->name,
                'description' => $checklist->description,
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Checklist criada com sucesso.');
    }

    public function destroy(Work $work, WorkChecklist $checklist): RedirectResponse
    {
        $this->ensureChecklistRouteScope($work, $checklist);
        $this->authorize('delete', $checklist);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        $payload = [
            'work_id' => $work->id,
            'work_code' => $work->code,
            'name' => $checklist->name,
            'items_total' => $checklist->items()->count(),
        ];

        $checklist->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'work_checklist',
            entityId: $checklist->id,
            payload: $payload,
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Checklist removida com sucesso.');
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

    private function nonEditableResponse(Work $work): RedirectResponse
    {
        return redirect()
            ->route('works.show', $work)
            ->with('error', 'Obra concluida ou cancelada. Nao e permitido alterar registos operacionais.');
    }
}
