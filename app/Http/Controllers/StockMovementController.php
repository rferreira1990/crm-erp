<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\IndexStockMovementRequest;
use App\Http\Requests\Stock\StoreManualStockMovementRequest;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class StockMovementController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index(IndexStockMovementRequest $request): View
    {
        $filters = $request->filters();

        $query = StockMovement::query()
            ->with([
                'item:id,code,name',
                'creator:id,name',
                'workMaterial:id,work_id,item_id,description_snapshot',
                'workMaterial.work:id,code,name',
            ])
            ->when($filters['search'], function ($subQuery, $search) {
                $subQuery->whereHas('item', function ($itemQuery) use ($search) {
                    $itemQuery
                        ->where('code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['movement_type'], function ($subQuery, $movementType) {
                $subQuery->where('movement_type', $movementType);
            })
            ->when($filters['direction'], function ($subQuery, $direction) {
                $subQuery->where('direction', $direction);
            })
            ->when($filters['date_from'], function ($subQuery, $dateFrom) {
                $subQuery->whereDate('occurred_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'], function ($subQuery, $dateTo) {
                $subQuery->whereDate('occurred_at', '<=', $dateTo);
            })
            ->when($filters['user_id'], function ($subQuery, $userId) {
                $subQuery->where('created_by', $userId);
            })
            ->when($filters['only_works'], function ($subQuery) {
                $subQuery->where(function ($workQuery) {
                    $workQuery
                        ->whereNotNull('work_material_id')
                        ->orWhere('source_type', StockMovement::TYPE_WORK_MATERIAL);
                });
            });

        $movements = $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $movementTypes = StockMovement::query()
            ->select('movement_type')
            ->distinct()
            ->orderBy('movement_type')
            ->pluck('movement_type')
            ->all();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $items = Item::query()
            ->where('is_active', true)
            ->where('tracks_stock', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'current_stock', 'tracks_stock']);

        return view('stock.index', [
            'movements' => $movements,
            'movementTypes' => $movementTypes,
            'users' => $users,
            'items' => $items,
            'filters' => $filters,
        ]);
    }

    public function storeManual(StoreManualStockMovementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $movement = DB::transaction(function () use ($validated) {
                $item = Item::query()->lockForUpdate()->findOrFail((int) $validated['item_id']);
                if (! $item->tracks_stock) {
                    throw new RuntimeException('O artigo selecionado nao controla stock.');
                }

                $quantity = round((float) $validated['quantity'], 3);
                $direction = (string) $validated['direction'];

                $delta = match ($direction) {
                    'in' => abs($quantity),
                    'out' => -abs($quantity),
                    default => $quantity,
                };

                $stockBefore = round((float) $item->current_stock, 3);
                $stockAfter = round($stockBefore + $delta, 3);

                if ($stockAfter < 0) {
                    throw new RuntimeException('Stock insuficiente para realizar este movimento.');
                }

                $item->update([
                    'current_stock' => $stockAfter,
                ]);

                return StockMovement::query()->create([
                    'item_id' => $item->id,
                    'movement_type' => $validated['movement_type'],
                    'direction' => $direction,
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'occurred_at' => $validated['occurred_at'] ?? now(),
                    'source_type' => 'manual',
                    'source_id' => null,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            });
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('stock.index')
                ->with('error', $exception->getMessage())
                ->withInput();
        }

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'stock_movement',
            entityId: $movement->id,
            payload: [
                'item_id' => $movement->item_id,
                'movement_type' => $movement->movement_type,
                'direction' => $movement->direction,
                'quantity' => $movement->quantity,
                'stock_before' => $movement->stock_before,
                'stock_after' => $movement->stock_after,
                'source_type' => $movement->source_type,
                'source_id' => $movement->source_id,
            ],
            ownerId: auth()->id(),
            userId: auth()->id(),
        );

        return redirect()
            ->route('stock.index')
            ->with('success', 'Movimento de stock manual registado com sucesso.');
    }
}
