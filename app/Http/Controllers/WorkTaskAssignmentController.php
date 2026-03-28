<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\StoreWorkTaskAssignmentRequest;
use App\Http\Requests\Works\UpdateWorkTaskAssignmentRequest;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignment;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class WorkTaskAssignmentController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(StoreWorkTaskAssignmentRequest $request, Work $work, WorkTask $task): RedirectResponse
    {
        $this->authorize('update', $work);

        if ((int) $task->work_id !== (int) $work->id) {
            abort(404);
        }

        $validated = $request->validated();
        $user = User::query()->findOrFail((int) $validated['user_id']);

        $workedMinutes = $this->resolveWorkedMinutes($validated);
        $hourlyCost = round((float) ($validated['hourly_cost_snapshot'] ?? $user->hourly_cost ?? 0), 2);

        $saleSnapshotInput = $validated['hourly_sale_price_snapshot'] ?? null;
        $hourlySalePrice = $saleSnapshotInput !== null
            ? round((float) $saleSnapshotInput, 2)
            : ($user->hourly_sale_price !== null ? round((float) $user->hourly_sale_price, 2) : null);

        $laborCostTotal = round(($workedMinutes / 60) * $hourlyCost, 2);
        $laborSaleTotal = $hourlySalePrice !== null
            ? round(($workedMinutes / 60) * $hourlySalePrice, 2)
            : null;

        $assignment = $task->assignments()->create([
            'user_id' => $user->id,
            'role_snapshot' => $validated['role_snapshot'] ?? ($user->job_title ?: null),
            'hourly_cost_snapshot' => $hourlyCost,
            'hourly_sale_price_snapshot' => $hourlySalePrice,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'worked_minutes' => $workedMinutes,
            'labor_cost_total' => $laborCostTotal,
            'labor_sale_total' => $laborSaleTotal,
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work_task_assignment',
            entityId: $assignment->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'work_task_id' => $task->id,
                'task_title' => $task->title,
                'user_id' => $assignment->user_id,
                'worked_minutes' => $assignment->worked_minutes,
                'labor_cost_total' => $assignment->labor_cost_total,
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Interveniente de mao de obra adicionado com sucesso.');
    }

    public function update(UpdateWorkTaskAssignmentRequest $request, Work $work, WorkTask $task, WorkTaskAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $work);

        if ((int) $task->work_id !== (int) $work->id || (int) $assignment->work_task_id !== (int) $task->id) {
            abort(404);
        }

        $validated = $request->validated();
        $user = User::query()->findOrFail((int) $validated['user_id']);

        $workedMinutes = $this->resolveWorkedMinutes($validated);
        $hourlyCost = round((float) $validated['hourly_cost_snapshot'], 2);
        $hourlySalePrice = $validated['hourly_sale_price_snapshot'] !== null
            ? round((float) $validated['hourly_sale_price_snapshot'], 2)
            : null;

        $laborCostTotal = round(($workedMinutes / 60) * $hourlyCost, 2);
        $laborSaleTotal = $hourlySalePrice !== null
            ? round(($workedMinutes / 60) * $hourlySalePrice, 2)
            : null;

        $oldData = $assignment->only([
            'user_id',
            'role_snapshot',
            'hourly_cost_snapshot',
            'hourly_sale_price_snapshot',
            'start_time',
            'end_time',
            'worked_minutes',
            'labor_cost_total',
            'labor_sale_total',
            'notes',
        ]);

        $assignment->update([
            'user_id' => $user->id,
            'role_snapshot' => $validated['role_snapshot'] ?? ($user->job_title ?: null),
            'hourly_cost_snapshot' => $hourlyCost,
            'hourly_sale_price_snapshot' => $hourlySalePrice,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'worked_minutes' => $workedMinutes,
            'labor_cost_total' => $laborCostTotal,
            'labor_sale_total' => $laborSaleTotal,
            'notes' => $validated['notes'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'work_task_assignment',
            entityId: $assignment->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'work_task_id' => $task->id,
                'task_title' => $task->title,
                'old' => $oldData,
                'new' => $assignment->only(array_keys($oldData)),
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Interveniente de mao de obra atualizado com sucesso.');
    }

    public function destroy(Work $work, WorkTask $task, WorkTaskAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $work);

        if ((int) $task->work_id !== (int) $work->id || (int) $assignment->work_task_id !== (int) $task->id) {
            abort(404);
        }

        $payload = [
            'work_id' => $work->id,
            'work_code' => $work->code,
            'work_task_id' => $task->id,
            'task_title' => $task->title,
            'user_id' => $assignment->user_id,
            'worked_minutes' => $assignment->worked_minutes,
            'labor_cost_total' => $assignment->labor_cost_total,
        ];

        $assignment->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'work_task_assignment',
            entityId: $assignment->id,
            payload: $payload,
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Interveniente de mao de obra removido com sucesso.');
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function resolveWorkedMinutes(array $validated): int
    {
        $start = $validated['start_time'] ?? null;
        $end = $validated['end_time'] ?? null;

        if ($start && $end) {
            $startTime = Carbon::createFromFormat('H:i', (string) $start);
            $endTime = Carbon::createFromFormat('H:i', (string) $end);

            return max(1, $startTime->diffInMinutes($endTime));
        }

        return max(1, (int) ($validated['worked_minutes'] ?? 0));
    }
}
