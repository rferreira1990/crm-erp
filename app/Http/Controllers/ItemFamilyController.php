<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemFamilyRequest;
use App\Http\Requests\UpdateItemFamilyRequest;
use App\Models\ItemFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ItemFamilyController extends Controller
{
   public function index(): View
    {
        $this->authorize('viewAny', ItemFamily::class);

        $itemFamilies = ItemFamily::query()
            ->orderBy('name')
            ->paginate(15);

        return view('item-families.index', compact('itemFamilies'));
    }

    public function create(): View
    {
        return view('item-families.create');
    }

    public function store(StoreItemFamilyRequest $request): RedirectResponse
    {
        ItemFamily::create($request->validated());

        return redirect()
            ->route('item-families.index')
            ->with('success', 'Família criada com sucesso.');
    }

    public function edit(ItemFamily $item_family): View
    {
        return view('item-families.edit', compact('item_family'));
    }

    public function update(UpdateItemFamilyRequest $request, ItemFamily $item_family): RedirectResponse
    {
        $item_family->update($request->validated());

        return redirect()
            ->route('item-families.index')
            ->with('success', 'Família atualizada com sucesso.');
    }
}
