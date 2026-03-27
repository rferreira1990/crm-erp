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
use Illuminate\Support\Facades\Auth;
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
            ->where('owner_id', Auth::id())
            ->with(['family', 'brand', 'unit', 'taxRate', 'primaryImage'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when(in_array($type, ['product', 'service'], true), fn($q) => $q->where('type', $type))
            ->when(in_array($status, ['active', 'inactive'], true), fn($q) => $q->where('is_active', $status === 'active'))
            ->when($familyId, fn($q) => $q->where('family_id', $familyId))
            ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'families' => $this->getFamiliesForSelect(),
            'brands' => $this->getBrandsForSelect(),
            'filters' => compact('search', 'type', 'status', 'family_id', 'brand_id'),
        ]);
    }

    public function show(Item $item): View
    {
        $this->authorizeItem($item);

        $item->load(['family', 'brand', 'unit', 'taxRate', 'primaryImage', 'images', 'documents']);

        return view('items.show', compact('item'));
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

        return redirect()
            ->route('items.edit', $item)
            ->with('success', 'Artigo/serviço criado com sucesso.');
    }

    public function edit(Item $item): View
    {
        $this->authorizeItem($item);

        $item->load(['files', 'images', 'documents', 'primaryImage']);

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
        $this->authorizeItem($item);

        $item->update($request->validatedData());

        return redirect()
            ->route('items.edit', $item)
            ->with('success', 'Artigo/serviço atualizado com sucesso.');
    }

    private function authorizeItem(Item $item): void
    {
        abort_unless((int) $item->owner_id === (int) Auth::id(), 403);
    }

    private function getFamiliesForSelect(?Item $item = null)
    {
        return ItemFamily::query()
            ->where('owner_id', Auth::id())
            ->where(function ($q) use ($item) {
                $q->where('is_active', true);
                if ($item?->family_id) {
                    $q->orWhere('id', $item->family_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function getBrandsForSelect(?Item $item = null)
    {
        return Brand::query()
            ->where('owner_id', Auth::id())
            ->where(function ($q) use ($item) {
                $q->where('is_active', true);
                if ($item?->brand_id) {
                    $q->orWhere('id', $item->brand_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function getUnitsForSelect(?Item $item = null)
    {
        return Unit::query()
            ->where('owner_id', Auth::id())
            ->where(function ($q) use ($item) {
                $q->where('is_active', true);
                if ($item?->unit_id) {
                    $q->orWhere('id', $item->unit_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function getTaxRatesForSelect(?Item $item = null)
    {
        return TaxRate::query()
            ->where('owner_id', Auth::id())
            ->where(function ($q) use ($item) {
                $q->where('is_active', true);
                if ($item?->tax_rate_id) {
                    $q->orWhere('id', $item->tax_rate_id);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
