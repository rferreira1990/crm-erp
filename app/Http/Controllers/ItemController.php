<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
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
        $items = Item::query()
            ->with(['family', 'brand', 'unit', 'taxRate'])
            ->orderByDesc('id')
            ->paginate(15);

        return view('items.index', compact('items'));
    }

    public function create(): View
    {
        $families = ItemFamily::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $brands = Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $taxRates = TaxRate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('items.create', compact('families', 'brands', 'units', 'taxRates'));
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        Item::create($request->validated());

        return redirect()
            ->route('items.index')
            ->with('success', 'Artigo criado com sucesso.');
    }
}
