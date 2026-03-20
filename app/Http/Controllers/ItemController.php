<?php

namespace App\Http\Controllers;

use App\Http\Requests\Items\StoreItemRequest;
use App\Http\Requests\Items\UpdateItemRequest;
use App\Models\Brand;
use App\Models\Item;
use App\Models\ItemFamily;
use App\Models\TaxRate;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $type = $request->input('type');
        $status = $request->input('status');
        $familyId = $request->input('family_id');
        $brandId = $request->input('brand_id');

        $items = Item::query()
            ->with([
                'family',
                'brand',
                'unit',
                'taxRate',
                'primaryImage',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when(in_array($type, ['product', 'service'], true), function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->when($familyId !== null && $familyId !== '', function ($query) use ($familyId) {
                $query->where('family_id', $familyId);
            })
            ->when($brandId !== null && $brandId !== '', function ($query) use ($brandId) {
                $query->where('brand_id', $brandId);
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'families' => $this->getActiveFamilies(),
            'brands' => $this->getActiveBrands(),
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
                'family_id' => $familyId,
                'brand_id' => $brandId,
            ],
        ]);
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
        $item->load([
            'files',
            'images',
            'documents',
            'primaryImage',
        ]);

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
