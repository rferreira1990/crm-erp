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
use Illuminate\Support\Facades\DB;
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
            'families' => $this->getFamiliesForSelect(),
            'brands' => $this->getBrandsForSelect(),
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
                'family_id' => $familyId,
                'brand_id' => $brandId,
            ],
        ]);
    }

    public function show(Item $item): View
    {
        $item->load([
            'family',
            'brand',
            'unit',
            'taxRate',
            'primaryImage',
            'images',
            'documents',
        ]);

        return view('items.show', [
            'item' => $item,
        ]);
    }

    public function create(): View
    {
        return view('items.create', [
            'item' => new Item(),
            'families' => $this->getFamiliesForSelect(),
            'brands' => $this->getBrandsForSelect(),
            'units' => $this->getUnitsForSelect(),
            'taxRates' => $this->getTaxRatesForSelect(),
        ]);
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        $item = DB::transaction(function () use ($request) {
            $item = Item::create($request->validatedData());

            $item->forceFill([
                'code' => 'ART-' . str_pad((string) $item->id, 6, '0', STR_PAD_LEFT),
            ])->saveQuietly();

            return $item;
        });

        if (auth()->user()?->can('items.edit')) {
            return redirect()
                ->route('items.edit', $item)
                ->with('success', 'Artigo/serviço criado com sucesso.');
        }

        return redirect()
            ->route('items.show', $item)
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
            'families' => $this->getFamiliesForSelect($item),
            'brands' => $this->getBrandsForSelect($item),
            'units' => $this->getUnitsForSelect($item),
            'taxRates' => $this->getTaxRatesForSelect($item),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validatedData());

        return redirect()
            ->route('items.edit', $item)
            ->with('success', 'Artigo/serviço atualizado com sucesso.');
    }

    private function getFamiliesForSelect(?Item $item = null)
    {
        return ItemFamily::query()
            ->where(function ($query) use ($item) {
                $query->where('is_active', true);

                if ($item?->family_id) {
                    $query->orWhereKey($item->family_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function getBrandsForSelect(?Item $item = null)
    {
        return Brand::query()
            ->where(function ($query) use ($item) {
                $query->where('is_active', true);

                if ($item?->brand_id) {
                    $query->orWhereKey($item->brand_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function getUnitsForSelect(?Item $item = null)
    {
        return Unit::query()
            ->where(function ($query) use ($item) {
                $query->where('is_active', true);

                if ($item?->unit_id) {
                    $query->orWhereKey($item->unit_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function getTaxRatesForSelect(?Item $item = null)
    {
        return TaxRate::query()
            ->where(function ($query) use ($item) {
                $query->where('is_active', true);

                if ($item?->tax_rate_id) {
                    $query->orWhereKey($item->tax_rate_id);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
