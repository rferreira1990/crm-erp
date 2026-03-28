<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\StoreWorkTaskRequest;
use App\Http\Requests\Works\UpdateWorkTaskRequest;
use App\Models\Work;
use App\Models\WorkTask;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class WorkTaskController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(StoreWorkTaskRequest $request, Work $work): RedirectResponse
    {
        $this->authorize('update', $work);

        $validated = $request->validated();
        $sortOrder = $validated['sort_order'] ?? ((int) $work->tasks()->max('sort_order')) + 1;
        $status = $validated['status'] ?? WorkTask::STATUS_PLANNED;

        $task = $work->tasks()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $status,
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'planned_date' => $validated['planned_date'] ?? null,
            'planned_start_time' => $validated['planned_start_time'] ?? null,
            'planned_end_time' => $validated['planned_end_time'] ?? null,
            'completed_at' => $status === WorkTask::STATUS_COMPLETED ? now() : null,
            'sort_order' => $sortOrder,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work_task',
            entityId: $task->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'title' => $task->title,
                'status' => $task->status,
                'assigned_user_id' => $task->assigned_user_id,
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Tarefa criada com sucesso.');
    }

    public function update(UpdateWorkTaskRequest $request, Work $work, WorkTask $task): RedirectResponse
    {
        $this->authorize('update', $work);

        if ($task->work_id !== $work->id) {
            abort(404);
        }

        $validated = $request->validated();
        $oldData = $task->only([
            'title',
            'description',
            'status',
            'assigned_user_id',
            'planned_date',
            'planned_start_time',
            'planned_end_time',
            'sort_order',
        ]);

        $status = $validated['status'];
        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $status,
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'planned_date' => $validated['planned_date'] ?? null,
            'planned_start_time' => $validated['planned_start_time'] ?? null,
            'planned_end_time' => $validated['planned_end_time'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $task->sort_order,
            'completed_at' => $status === WorkTask::STATUS_COMPLETED
                ? ($task->completed_at ?? now())
                : null,
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'work_task',
            entityId: $task->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'old' => $oldData,
                'new' => $task->only([
                    'title',
                    'description',
                    'status',
                    'assigned_user_id',
                    'planned_date',
                    'planned_start_time',
                    'planned_end_time',
                    'sort_order',
                ]),
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Tarefa atualizada com sucesso.');
    }

    public function complete(Work $work, WorkTask $task): RedirectResponse
    {
        $this->authorize('update', $work);

        if ($task->work_id !== $work->id) {
            abort(404);
        }

        $task->update([
            'status' => WorkTask::STATUS_COMPLETED,
            'completed_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::COMPLETED,
            entity: 'work_task',
            entityId: $task->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'title' => $task->title,
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Tarefa concluida com sucesso.');
    }

    public function destroy(Work $work, WorkTask $task): RedirectResponse
    {
        $this->authorize('update', $work);

        if ($task->work_id !== $work->id) {
            abort(404);
        }

        $payload = [
            'work_id' => $work->id,
            'work_code' => $work->code,
            'title' => $task->title,
            'status' => $task->status,
        ];

        $task->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'work_task',
            entityId: $task->id,
            payload: $payload,
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Tarefa removida com sucesso.');
    }
}
