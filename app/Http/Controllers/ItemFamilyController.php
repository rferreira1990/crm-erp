<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemFamilyRequest;
use App\Http\Requests\UpdateItemFamilyRequest;
use App\Models\ItemFamily;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ItemFamilyController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', ItemFamily::class);

        $baseFamilies = ItemFamily::query()
            ->withCount(['items', 'children'])
            ->orderBy('name')
            ->get();

        $itemFamilies = ItemFamily::flattenedHierarchy($baseFamilies);

        return view('item-families.index', [
            'itemFamilies' => $itemFamilies,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ItemFamily::class);

        return view('item-families.create', [
            'item_family' => new ItemFamily(),
            'parentOptions' => ItemFamily::flattenedHierarchy(
                ItemFamily::query()->orderBy('name')->get()
            ),
        ]);
    }

    public function store(StoreItemFamilyRequest $request): RedirectResponse
    {
        $this->authorize('create', ItemFamily::class);

        $itemFamily = ItemFamily::create($request->validatedData());

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'item_family',
            entityId: $itemFamily->id,
            payload: [
                'name' => $itemFamily->name,
                'parent_id' => $itemFamily->parent_id,
                'description' => $itemFamily->description,
                'is_active' => $itemFamily->is_active,
            ],
            ownerId: $itemFamily->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('item-families.index')
            ->with('success', 'Familia criada com sucesso.');
    }

    public function edit(ItemFamily $item_family): View
    {
        $this->authorize('update', $item_family);

        $excludedIds = array_merge([$item_family->id], $item_family->descendantIds());

        return view('item-families.edit', [
            'item_family' => $item_family,
            'parentOptions' => ItemFamily::flattenedHierarchy(
                ItemFamily::query()->orderBy('name')->get(),
                $excludedIds
            ),
        ]);
    }

    public function update(UpdateItemFamilyRequest $request, ItemFamily $item_family): RedirectResponse
    {
        $this->authorize('update', $item_family);

        $oldData = $item_family->only([
            'name',
            'parent_id',
            'description',
            'is_active',
        ]);

        $item_family->update($request->validatedData());

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'item_family',
            entityId: $item_family->id,
            payload: [
                'old' => $oldData,
                'new' => $item_family->only(array_keys($oldData)),
            ],
            ownerId: $item_family->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('item-families.index')
            ->with('success', 'Familia atualizada com sucesso.');
    }

    public function destroy(ItemFamily $item_family): RedirectResponse
    {
        $this->authorize('delete', $item_family);

        if ($item_family->children()->exists()) {
            return redirect()
                ->route('item-families.index')
                ->with('error', 'Nao e possivel apagar esta familia porque tem subfamilias associadas.');
        }

        if ($item_family->items()->exists()) {
            return redirect()
                ->route('item-families.index')
                ->with('error', 'Nao e possivel apagar esta familia porque existem artigos associados.');
        }

        $payload = $item_family->only([
            'name',
            'parent_id',
            'description',
            'is_active',
        ]);

        $item_family->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'item_family',
            entityId: $item_family->id,
            payload: $payload,
            ownerId: $item_family->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('item-families.index')
            ->with('success', 'Familia apagada com sucesso.');
    }
}