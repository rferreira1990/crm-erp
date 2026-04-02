<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\StoreWorkDailyReportRequest;
use App\Http\Requests\Works\UpdateWorkDailyReportRequest;
use App\Models\Item;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkDailyReport;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class WorkDailyReportController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index(Request $request, Work $work): View
    {
        $this->ensureWorkRouteScope($work);
        $this->authorize('viewAny', [WorkDailyReport::class, $work]);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'day_status' => trim((string) $request->input('day_status', '')),
            'date_from' => trim((string) $request->input('date_from', '')),
            'date_to' => trim((string) $request->input('date_to', '')),
            'user_id' => trim((string) $request->input('user_id', '')),
        ];

        $reports = $work->dailyReports()
            ->with(['user'])
            ->withCount('items')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('work_summary', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%')
                        ->orWhere('incidents', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['day_status'] !== '', function ($query) use ($filters) {
                $query->where('day_status', $filters['day_status']);
            })
            ->when($filters['date_from'] !== '', function ($query) use ($filters) {
                $query->whereDate('report_date', '>=', $filters['date_from']);
            })
            ->when($filters['date_to'] !== '', function ($query) use ($filters) {
                $query->whereDate('report_date', '<=', $filters['date_to']);
            })
            ->when($filters['user_id'] !== '', function ($query) use ($filters) {
                $query->where('user_id', (int) $filters['user_id']);
            })
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $users = User::query()
            ->whereIn('id', $work->dailyReports()->select('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('works.daily-reports.index', compact(
            'work',
            'reports',
            'filters',
            'users',
        ));
    }

    public function create(Work $work): View
    {
        $this->ensureWorkRouteScope($work);
        $this->authorize('create', [WorkDailyReport::class, $work]);

        $availableItems = $this->availableItemsForOwner((int) $work->owner_id);

        return view('works.daily-reports.create', [
            'work' => $work,
            'dailyReport' => new WorkDailyReport([
                'report_date' => now(),
                'day_status' => WorkDailyReport::STATUS_NORMAL,
                'hours_spent' => 0,
            ]),
            'availableItems' => $availableItems,
            'dayStatuses' => WorkDailyReport::statuses(),
        ]);
    }

    public function store(StoreWorkDailyReportRequest $request, Work $work): RedirectResponse
    {
        $this->ensureWorkRouteScope($work);
        $this->authorize('create', [WorkDailyReport::class, $work]);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        $validated = $request->validated();

        $report = DB::transaction(function () use ($work, $validated) {
            $report = WorkDailyReport::query()->create([
                'owner_id' => $work->owner_id,
                'work_id' => $work->id,
                'user_id' => Auth::id(),
                'report_date' => $validated['report_date'],
                'day_status' => $validated['day_status'],
                'work_summary' => $validated['work_summary'],
                'hours_spent' => (float) $validated['hours_spent'],
                'notes' => $validated['notes'] ?? null,
                'incidents' => $validated['incidents'] ?? null,
            ]);

            $this->syncItems(
                report: $report,
                rows: $validated['items'] ?? [],
                ownerId: (int) $work->owner_id,
            );

            return $report->load(['user', 'items.item.unit']);
        });

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work_daily_report',
            entityId: $report->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'report_date' => optional($report->report_date)->format('Y-m-d'),
                'day_status' => $report->day_status,
                'hours_spent' => (float) $report->hours_spent,
                'items_count' => $report->items->count(),
                'items_total_qty' => (float) $report->items->sum('quantity'),
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.daily-reports.show', [$work, $report])
            ->with('success', 'Registo diario criado com sucesso.');
    }

    public function show(Work $work, WorkDailyReport $dailyReport): View
    {
        $this->ensureReportRouteScope($work, $dailyReport);
        $this->authorize('view', $dailyReport);

        $dailyReport->load([
            'user',
            'items.item.unit',
        ]);

        return view('works.daily-reports.show', [
            'work' => $work,
            'dailyReport' => $dailyReport,
            'dayStatuses' => WorkDailyReport::statuses(),
        ]);
    }

    public function edit(Work $work, WorkDailyReport $dailyReport): View
    {
        $this->ensureReportRouteScope($work, $dailyReport);
        $this->authorize('update', $dailyReport);

        if (! $work->isEditable()) {
            abort(403, 'Obra concluida ou cancelada. Nao e permitido editar registos diarios.');
        }

        $dailyReport->load('items');

        return view('works.daily-reports.edit', [
            'work' => $work,
            'dailyReport' => $dailyReport,
            'availableItems' => $this->availableItemsForOwner((int) $work->owner_id),
            'dayStatuses' => WorkDailyReport::statuses(),
        ]);
    }

    public function update(UpdateWorkDailyReportRequest $request, Work $work, WorkDailyReport $dailyReport): RedirectResponse
    {
        $this->ensureReportRouteScope($work, $dailyReport);
        $this->authorize('update', $dailyReport);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        $validated = $request->validated();
        $dailyReport->load('items');

        $oldData = $dailyReport->only([
            'report_date',
            'day_status',
            'work_summary',
            'hours_spent',
            'notes',
            'incidents',
        ]);
        $oldItemsCount = $dailyReport->items->count();
        $oldItemsTotalQty = (float) $dailyReport->items->sum('quantity');

        DB::transaction(function () use ($dailyReport, $validated, $work) {
            $dailyReport->update([
                'report_date' => $validated['report_date'],
                'day_status' => $validated['day_status'],
                'work_summary' => $validated['work_summary'],
                'hours_spent' => (float) $validated['hours_spent'],
                'notes' => $validated['notes'] ?? null,
                'incidents' => $validated['incidents'] ?? null,
            ]);

            $this->syncItems(
                report: $dailyReport,
                rows: $validated['items'] ?? [],
                ownerId: (int) $work->owner_id,
            );
        });

        $dailyReport->refresh()->load('items');

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'work_daily_report',
            entityId: $dailyReport->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'old' => $oldData,
                'new' => $dailyReport->only([
                    'report_date',
                    'day_status',
                    'work_summary',
                    'hours_spent',
                    'notes',
                    'incidents',
                ]),
                'items_old_count' => $oldItemsCount,
                'items_new_count' => $dailyReport->items->count(),
                'items_old_total_qty' => $oldItemsTotalQty,
                'items_new_total_qty' => (float) $dailyReport->items->sum('quantity'),
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.daily-reports.show', [$work, $dailyReport])
            ->with('success', 'Registo diario atualizado com sucesso.');
    }

    public function destroy(Work $work, WorkDailyReport $dailyReport): RedirectResponse
    {
        $this->ensureReportRouteScope($work, $dailyReport);
        $this->authorize('delete', $dailyReport);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        $dailyReport->load('items');

        $payload = [
            'work_id' => $work->id,
            'work_code' => $work->code,
            'report_date' => optional($dailyReport->report_date)->format('Y-m-d'),
            'day_status' => $dailyReport->day_status,
            'hours_spent' => (float) $dailyReport->hours_spent,
            'items_count' => $dailyReport->items->count(),
            'items_total_qty' => (float) $dailyReport->items->sum('quantity'),
        ];

        $dailyReport->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'work_daily_report',
            entityId: $dailyReport->id,
            payload: $payload,
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.daily-reports.index', $work)
            ->with('success', 'Registo diario removido com sucesso.');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function syncItems(WorkDailyReport $report, array $rows, int $ownerId): void
    {
        $report->items()->delete();

        if (count($rows) === 0) {
            return;
        }

        $itemIds = collect($rows)
            ->pluck('item_id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $itemsById = Item::query()
            ->whereIn('id', $itemIds)
            ->with('unit:id,name')
            ->get()
            ->keyBy('id');

        foreach ($rows as $row) {
            $itemId = isset($row['item_id']) && $row['item_id'] !== null
                ? (int) $row['item_id']
                : null;

            $item = $itemId !== null ? $itemsById->get($itemId) : null;
            if ($itemId !== null && ! $item) {
                throw new RuntimeException('Artigo invalido na lista de materiais.');
            }

            $descriptionSnapshot = $item?->name ?: (string) ($row['description_snapshot'] ?? '');
            $descriptionSnapshot = trim($descriptionSnapshot);

            $unitSnapshot = $item?->unit?->name;
            if ($unitSnapshot === null || trim($unitSnapshot) === '') {
                $unitSnapshot = trim((string) ($row['unit_snapshot'] ?? '')) ?: null;
            }

            $report->items()->create([
                'owner_id' => $ownerId,
                'item_id' => $item?->id,
                'description_snapshot' => $descriptionSnapshot,
                'quantity' => (float) $row['quantity'],
                'unit_snapshot' => $unitSnapshot,
            ]);
        }
    }

    private function ensureWorkRouteScope(Work $work): void
    {
        abort_if((int) $work->owner_id !== (int) Auth::id(), 404);
    }

    private function ensureReportRouteScope(Work $work, WorkDailyReport $dailyReport): void
    {
        if ((int) $dailyReport->work_id !== (int) $work->id) {
            abort(404);
        }

        if ((int) $dailyReport->owner_id !== (int) $work->owner_id) {
            abort(404);
        }

        $this->ensureWorkRouteScope($work);
    }

    private function nonEditableResponse(Work $work): RedirectResponse
    {
        return redirect()
            ->route('works.daily-reports.index', $work)
            ->with('error', 'Obra concluida ou cancelada. Nao e permitido alterar registos operacionais.');
    }

    private function availableItemsForOwner(int $ownerId)
    {
        return Item::query()
            ->where('is_active', true)
            ->where(function ($query) use ($ownerId) {
                $query
                    ->where('owner_id', $ownerId)
                    ->orWhereNull('owner_id');
            })
            ->with('unit:id,name')
            ->orderBy('name')
            ->get(['id', 'owner_id', 'code', 'name', 'unit_id']);
    }
}
