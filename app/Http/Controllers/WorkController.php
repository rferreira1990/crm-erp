<?php

namespace App\Http\Controllers;

use App\Actions\Works\ChangeWorkStatusAction;
use App\Actions\Works\CreateWorkFromAcceptedBudgetAction;
use App\Http\Requests\Works\StoreWorkRequest;
use App\Http\Requests\Works\UpdateWorkRequest;
use App\Models\ActivityLog;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkDailyReport;
use App\Models\WorkExpense;
use App\Models\WorkStatusHistory;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class WorkController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Work::class);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => trim((string) $request->input('status', '')),
            'technical_manager_id' => trim((string) $request->input('technical_manager_id', '')),
            'date_from' => trim((string) $request->input('date_from', '')),
        ];

        $works = Work::query()
            ->with(['customer', 'technicalManager', 'budget'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('work_type', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($filters['status'] !== '', function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['technical_manager_id'] !== '', function ($query) use ($filters) {
                $query->where('technical_manager_id', $filters['technical_manager_id']);
            })
            ->when($filters['date_from'] !== '', function ($query) use ($filters) {
                $query->whereDate('start_date_planned', '>=', $filters['date_from']);
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $users = User::query()
            ->assignableToWorks()
            ->get();

        return view('works.index', compact('works', 'filters', 'users'));
    }

    public function create(): View
    {
        $this->authorize('create', Work::class);

        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        $budgets = Budget::query()
            ->select(['id', 'customer_id', 'code', 'designation', 'status'])
            ->where('status', Budget::STATUS_ACCEPTED)
            ->whereDoesntHave('work')
            ->orderByDesc('id')
            ->get();

        $users = User::query()
            ->assignableToWorks()
            ->get();

        return view('works.create', compact('customers', 'budgets', 'users'));
    }

    public function store(StoreWorkRequest $request): RedirectResponse
    {
        $this->authorize('create', Work::class);

        $validated = $request->validated();
        $teamIds = $this->normalizedTeamIds(
            team: $validated['team'] ?? [],
            technicalManagerId: $validated['technical_manager_id'] ?? null,
        );

        $work = DB::transaction(function () use ($validated, $teamIds) {
            $nextId = ((int) Work::query()->lockForUpdate()->max('id')) + 1;

            $work = Work::create([
                'owner_id' => Auth::id(),
                'customer_id' => $validated['customer_id'],
                'budget_id' => $validated['budget_id'] ?? null,
                'code' => Work::generateCode($nextId),
                'name' => $validated['name'],
                'status' => Work::STATUS_PLANNED,
                'work_type' => $validated['work_type'] ?? null,
                'location' => $validated['location'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'city' => $validated['city'] ?? null,
                'start_date_planned' => $validated['start_date_planned'] ?? null,
                'end_date_planned' => $validated['end_date_planned'] ?? null,
                'start_date_actual' => $validated['start_date_actual'] ?? null,
                'end_date_actual' => $validated['end_date_actual'] ?? null,
                'technical_manager_id' => $validated['technical_manager_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'other_costs' => (float) ($validated['other_costs'] ?? 0),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $work->team()->sync($teamIds);

            WorkStatusHistory::create([
                'work_id' => $work->id,
                'old_status' => null,
                'new_status' => Work::STATUS_PLANNED,
                'notes' => 'Obra criada.',
                'changed_by' => Auth::id(),
            ]);

            return $work;
        });

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work',
            entityId: $work->id,
            payload: [
                'code' => $work->code,
                'name' => $work->name,
                'customer_id' => $work->customer_id,
                'budget_id' => $work->budget_id,
                'technical_manager_id' => $work->technical_manager_id,
                'team' => $teamIds,
                'status' => $work->status,
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Obra criada com sucesso.');
    }

    public function storeFromBudget(Budget $budget, CreateWorkFromAcceptedBudgetAction $action): RedirectResponse
    {
        $this->authorize('create', Work::class);

        if (! (Auth::user()?->can('budgets.view') ?? false)) {
            abort(403);
        }

        if (! $budget->isLatestVersion()) {
            $latestBudget = $budget->latestVersionInGroup();
            $latestBudgetLabel = $latestBudget
                ? $latestBudget->code . ' (' . $latestBudget->versionLabel() . ')'
                : 'a versao mais recente';

            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'Apenas a versao mais recente pode gerar obra. Usa ' . $latestBudgetLabel . '.');
        }

        try {
            $work = $action->execute($budget->loadMissing('customer'));
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', $exception->getMessage());
        }

        $this->activityLogService->log(
            action: ActivityActions::CREATED_FROM_BUDGET,
            entity: 'work',
            entityId: $work->id,
            payload: [
                'work_code' => $work->code,
                'work_name' => $work->name,
                'budget_id' => $budget->id,
                'budget_code' => $budget->code,
                'customer_id' => $budget->customer_id,
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Obra criada a partir do orcamento aceite com sucesso.');
    }

    public function show(Work $work): View
    {
        $this->authorize('view', $work);

        $work->load([
            'customer',
            'budget',
            'technicalManager',
            'team',
            'statusHistories.changedBy',
            'tasks.assignedUser',
            'expenses.user',
        ]);

        $availableStatuses = collect(Work::statuses())
            ->filter(fn ($label, $status) => $work->canChangeTo($status))
            ->all();

        $assignableUsers = User::query()
            ->assignableToWorks()
            ->get();

        $dailyReports = WorkDailyReport::query()
            ->where('work_id', $work->id)
            ->with(['user', 'items.item.unit'])
            ->withCount('items')
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $expenseUsers = User::query()
            ->assignableToWorks()
            ->get(['id', 'name']);

        $expenseTypes = WorkExpense::types();

        $operationalLogs = ActivityLog::query()
            ->with('user:id,name,email')
            ->where(function ($query) use ($work) {
                $query->where(function ($subQuery) use ($work) {
                    $subQuery
                        ->where('entity', 'work')
                        ->where('entity_id', $work->id);
                })->orWhere(function ($subQuery) use ($work) {
                    $subQuery
                        ->whereIn('entity', ['work_task', 'work_material', 'work_task_assignment', 'work_expense', 'work_daily_report'])
                        ->where('payload->work_id', $work->id);
                });
            })
            ->latest('id')
            ->limit(50)
            ->get();

        return view('works.show', compact(
            'work',
            'availableStatuses',
            'assignableUsers',
            'dailyReports',
            'expenseUsers',
            'expenseTypes',
            'operationalLogs',
        ));
    }

    public function edit(Work $work): View
    {
        $this->authorize('update', $work);

        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        $budgets = Budget::query()
            ->select(['id', 'customer_id', 'code', 'designation', 'status'])
            ->where('status', Budget::STATUS_ACCEPTED)
            ->where(function ($query) use ($work) {
                $query->whereDoesntHave('work');

                if ($work->budget_id) {
                    $query->orWhereKey($work->budget_id);
                }
            })
            ->orderByDesc('id')
            ->get();

        $users = User::query()
            ->assignableToWorks()
            ->get();

        $work->load('team');

        return view('works.edit', compact('work', 'customers', 'budgets', 'users'));
    }

    public function update(UpdateWorkRequest $request, Work $work): RedirectResponse
    {
        $this->authorize('update', $work);

        $validated = $request->validated();
        $oldData = $work->only([
            'customer_id',
            'budget_id',
            'name',
            'work_type',
            'location',
            'postal_code',
            'city',
            'start_date_planned',
            'end_date_planned',
            'start_date_actual',
            'end_date_actual',
            'technical_manager_id',
            'description',
            'internal_notes',
            'other_costs',
        ]);

        $oldTechnicalManagerId = $work->technical_manager_id;
        $oldTeamIds = $work->team()->pluck('users.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $teamIds = $this->normalizedTeamIds(
            team: $validated['team'] ?? [],
            technicalManagerId: $validated['technical_manager_id'] ?? null,
        );

        $work->update([
            'customer_id' => $validated['customer_id'],
            'budget_id' => $validated['budget_id'] ?? null,
            'name' => $validated['name'],
            'work_type' => $validated['work_type'] ?? null,
            'location' => $validated['location'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'city' => $validated['city'] ?? null,
            'start_date_planned' => $validated['start_date_planned'] ?? null,
            'end_date_planned' => $validated['end_date_planned'] ?? null,
            'start_date_actual' => $validated['start_date_actual'] ?? null,
            'end_date_actual' => $validated['end_date_actual'] ?? null,
            'technical_manager_id' => $validated['technical_manager_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'other_costs' => (float) ($validated['other_costs'] ?? 0),
            'updated_by' => Auth::id(),
        ]);

        $work->team()->sync($teamIds);

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'work',
            entityId: $work->id,
            payload: [
                'code' => $work->code,
                'old' => $oldData,
                'new' => $work->only(array_keys($oldData)),
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        if ((int) ($validated['technical_manager_id'] ?? 0) !== (int) ($oldTechnicalManagerId ?? 0)) {
            $this->activityLogService->log(
                action: ActivityActions::TECHNICAL_MANAGER_CHANGED,
                entity: 'work',
                entityId: $work->id,
                payload: [
                    'work_id' => $work->id,
                    'work_code' => $work->code,
                    'old_technical_manager_id' => $oldTechnicalManagerId,
                    'new_technical_manager_id' => $validated['technical_manager_id'] ?? null,
                ],
                ownerId: $work->owner_id,
                userId: Auth::id(),
            );
        }

        $newTeamIds = collect($teamIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($newTeamIds !== $oldTeamIds) {
            $this->activityLogService->log(
                action: ActivityActions::TEAM_CHANGED,
                entity: 'work',
                entityId: $work->id,
                payload: [
                    'work_id' => $work->id,
                    'work_code' => $work->code,
                    'old_team' => $oldTeamIds,
                    'new_team' => $newTeamIds,
                ],
                ownerId: $work->owner_id,
                userId: Auth::id(),
            );
        }

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Obra atualizada com sucesso.');
    }

    public function changeStatus(Request $request, Work $work, ChangeWorkStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $work);

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'status_notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'status' => 'estado',
            'status_notes' => 'observacoes',
        ]);

        $oldStatus = $work->status;

        try {
            $action->execute(
                work: $work,
                newStatus: $validated['status'],
                notes: $validated['status_notes'] ?? null,
            );

            $work->refresh();

            $this->activityLogService->log(
                action: $work->status === Work::STATUS_COMPLETED
                    ? ActivityActions::COMPLETED
                    : ActivityActions::STATUS_CHANGED,
                entity: 'work',
                entityId: $work->id,
                payload: [
                    'work_id' => $work->id,
                    'work_code' => $work->code,
                    'old_status' => $oldStatus,
                    'new_status' => $work->status,
                    'notes' => $validated['status_notes'] ?? null,
                ],
                ownerId: $work->owner_id,
                userId: Auth::id(),
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('works.show', $work)
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('works.show', $work)
                ->with('error', 'Ocorreu um erro ao alterar o estado da obra.');
        }

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Estado da obra atualizado com sucesso.');
    }

    public function destroy(Work $work): RedirectResponse
    {
        $this->authorize('delete', $work);

        if (! $work->canBeDeleted()) {
            return redirect()
                ->route('works.show', $work)
                ->with('error', 'So e possivel apagar obras planeadas.');
        }

        $payload = [
            'work_id' => $work->id,
            'work_code' => $work->code,
            'name' => $work->name,
            'status' => $work->status,
            'customer_id' => $work->customer_id,
            'budget_id' => $work->budget_id,
        ];

        $work->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'work',
            entityId: $work->id,
            payload: $payload,
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.index')
            ->with('success', 'Obra apagada com sucesso.');
    }

    /**
     * @param array<int, int|string> $team
     * @return array<int, int>
     */
    private function normalizedTeamIds(array $team, mixed $technicalManagerId): array
    {
        $teamIds = collect($team)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($technicalManagerId !== null && $technicalManagerId !== '') {
            $teamIds->push((int) $technicalManagerId);
        }

        return $teamIds->unique()->values()->all();
    }
}
