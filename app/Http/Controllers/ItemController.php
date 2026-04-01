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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Item::class);

        $filters = $this->resolveFilters($request);

        $items = $this->buildFilteredItemsQuery($filters)
            ->with([
                'family',
                'brand',
                'unit',
                'taxRate',
                'primaryImage',
            ])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'families' => $this->getFamiliesForSelect(),
            'brands' => $this->getBrandsForSelect(),
            'filters' => $filters,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Item::class);

        $filters = $this->resolveFilters($request);

        $items = $this->buildFilteredItemsQuery($filters)
            ->with([
                'family:id,name,parent_id',
                'brand:id,name',
                'unit:id,code,name',
                'taxRate:id,name,saft_code,percent',
            ])
            ->orderBy('name')
            ->get();
        $familyPathLookup = $this->buildFamilyPathLookup();

        $filename = 'items-export-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($items, $familyPathLookup) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'code',
                'name',
                'type',
                'item_family',
                'brand',
                'unit',
                'tax_rate',
                'purchase_price',
                'sale_price',
                'tracks_stock',
                'min_stock',
                'is_active',
                'notes',
                'barcode',
                'supplier_reference',
                'short_name',
                'max_stock',
                'max_discount_percent',
            ], ';');

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->code,
                    $item->name,
                    $item->type,
                    $familyPathLookup[$item->family_id] ?? $item->family?->name,
                    $item->brand?->name,
                    $item->unit?->code,
                    $item->taxRate?->name,
                    $this->formatCsvDecimal($item->cost_price),
                    $this->formatCsvDecimal($item->sale_price),
                    $item->tracks_stock ? '1' : '0',
                    $this->formatCsvDecimal($item->min_stock, 3),
                    $item->is_active ? '1' : '0',
                    $item->description,
                    $item->barcode,
                    $item->supplier_reference,
                    $item->short_name,
                    $this->formatCsvDecimal($item->max_stock, 3),
                    $this->formatCsvDecimal($item->max_discount_percent),
                ], ';');
            }

            fclose($handle);
        }, $filename, $headers);
    }

    public function show(Item $item): View
    {
        $this->authorize('view', $item);

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
        $this->authorize('create', Item::class);

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
        $this->authorize('create', Item::class);

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
        $this->authorize('update', $item);

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
        $this->authorize('update', $item);

        $item->update($request->validatedData());

        return redirect()
            ->route('items.edit', $item)
            ->with('success', 'Artigo/serviço atualizado com sucesso.');
    }

    private function getFamiliesForSelect(?Item $item = null)
    {
        $families = ItemFamily::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($item?->family_id) {
            $idsToInclude = array_merge(
                [$item->family_id],
                ItemFamily::ancestorIdsOf((int) $item->family_id)
            );

            $families = $families
                ->merge(
                    ItemFamily::query()
                        ->whereIn('id', $idsToInclude)
                        ->get()
                )
                ->unique('id')
                ->values();
        }

        return ItemFamily::flattenedHierarchy($families);
    }

    private function getBrandsForSelect(?Item $item = null)
    {
        return Brand::query()
            ->where(function ($query) use ($item) {
                $query->where('is_active', true);

                if ($item?->brand_id) {
                    $query->orWhere('id', $item->brand_id);
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
                    $query->orWhere('id', $item->unit_id);
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
                    $query->orWhere('id', $item->tax_rate_id);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function resolveFilters(Request $request): array
    {
        $search = trim((string) $request->input('search'));
        $type = $request->input('type');
        $status = $request->input('status');
        $familyId = $this->normalizeFilterId($request->input('family_id'));
        $brandId = $this->normalizeFilterId($request->input('brand_id'));

        return [
            'search' => $search,
            'type' => in_array($type, ['product', 'service'], true) ? $type : '',
            'status' => in_array($status, ['active', 'inactive'], true) ? $status : '',
            'family_id' => $familyId,
            'brand_id' => $brandId,
        ];
    }

    private function buildFilteredItemsQuery(array $filters): Builder
    {
        return Item::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = $filters['search'];

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
                $query->where('is_active', $filters['status'] === 'active');
            })
            ->when($filters['family_id'] !== null, function (Builder $query) use ($filters) {
                $familyIds = ItemFamily::descendantAndSelfIds((int) $filters['family_id']);
                $query->whereIn('family_id', $familyIds);
            })
            ->when($filters['brand_id'] !== null, function (Builder $query) use ($filters) {
                $query->where('brand_id', $filters['brand_id']);
            });
    }

    private function normalizeFilterId(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_INT) === false
            ? null
            : (int) $value;
    }

    private function formatCsvDecimal(mixed $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, $decimals, '.', '');
    }

    /**
     * @return array<int, string>
     */
    private function buildFamilyPathLookup(): array
    {
        $families = ItemFamily::query()
            ->get(['id', 'name', 'parent_id'])
            ->keyBy('id');

        $cache = [];
        $resolve = function (int $familyId) use (&$resolve, $families, &$cache): string {
            if (isset($cache[$familyId])) {
                return $cache[$familyId];
            }

            $family = $families->get($familyId);
            if (! $family) {
                return '';
            }

            $label = $family->name;
            $parentId = $family->parent_id !== null ? (int) $family->parent_id : null;

            if ($parentId !== null && $parentId !== $familyId) {
                $parentLabel = $resolve($parentId);
                if ($parentLabel !== '') {
                    $label = $parentLabel . ' > ' . $label;
                }
            }

            $cache[$familyId] = $label;

            return $label;
        };

        foreach ($families as $family) {
            $resolve((int) $family->id);
        }

        return $cache;
    }
}
