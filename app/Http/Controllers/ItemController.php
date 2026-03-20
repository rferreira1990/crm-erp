<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Brand;
use App\Models\Item;
use App\Models\ItemFamily;
use App\Models\TaxRate;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(): View
    {
        $items = Item::with(['family', 'brand', 'unit', 'taxRate'])
            ->orderBy('name')
            ->paginate(20);

        return view('items.index', compact('items'));
    }

    public function create(): View
    {
        return view('items.create', [
            'item' => new Item(),
            'families' => $this->getActiveFamilies(),
            'brands' => $this->getActiveBrands(),
            'units' => $this->getActiveUnits(),
            'taxRates' => $this->getActiveTaxRates(),
        ]);
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        $item = Item::create([
            ...$request->validatedData(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('items.edit', $item)
            ->with('success', 'Artigo/serviço criado com sucesso.');
    }

    public function edit(Item $item): View
    {
        return view('items.edit', [
            'item' => $item,
            'families' => $this->getActiveFamilies(),
            'brands' => $this->getActiveBrands(),
            'units' => $this->getActiveUnits(),
            'taxRates' => $this->getActiveTaxRates(),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $item->update([
            ...$request->validatedData(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('items.edit', $item)
            ->with('success', 'Artigo/serviço atualizado com sucesso.');
    }

    private function getActiveFamilies()
    {
        return ItemFamily::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function getActiveBrands()
    {
        return Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function getActiveUnits()
    {
        return Unit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function getActiveTaxRates()
    {
        return TaxRate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
