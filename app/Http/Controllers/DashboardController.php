<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Work;
use App\Models\WorkExpense;
use App\Models\WorkMaterial;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

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
            $worksInProgress = Work::query()->where('status', Work::STATUS_IN_PROGRESS)->count();
            $worksCompleted = Work::query()->where('status', Work::STATUS_COMPLETED)->count();
            $worksSuspended = Work::query()->where('status', Work::STATUS_SUSPENDED)->count();

            $pendingTasks = WorkTask::query()
                ->whereIn('status', [WorkTask::STATUS_PLANNED, WorkTask::STATUS_IN_PROGRESS])
                ->count();

            $materialsCost = (float) WorkMaterial::query()->sum('total_cost');
            $laborCost = (float) WorkTaskAssignment::query()->sum('labor_cost_total');
            $otherCosts = (float) Work::query()->sum('other_costs') + (float) WorkExpense::query()->sum('total_cost');

            $plannedRevenueGlobal = (float) Work::query()
                ->join('budgets', 'budgets.id', '=', 'works.budget_id')
                ->sum('budgets.total');

            $estimatedMarginGlobal = $plannedRevenueGlobal - ($materialsCost + $laborCost + $otherCosts);
        }

        $lowStockCount = null;
        $recentStockMovementsCount = null;
        $recentStockMovements = collect();

        if ($canViewStock) {
            $lowStockCount = Item::query()
                ->where('is_active', true)
                ->where('tracks_stock', true)
                ->where('min_stock', '>', 0)
                ->whereColumn('current_stock', '<', 'min_stock')
                ->count();

            $recentStockMovementsCount = StockMovement::query()
                ->where('occurred_at', '>=', now()->subDays(7))
                ->count();

            $recentStockMovements = StockMovement::query()
                ->with([
                    'item:id,code,name',
                    'creator:id,name',
                    'workMaterial:id,work_id',
                    'workMaterial.work:id,code,name',
                ])
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->limit(10)
                ->get();
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
            'materialsCost' => $materialsCost,
            'laborCost' => $laborCost,
            'otherCosts' => $otherCosts,
            'plannedRevenueGlobal' => $plannedRevenueGlobal,
            'estimatedMarginGlobal' => $estimatedMarginGlobal,
        ]);
    }

    public function works(Request $request): View
    {
        $period = $this->resolvePeriod($request);
        $dateFrom = $period['date_from'];
        $dateTo = $period['date_to'];

        $works = Work::query()
            ->with([
                'customer:id,name',
                'budget:id,total,code',
                'technicalManager:id,name',
            ])
            ->withSum('materials as materials_cost_sum', 'total_cost')
            ->withSum('taskAssignments as labor_cost_sum', 'labor_cost_total')
            ->withSum('expenses as expenses_cost_sum', 'total_cost')
            ->get([
                'id',
                'code',
                'name',
                'status',
                'customer_id',
                'budget_id',
                'technical_manager_id',
                'other_costs',
                'end_date_actual',
                'updated_at',
            ]);

        $works = $this->appendWorkFinancials($works);

        $topWorksByCost = $works
            ->sortByDesc(fn (Work $work) => (float) $work->total_cost_dashboard)
            ->take(10)
            ->values();

        $topWorksByLowMargin = $works
            ->filter(fn (Work $work) => (float) $work->planned_revenue_dashboard > 0)
            ->sortBy(fn (Work $work) => (float) $work->gross_margin_dashboard)
            ->take(10)
            ->values();

        $worksWithoutTechnicalManager = $works
            ->filter(function (Work $work) {
                return $work->technical_manager_id === null
                    && in_array($work->status, [
                        Work::STATUS_PLANNED,
                        Work::STATUS_IN_PROGRESS,
                        Work::STATUS_SUSPENDED,
                    ], true);
            })
            ->sortBy('code')
            ->values();

        $worksWithPendingTasks = Work::query()
            ->with('customer:id,name')
            ->withCount([
                'tasks as pending_tasks_count' => function ($query) {
                    $query->whereIn('status', [WorkTask::STATUS_PLANNED, WorkTask::STATUS_IN_PROGRESS]);
                },
            ])
            ->having('pending_tasks_count', '>', 0)
            ->orderByDesc('pending_tasks_count')
            ->limit(10)
            ->get(['id', 'code', 'name', 'status', 'customer_id']);

        $completedWorksInPeriod = Work::query()
            ->with('customer:id,name')
            ->where('status', Work::STATUS_COMPLETED)
            ->where(function ($query) use ($dateFrom, $dateTo) {
                $query
                    ->whereBetween('end_date_actual', [$dateFrom->toDateString(), $dateTo->toDateString()])
                    ->orWhere(function ($subQuery) use ($dateFrom, $dateTo) {
                        $subQuery
                            ->whereNull('end_date_actual')
                            ->whereBetween('updated_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()]);
                    });
            })
            ->orderByDesc('end_date_actual')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'code', 'name', 'customer_id', 'end_date_actual', 'updated_at']);

        return view('dashboard.works', [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
            'topWorksByCost' => $topWorksByCost,
            'topWorksByLowMargin' => $topWorksByLowMargin,
            'worksWithoutTechnicalManager' => $worksWithoutTechnicalManager,
            'worksWithPendingTasks' => $worksWithPendingTasks,
            'completedWorksInPeriod' => $completedWorksInPeriod,
            'completedWorksInPeriodCount' => $completedWorksInPeriod->count(),
        ]);
    }

    public function stock(Request $request): View
    {
        $period = $this->resolvePeriod($request);
        $dateFrom = $period['date_from'];
        $dateTo = $period['date_to'];

        $lowStockItems = Item::query()
            ->where('is_active', true)
            ->where('tracks_stock', true)
            ->where('min_stock', '>', 0)
            ->whereColumn('current_stock', '<', 'min_stock')
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

        $latestMovements = StockMovement::query()
            ->with([
                'item:id,code,name',
                'creator:id,name',
                'workMaterial:id,work_id',
                'workMaterial.work:id,code,name',
            ])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $periodMovements = StockMovement::query()
            ->whereBetween('occurred_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()]);

        $entriesQty = (float) (clone $periodMovements)
            ->where('direction', StockMovement::DIRECTION_IN)
            ->sum('quantity');

        $exitsQty = (float) (clone $periodMovements)
            ->where('direction', StockMovement::DIRECTION_OUT)
            ->sum('quantity');

        $adjustmentsQty = (float) (clone $periodMovements)
            ->where('direction', StockMovement::DIRECTION_ADJUSTMENT)
            ->sum('quantity');

        $manualRecentMovements = StockMovement::query()
            ->with(['item:id,code,name', 'creator:id,name'])
            ->where(function ($query) {
                $query
                    ->where('source_type', 'manual')
                    ->orWhereIn('movement_type', [
                        StockMovement::TYPE_MANUAL_ENTRY,
                        StockMovement::TYPE_MANUAL_EXIT,
                        StockMovement::TYPE_MANUAL_ADJUSTMENT,
                    ]);
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('dashboard.stock', [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
            'lowStockItems' => $lowStockItems,
            'outOfStockItems' => $outOfStockItems,
            'latestMovements' => $latestMovements,
            'entriesQty' => $entriesQty,
            'exitsQty' => $exitsQty,
            'adjustmentsQty' => $adjustmentsQty,
            'manualRecentMovements' => $manualRecentMovements,
        ]);
    }

    /**
     * @param Collection<int, Work> $works
     * @return Collection<int, Work>
     */
    private function appendWorkFinancials(Collection $works): Collection
    {
        return $works->map(function (Work $work) {
            $materials = (float) ($work->materials_cost_sum ?? 0);
            $labor = (float) ($work->labor_cost_sum ?? 0);
            $expenses = (float) ($work->expenses_cost_sum ?? 0);
            $manualOther = (float) ($work->other_costs ?? 0);
            $totalCost = $materials + $labor + $expenses + $manualOther;
            $plannedRevenue = (float) ($work->budget?->total ?? 0);
            $grossMargin = $plannedRevenue - $totalCost;
            $grossMarginPercent = $plannedRevenue > 0 ? ($grossMargin / $plannedRevenue) * 100 : null;

            $work->setAttribute('materials_cost_dashboard', $materials);
            $work->setAttribute('labor_cost_dashboard', $labor);
            $work->setAttribute('expenses_cost_dashboard', $expenses);
            $work->setAttribute('manual_other_cost_dashboard', $manualOther);
            $work->setAttribute('total_cost_dashboard', $totalCost);
            $work->setAttribute('planned_revenue_dashboard', $plannedRevenue);
            $work->setAttribute('gross_margin_dashboard', $grossMargin);
            $work->setAttribute('gross_margin_percent_dashboard', $grossMarginPercent);

            return $work;
        });
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
}

