<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\CustomerAccountEntry;
use App\Models\SupplierAccountEntry;
use App\Models\Work;
use App\Models\WorkExpense;
use App\Models\WorkMaterial;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $ownerId = (int) ($user?->id ?? 0);

        $canViewWorks = (bool) ($user?->can('works.view') ?? false);
        $canViewStock = (bool) ($user?->can('stock.view') ?? false);

        $worksInProgress = null;
        $worksCompleted = null;
        $worksSuspended = null;
        $pendingTasks = null;
        $materialsCost = null;
        $laborCost = null;
        $otherCosts = null;
        $estimatedMarginGlobal = null;
        $plannedRevenueGlobal = null;

        if ($canViewWorks) {
            $worksMetrics = Cache::remember(
                'dashboard:index:works:v2:owner:' . $ownerId,
                now()->addMinutes(2),
                fn () => $this->buildMainWorksMetrics($ownerId)
            );

            $worksInProgress = $worksMetrics['worksInProgress'];
            $worksCompleted = $worksMetrics['worksCompleted'];
            $worksSuspended = $worksMetrics['worksSuspended'];
            $pendingTasks = $worksMetrics['pendingTasks'];
            $materialsCost = $worksMetrics['materialsCost'];
            $laborCost = $worksMetrics['laborCost'];
            $otherCosts = $worksMetrics['otherCosts'];
            $plannedRevenueGlobal = $worksMetrics['plannedRevenueGlobal'];
            $estimatedMarginGlobal = $worksMetrics['estimatedMarginGlobal'];
        }

        $lowStockCount = null;
        $recentStockMovementsCount = null;
        $recentStockMovements = collect();
        $canViewFinancial = (bool) (
            ($user?->can('customers.view') ?? false)
            || ($user?->can('suppliers.view') ?? false)
        );
        $financialFilters = [
            'financial_date_from' => '',
            'financial_date_to' => '',
        ];
        $financialMetrics = null;

        if ($canViewStock) {
            $stockMetrics = Cache::remember(
                'dashboard:index:stock:v2:owner:' . $ownerId,
                now()->addMinutes(1),
                fn () => $this->buildMainStockMetrics($ownerId)
            );

            $lowStockCount = $stockMetrics['lowStockCount'];
            $recentStockMovementsCount = $stockMetrics['recentStockMovementsCount'];
            $recentStockMovements = $stockMetrics['recentStockMovements'];
        }

        if ($canViewFinancial && $user) {
            $financialPeriod = $this->resolveFinancialPeriod($request);
            $financialFilters = [
                'financial_date_from' => $financialPeriod['date_from']->toDateString(),
                'financial_date_to' => $financialPeriod['date_to']->toDateString(),
            ];

            $financialMetrics = $this->buildFinancialOperationalMetrics(
                ownerId: (int) $user->id,
                dateFrom: $financialPeriod['date_from']->copy(),
                dateTo: $financialPeriod['date_to']->copy(),
            );
        }

        return view('dashboard.index', [
            'canViewWorks' => $canViewWorks,
            'canViewStock' => $canViewStock,
            'worksInProgress' => $worksInProgress,
            'worksCompleted' => $worksCompleted,
            'worksSuspended' => $worksSuspended,
            'pendingTasks' => $pendingTasks,
            'lowStockCount' => $lowStockCount,
            'recentStockMovementsCount' => $recentStockMovementsCount,
            'recentStockMovements' => $recentStockMovements,
            'canViewFinancial' => $canViewFinancial,
            'financialFilters' => $financialFilters,
            'financialMetrics' => $financialMetrics,
            'materialsCost' => $materialsCost,
            'laborCost' => $laborCost,
            'otherCosts' => $otherCosts,
            'plannedRevenueGlobal' => $plannedRevenueGlobal,
            'estimatedMarginGlobal' => $estimatedMarginGlobal,
        ]);
    }

    public function works(Request $request): View
    {
        $ownerId = (int) ($request->user()?->id ?? 0);
        $period = $this->resolvePeriod($request);
        $dateFrom = $period['date_from'];
        $dateTo = $period['date_to'];

        $cacheKey = sprintf(
            'dashboard:works:v2:%d:%s:%s',
            $ownerId,
            $dateFrom->toDateString(),
            $dateTo->toDateString()
        );

        $dashboardData = Cache::remember(
            $cacheKey,
            now()->addMinutes(3),
            fn () => $this->buildWorksDashboardData($dateFrom->copy(), $dateTo->copy(), $ownerId)
        );

        return view('dashboard.works', [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
            'topWorksByCost' => $dashboardData['topWorksByCost'],
            'topWorksByLowMargin' => $dashboardData['topWorksByLowMargin'],
            'worksWithoutTechnicalManager' => $dashboardData['worksWithoutTechnicalManager'],
            'worksWithoutTechnicalManagerCount' => $dashboardData['worksWithoutTechnicalManagerCount'],
            'worksWithPendingTasks' => $dashboardData['worksWithPendingTasks'],
            'worksWithPendingTasksCount' => $dashboardData['worksWithPendingTasksCount'],
            'completedWorksInPeriod' => $dashboardData['completedWorksInPeriod'],
            'completedWorksInPeriodCount' => $dashboardData['completedWorksInPeriodCount'],
        ]);
    }

    public function tasksPrintView(Request $request): View
    {
        $this->authorize('viewAny', Work::class);

        $validated = $request->validate([
            'task_date' => ['required', 'date'],
        ], [], [
            'task_date' => 'data',
        ]);

        $ownerId = (int) ($request->user()?->id ?? 0);
        $selectedDate = Carbon::parse((string) $validated['task_date'])->startOfDay();

        $tasks = WorkTask::query()
            ->with([
                'work:id,owner_id,customer_id,code,name',
                'work.customer:id,code,name',
                'assignedUser:id,name',
                'assignments:id,work_task_id,user_id',
                'assignments.user:id,name',
            ])
            ->whereDate('planned_date', $selectedDate->toDateString())
            ->whereHas('work', function (Builder $query) use ($ownerId): void {
                $query->where('owner_id', $ownerId);
            })
            ->orderByRaw('CASE WHEN planned_start_time IS NULL THEN 1 ELSE 0 END')
            ->orderBy('planned_start_time')
            ->orderBy('planned_end_time')
            ->orderBy('id')
            ->get();

        return view('dashboard.tasks-print', [
            'tasks' => $tasks,
            'selectedDate' => $selectedDate,
            'statusLabels' => WorkTask::statuses(),
            'generatedAt' => now(),
        ]);
    }

    public function stock(Request $request): View
    {
        $ownerId = (int) ($request->user()?->id ?? 0);
        $period = $this->resolvePeriod($request);
        $dateFrom = $period['date_from'];
        $dateTo = $period['date_to'];

        $cacheKey = sprintf(
            'dashboard:stock:v2:%d:%s:%s',
            $ownerId,
            $dateFrom->toDateString(),
            $dateTo->toDateString()
        );

        $dashboardData = Cache::remember(
            $cacheKey,
            now()->addMinutes(2),
            fn () => $this->buildStockDashboardData($dateFrom->copy(), $dateTo->copy(), $ownerId)
        );

        return view('dashboard.stock', [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
            'lowStockItems' => $dashboardData['lowStockItems'],
            'outOfStockItems' => $dashboardData['outOfStockItems'],
            'latestMovements' => $dashboardData['latestMovements'],
            'periodMovementsCount' => $dashboardData['periodMovementsCount'],
            'entriesQty' => $dashboardData['entriesQty'],
            'exitsQty' => $dashboardData['exitsQty'],
            'adjustmentsQty' => $dashboardData['adjustmentsQty'],
            'manualMovementsInPeriodCount' => $dashboardData['manualMovementsInPeriodCount'],
            'manualRecentMovements' => $dashboardData['manualRecentMovements'],
        ]);
    }

    /**
     * @return array{
     *     worksInProgress:int,
     *     worksCompleted:int,
     *     worksSuspended:int,
     *     pendingTasks:int,
     *     materialsCost:float,
     *     laborCost:float,
     *     otherCosts:float,
     *     plannedRevenueGlobal:float,
     *     estimatedMarginGlobal:float
     * }
     */
    private function buildMainWorksMetrics(int $ownerId): array
    {
        $statusSummary = Work::query()
            ->where('owner_id', $ownerId)
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS works_in_progress,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS works_completed,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS works_suspended',
                [
                    Work::STATUS_IN_PROGRESS,
                    Work::STATUS_COMPLETED,
                    Work::STATUS_SUSPENDED,
                ]
            )
            ->first();

        $pendingTasks = (int) WorkTask::query()
            ->whereHas('work', function (Builder $query) use ($ownerId) {
                $query->where('owner_id', $ownerId);
            })
            ->whereIn('status', [WorkTask::STATUS_PLANNED, WorkTask::STATUS_IN_PROGRESS])
            ->count();

        $materialsCost = (float) WorkMaterial::query()
            ->where('owner_id', $ownerId)
            ->sum('total_cost');

        $laborCost = (float) WorkTaskAssignment::query()
            ->join('work_tasks', 'work_tasks.id', '=', 'work_task_assignments.work_task_id')
            ->join('works', 'works.id', '=', 'work_tasks.work_id')
            ->where('works.owner_id', $ownerId)
            ->sum('work_task_assignments.labor_cost_total');

        $manualOtherCosts = (float) Work::query()
            ->where('owner_id', $ownerId)
            ->sum('other_costs');

        $expensesCost = (float) WorkExpense::query()
            ->where('owner_id', $ownerId)
            ->sum('total_cost');
        $otherCosts = $manualOtherCosts + $expensesCost;

        $plannedRevenueGlobal = (float) Work::query()
            ->where('works.owner_id', $ownerId)
            ->leftJoin('budgets', 'budgets.id', '=', 'works.budget_id')
            ->sum('budgets.total');

        return [
            'worksInProgress' => (int) ($statusSummary->works_in_progress ?? 0),
            'worksCompleted' => (int) ($statusSummary->works_completed ?? 0),
            'worksSuspended' => (int) ($statusSummary->works_suspended ?? 0),
            'pendingTasks' => $pendingTasks,
            'materialsCost' => $materialsCost,
            'laborCost' => $laborCost,
            'otherCosts' => $otherCosts,
            'plannedRevenueGlobal' => $plannedRevenueGlobal,
            'estimatedMarginGlobal' => $plannedRevenueGlobal - ($materialsCost + $laborCost + $otherCosts),
        ];
    }

    /**
     * @return array{
     *   total_receivable: float,
     *   overdue_receivable: float,
     *   period_receipts: float,
     *   total_payable: float,
     *   overdue_payable: float,
     *   period_payments: float,
     *   net_operational_balance: float,
     *   receivable_aging: array{0_30: float,31_60: float,61_90: float,90_plus: float},
     *   payable_aging: array{0_30: float,31_60: float,61_90: float,90_plus: float}
     * }
     */
    private function buildFinancialOperationalMetrics(int $ownerId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $today = Carbon::today();

        $customerSignedExpression = "
            CASE
                WHEN type IN ('debit', 'adjustment') THEN amount
                ELSE amount * -1
            END
        ";

        $supplierSignedExpression = "
            CASE
                WHEN type IN ('debit', 'purchase_invoice', 'adjustment') THEN amount
                ELSE amount * -1
            END
        ";

        $totalReceivableRaw = (float) CustomerAccountEntry::query()
            ->where('owner_id', $ownerId)
            ->selectRaw("COALESCE(SUM({$customerSignedExpression}), 0) AS total")
            ->value('total');

        $overdueReceivableRaw = (float) CustomerAccountEntry::query()
            ->where('owner_id', $ownerId)
            ->whereDate('due_date', '<', $today->toDateString())
            ->selectRaw("COALESCE(SUM({$customerSignedExpression}), 0) AS total")
            ->value('total');

        $periodReceipts = (float) CustomerAccountEntry::query()
            ->where('owner_id', $ownerId)
            ->where('type', CustomerAccountEntry::TYPE_PAYMENT)
            ->whereBetween('entry_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->sum('amount');

        $totalPayableRaw = (float) SupplierAccountEntry::query()
            ->where('owner_id', $ownerId)
            ->selectRaw("COALESCE(SUM({$supplierSignedExpression}), 0) AS total")
            ->value('total');

        $overduePayableRaw = (float) SupplierAccountEntry::query()
            ->where('owner_id', $ownerId)
            ->whereDate('due_date', '<', $today->toDateString())
            ->selectRaw("COALESCE(SUM({$supplierSignedExpression}), 0) AS total")
            ->value('total');

        $periodPayments = (float) SupplierAccountEntry::query()
            ->where('owner_id', $ownerId)
            ->where('type', SupplierAccountEntry::TYPE_PAYMENT)
            ->whereBetween('entry_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->sum('amount');

        $receivableAging = $this->buildAgingBuckets(
            baseQuery: CustomerAccountEntry::query()
                ->where('owner_id', $ownerId)
                ->whereIn('type', [CustomerAccountEntry::TYPE_DEBIT, CustomerAccountEntry::TYPE_ADJUSTMENT]),
            today: $today->copy(),
        );

        $payableAging = $this->buildAgingBuckets(
            baseQuery: SupplierAccountEntry::query()
                ->where('owner_id', $ownerId)
                ->whereIn('type', [
                    SupplierAccountEntry::TYPE_DEBIT,
                    SupplierAccountEntry::TYPE_PURCHASE_INVOICE,
                    SupplierAccountEntry::TYPE_ADJUSTMENT,
                ]),
            today: $today->copy(),
        );

        $totalReceivable = max(0, round($totalReceivableRaw, 2));
        $totalPayable = max(0, round($totalPayableRaw, 2));
        $overdueReceivable = max(0, round($overdueReceivableRaw, 2));
        $overduePayable = max(0, round($overduePayableRaw, 2));

        return [
            'total_receivable' => $totalReceivable,
            'overdue_receivable' => $overdueReceivable,
            'period_receipts' => round($periodReceipts, 2),
            'total_payable' => $totalPayable,
            'overdue_payable' => $overduePayable,
            'period_payments' => round($periodPayments, 2),
            'net_operational_balance' => round($totalReceivable - $totalPayable, 2),
            'receivable_aging' => $receivableAging,
            'payable_aging' => $payableAging,
        ];
    }

    /**
     * @return array{0_30: float,31_60: float,61_90: float,90_plus: float}
     */
    private function buildAgingBuckets(Builder $baseQuery, Carbon $today): array
    {
        $day30 = $today->copy()->subDays(30)->toDateString();
        $day31 = $today->copy()->subDays(31)->toDateString();
        $day60 = $today->copy()->subDays(60)->toDateString();
        $day61 = $today->copy()->subDays(61)->toDateString();
        $day90 = $today->copy()->subDays(90)->toDateString();

        $bucket0to30 = (float) (clone $baseQuery)
            ->whereDate('due_date', '<=', $today->toDateString())
            ->whereDate('due_date', '>=', $day30)
            ->sum('amount');

        $bucket31to60 = (float) (clone $baseQuery)
            ->whereDate('due_date', '<=', $day31)
            ->whereDate('due_date', '>=', $day60)
            ->sum('amount');

        $bucket61to90 = (float) (clone $baseQuery)
            ->whereDate('due_date', '<=', $day61)
            ->whereDate('due_date', '>=', $day90)
            ->sum('amount');

        $bucket90Plus = (float) (clone $baseQuery)
            ->whereDate('due_date', '<', $day90)
            ->sum('amount');

        return [
            '0_30' => round($bucket0to30, 2),
            '31_60' => round($bucket31to60, 2),
            '61_90' => round($bucket61to90, 2),
            '90_plus' => round($bucket90Plus, 2),
        ];
    }

    /**
     * @return array{
     *     lowStockCount:int,
     *     recentStockMovementsCount:int,
     *     recentStockMovements:\Illuminate\Support\Collection<int, StockMovement>
     * }
     */
    private function buildMainStockMetrics(int $ownerId): array
    {
        $lowStockCount = (int) $this->lowStockItemsQuery($ownerId)->count();

        $recentStockMovementsCount = (int) StockMovement::query()
            ->whereHas('item', function (Builder $query) use ($ownerId) {
                $query->where('owner_id', $ownerId);
            })
            ->where('occurred_at', '>=', now()->subDays(7))
            ->count();

        $recentStockMovements = $this->recentStockMovementsQuery($ownerId)
            ->limit(10)
            ->get();

        return [
            'lowStockCount' => $lowStockCount,
            'recentStockMovementsCount' => $recentStockMovementsCount,
            'recentStockMovements' => $recentStockMovements,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWorksDashboardData(Carbon $dateFrom, Carbon $dateTo, int $ownerId): array
    {
        $financialBaseQuery = $this->workFinancialQuery($ownerId);

        $topWorksByCost = (clone $financialBaseQuery)
            ->orderByDesc('total_cost_dashboard')
            ->orderBy('works.id')
            ->limit(10)
            ->get();

        $topWorksByLowMargin = (clone $financialBaseQuery)
            ->whereRaw('COALESCE(budgets.total, 0) > 0')
            ->orderBy('gross_margin_dashboard')
            ->orderBy('works.id')
            ->limit(10)
            ->get();

        $worksWithoutTechnicalManager = Work::query()
            ->where('owner_id', $ownerId)
            ->whereNull('technical_manager_id')
            ->whereIn('status', [
                Work::STATUS_PLANNED,
                Work::STATUS_IN_PROGRESS,
                Work::STATUS_SUSPENDED,
            ])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'status']);

        $worksWithPendingTasksBaseQuery = Work::query()
            ->where('owner_id', $ownerId)
            ->whereHas('tasks', function (Builder $query) {
                $query->whereIn('status', [WorkTask::STATUS_PLANNED, WorkTask::STATUS_IN_PROGRESS]);
            });

        $worksWithPendingTasksCount = (clone $worksWithPendingTasksBaseQuery)->count();

        $worksWithPendingTasks = Work::query()
            ->where('owner_id', $ownerId)
            ->withCount([
                'tasks as pending_tasks_count' => function (Builder $query) {
                    $query->whereIn('status', [WorkTask::STATUS_PLANNED, WorkTask::STATUS_IN_PROGRESS]);
                },
            ])
            ->having('pending_tasks_count', '>', 0)
            ->orderByDesc('pending_tasks_count')
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'code', 'name', 'status']);

        $completedWorksBaseQuery = Work::query()
            ->where('owner_id', $ownerId)
            ->with('customer:id,name')
            ->where('status', Work::STATUS_COMPLETED)
            ->where(function (Builder $query) use ($dateFrom, $dateTo) {
                $query
                    ->whereBetween('end_date_actual', [$dateFrom->toDateString(), $dateTo->toDateString()])
                    ->orWhere(function (Builder $subQuery) use ($dateFrom, $dateTo) {
                        $subQuery
                            ->whereNull('end_date_actual')
                            ->whereBetween('updated_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()]);
                    });
            });

        $completedWorksInPeriodCount = (clone $completedWorksBaseQuery)->count();

        $completedWorksInPeriod = (clone $completedWorksBaseQuery)
            ->orderByDesc('end_date_actual')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'code', 'name', 'customer_id', 'end_date_actual', 'updated_at']);

        return [
            'topWorksByCost' => $topWorksByCost,
            'topWorksByLowMargin' => $topWorksByLowMargin,
            'worksWithoutTechnicalManager' => $worksWithoutTechnicalManager,
            'worksWithoutTechnicalManagerCount' => $worksWithoutTechnicalManager->count(),
            'worksWithPendingTasks' => $worksWithPendingTasks,
            'worksWithPendingTasksCount' => $worksWithPendingTasksCount,
            'completedWorksInPeriod' => $completedWorksInPeriod,
            'completedWorksInPeriodCount' => $completedWorksInPeriodCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStockDashboardData(Carbon $dateFrom, Carbon $dateTo): array
    {
        $lowStockItems = $this->lowStockItemsQuery()
            ->orderByRaw('(min_stock - current_stock) DESC')
            ->limit(25)
            ->get(['id', 'code', 'name', 'current_stock', 'min_stock']);

        $outOfStockItems = Item::query()
            ->where('is_active', true)
            ->where('tracks_stock', true)
            ->where('current_stock', '<=', 0)
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'code', 'name', 'current_stock']);

        $latestMovements = $this->recentStockMovementsQuery()
            ->limit(20)
            ->get();

        $periodStart = $dateFrom->copy()->startOfDay();
        $periodEnd = $dateTo->copy()->endOfDay();

        $manualMovementTypes = $this->manualMovementTypes();
        $manualMovementPlaceholders = implode(', ', array_fill(0, count($manualMovementTypes), '?'));

        $periodSummary = StockMovement::query()
            ->whereBetween('occurred_at', [$periodStart, $periodEnd])
            ->selectRaw(
                'COUNT(*) AS period_movements_count,
                 COALESCE(SUM(CASE WHEN direction = ? THEN quantity ELSE 0 END), 0) AS entries_qty,
                 COALESCE(SUM(CASE WHEN direction = ? THEN quantity ELSE 0 END), 0) AS exits_qty,
                 COALESCE(SUM(CASE WHEN direction = ? THEN quantity ELSE 0 END), 0) AS adjustments_qty,
                 SUM(CASE WHEN source_type = ? OR movement_type IN (' . $manualMovementPlaceholders . ') THEN 1 ELSE 0 END) AS manual_movements_count',
                array_merge(
                    [
                        StockMovement::DIRECTION_IN,
                        StockMovement::DIRECTION_OUT,
                        StockMovement::DIRECTION_ADJUSTMENT,
                        'manual',
                    ],
                    $manualMovementTypes
                )
            )
            ->first();

        $manualRecentMovements = $this->applyManualMovementFilter(
            StockMovement::query()->with(['item:id,code,name', 'creator:id,name'])
        )
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return [
            'lowStockItems' => $lowStockItems,
            'outOfStockItems' => $outOfStockItems,
            'latestMovements' => $latestMovements,
            'periodMovementsCount' => (int) ($periodSummary->period_movements_count ?? 0),
            'entriesQty' => (float) ($periodSummary->entries_qty ?? 0),
            'exitsQty' => (float) ($periodSummary->exits_qty ?? 0),
            'adjustmentsQty' => (float) ($periodSummary->adjustments_qty ?? 0),
            'manualMovementsInPeriodCount' => (int) ($periodSummary->manual_movements_count ?? 0),
            'manualRecentMovements' => $manualRecentMovements,
        ];
    }

    private function lowStockItemsQuery(): Builder
    {
        return Item::query()
            ->where('is_active', true)
            ->where('tracks_stock', true)
            ->where('min_stock', '>', 0)
            ->whereColumn('current_stock', '<', 'min_stock');
    }

    private function recentStockMovementsQuery(): Builder
    {
        return StockMovement::query()
            ->with([
                'item:id,code,name',
                'creator:id,name',
                'workMaterial:id,work_id',
                'workMaterial.work:id,code,name',
            ])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    private function applyManualMovementFilter(Builder $query): Builder
    {
        return $query->where(function (Builder $subQuery) {
            $subQuery
                ->where('source_type', 'manual')
                ->orWhereIn('movement_type', $this->manualMovementTypes());
        });
    }

    /**
     * @return array<int, string>
     */
    private function manualMovementTypes(): array
    {
        return [
            StockMovement::TYPE_MANUAL_ENTRY,
            StockMovement::TYPE_MANUAL_EXIT,
            StockMovement::TYPE_MANUAL_ADJUSTMENT,
        ];
    }

    private function workFinancialQuery(): Builder
    {
        $materialsTotals = DB::table('work_materials')
            ->selectRaw('work_id, COALESCE(SUM(total_cost), 0) AS materials_cost')
            ->groupBy('work_id');

        $laborTotals = DB::table('work_task_assignments')
            ->join('work_tasks', 'work_tasks.id', '=', 'work_task_assignments.work_task_id')
            ->selectRaw('work_tasks.work_id, COALESCE(SUM(work_task_assignments.labor_cost_total), 0) AS labor_cost')
            ->groupBy('work_tasks.work_id');

        $expenseTotals = DB::table('work_expenses')
            ->selectRaw('work_id, COALESCE(SUM(total_cost), 0) AS expenses_cost')
            ->groupBy('work_id');

        $totalCostExpression = 'COALESCE(wm.materials_cost, 0) + COALESCE(wta.labor_cost, 0) + COALESCE(we.expenses_cost, 0) + COALESCE(works.other_costs, 0)';
        $grossMarginExpression = 'COALESCE(budgets.total, 0) - (' . $totalCostExpression . ')';

        return Work::query()
            ->leftJoinSub($materialsTotals, 'wm', function ($join) {
                $join->on('wm.work_id', '=', 'works.id');
            })
            ->leftJoinSub($laborTotals, 'wta', function ($join) {
                $join->on('wta.work_id', '=', 'works.id');
            })
            ->leftJoinSub($expenseTotals, 'we', function ($join) {
                $join->on('we.work_id', '=', 'works.id');
            })
            ->leftJoin('budgets', 'budgets.id', '=', 'works.budget_id')
            ->select([
                'works.id',
                'works.code',
                'works.name',
                'works.status',
                'works.customer_id',
                'works.budget_id',
                'works.technical_manager_id',
                'works.other_costs',
                'works.end_date_actual',
                'works.updated_at',
            ])
            ->selectRaw('COALESCE(wm.materials_cost, 0) AS materials_cost_dashboard')
            ->selectRaw('COALESCE(wta.labor_cost, 0) AS labor_cost_dashboard')
            ->selectRaw('COALESCE(we.expenses_cost, 0) AS expenses_cost_dashboard')
            ->selectRaw('COALESCE(works.other_costs, 0) AS manual_other_cost_dashboard')
            ->selectRaw($totalCostExpression . ' AS total_cost_dashboard')
            ->selectRaw('COALESCE(budgets.total, 0) AS planned_revenue_dashboard')
            ->selectRaw($grossMarginExpression . ' AS gross_margin_dashboard')
            ->selectRaw(
                'CASE
                    WHEN COALESCE(budgets.total, 0) > 0
                    THEN (' . $grossMarginExpression . ' / COALESCE(budgets.total, 0)) * 100
                    ELSE NULL
                END AS gross_margin_percent_dashboard'
            );
    }

    /**
     * @return array{date_from: Carbon, date_to: Carbon}
     */
    private function resolvePeriod(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = isset($validated['date_from'])
            ? Carbon::parse((string) $validated['date_from'])
            : now()->copy()->subDays(30);

        $dateTo = isset($validated['date_to'])
            ? Carbon::parse((string) $validated['date_to'])
            : now();

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /**
     * @return array{date_from: Carbon, date_to: Carbon}
     */
    private function resolveFinancialPeriod(Request $request): array
    {
        $validated = $request->validate([
            'financial_date_from' => ['nullable', 'date'],
            'financial_date_to' => ['nullable', 'date', 'after_or_equal:financial_date_from'],
        ]);

        $dateFrom = isset($validated['financial_date_from'])
            ? Carbon::parse((string) $validated['financial_date_from'])
            : now()->copy()->startOfMonth();

        $dateTo = isset($validated['financial_date_to'])
            ? Carbon::parse((string) $validated['financial_date_to'])
            : now();

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
