<?php

namespace App\Http\Controllers;

use App\Http\Requests\Items\StoreItemRequest;
use App\Http\Requests\Items\UpdateItemRequest;
use App\Models\Brand;
use App\Models\Item;
use App\Models\ItemFamily;
use App\Models\TaxRate;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search' => (string) $request->input('search', ''),
            'type' => (string) $request->input('type', ''),
            'status' => (string) $request->input('status', ''),
            'family_id' => (string) $request->input('family_id', ''),
            'brand_id' => (string) $request->input('brand_id', ''),
        ];

        $items = Item::query()
            ->with(['family', 'brand', 'unit', 'taxRate', 'primaryImage'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] !== '', function (Builder $query) use ($filters) {
                $query->where('type', $filters['type']);
            })
            ->when($filters['status'] !== '', function (Builder $query) use ($filters) {
                if ($filters['status'] === 'active') {
                    $query->where('is_active', true);
                }

                if ($filters['status'] === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($filters['family_id'] !== '', function (Builder $query) use ($filters) {
                $query->where('family_id', (int) $filters['family_id']);
            })
            ->when($filters['brand_id'] !== '', function (Builder $query) use ($filters) {
                $query->where('brand_id', (int) $filters['brand_id']);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $families = ItemFamily::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $brands = Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('items.index', compact('items', 'families', 'brands', 'filters'));
    }

    public function create()
    {
        $item = new Item([
            'type' => 'product',
            'tracks_stock' => false,
            'stock_alert' => false,
            'is_active' => true,
            'min_stock' => 0,
            'max_stock' => null,
        ]);

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
            ->orderBy('name')
            ->get();

        return view('items.create', compact('item', 'families', 'brands', 'units', 'taxRates'));
    }

    public function store(StoreItemRequest $request)
    {
        $item = Item::create([
            ...$request->validatedData(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if (auth()->user()->can('items.edit')) {
            return redirect()
                ->route('items.edit', $item)
                ->with('success', 'Artigo criado com sucesso.');
        }

        return redirect()
            ->route('items.index')
            ->with('success', 'Artigo criado com sucesso.');
    }

    public function edit(Item $item)
    {
        $item->load([
            'family',
            'brand',
            'unit',
            'taxRate',
            'files',
            'images',
            'documents',
            'primaryImage',
        ]);

        $families = ItemFamily::query()
            ->where(function (Builder $query) use ($item) {
                $query->where('is_active', true);

                if ($item->family_id) {
                    $query->orWhere('id', $item->family_id);
                }
            })
            ->orderBy('name')
            ->get();

        $brands = Brand::query()
            ->where(function (Builder $query) use ($item) {
                $query->where('is_active', true);

                if ($item->brand_id) {
                    $query->orWhere('id', $item->brand_id);
                }
            })
            ->orderBy('name')
            ->get();

        $units = Unit::query()
            ->where(function (Builder $query) use ($item) {
                $query->where('is_active', true);

                if ($item->unit_id) {
                    $query->orWhere('id', $item->unit_id);
                }
            })
            ->orderBy('name')
            ->get();

        $taxRates = TaxRate::query()
            ->where(function (Builder $query) use ($item) {
                $query->where('is_active', true);

                if ($item->tax_rate_id) {
                    $query->orWhere('id', $item->tax_rate_id);
                }
            })
            ->orderBy('name')
            ->get();

        return view('items.edit', compact('item', 'families', 'brands', 'units', 'taxRates'));
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $item->update([
            ...$request->validatedData(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('items.edit', $item)
            ->with('success', 'Artigo atualizado com sucesso.');
    }
}
