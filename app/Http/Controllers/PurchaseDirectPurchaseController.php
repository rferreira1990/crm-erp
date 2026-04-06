<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchases\StorePurchaseDirectPurchaseRequest;
use App\Models\Item;
use App\Models\PurchaseDirectPurchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseDirectPurchaseController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseDirectPurchase::class);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'supplier_id' => (int) $request->input('supplier_id', 0),
            'status' => trim((string) $request->input('status', '')),
            'purchase_from' => trim((string) $request->input('purchase_from', '')),
            'purchase_to' => trim((string) $request->input('purchase_to', '')),
        ];

        $directPurchases = PurchaseDirectPurchase::query()
            ->where('owner_id', Auth::id())
            ->with([
                'supplier:id,code,name',
                'creator:id,name',
            ])
            ->withCount(['items'])
            ->withSum('items as total_qty', 'quantity')
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $subQuery) use ($filters): void {
                    $subQuery
                        ->where('document_number', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('external_reference', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('notes', 'like', '%' . $filters['search'] . '%')
                        ->orWhereHas('supplier', function (Builder $supplierQuery) use ($filters): void {
                            $supplierQuery
                                ->where('name', 'like', '%' . $filters['search'] . '%')
                                ->orWhere('code', 'like', '%' . $filters['search'] . '%');
                        });
                });
            })
            ->when($filters['supplier_id'] > 0, function (Builder $query) use ($filters): void {
                $query->where('supplier_id', $filters['supplier_id']);
            })
            ->when($filters['status'] !== '', function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when($filters['purchase_from'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('purchase_date', '>=', $filters['purchase_from']);
            })
            ->when($filters['purchase_to'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('purchase_date', '<=', $filters['purchase_to']);
            })
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $suppliers = Supplier::query()
            ->where('owner_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('purchases.direct-purchases.index', [
            'directPurchases' => $directPurchases,
            'filters' => $filters,
            'suppliers' => $suppliers,
            'statuses' => PurchaseDirectPurchase::statuses(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseDirectPurchase::class);

        return view('purchases.direct-purchases.create', [
            'directPurchase' => new PurchaseDirectPurchase([
                'currency' => 'EUR',
                'status' => PurchaseDirectPurchase::STATUS_POSTED,
            ]),
            'suppliers' => $this->availableSuppliers(),
            'taxRates' => $this->activeTaxRates(),
            'itemInitialOptions' => $this->itemInitialOptions(),
        ]);
    }

    public function store(StorePurchaseDirectPurchaseRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseDirectPurchase::class);

        $validated = $request->validated();

        $result = DB::transaction(function () use ($validated): array {
            $ownerId = (int) Auth::id();

            $directPurchase = PurchaseDirectPurchase::query()->create([
                'owner_id' => $ownerId,
                'supplier_id' => (int) $validated['supplier_id'],
                'document_number' => 'PENDING-' . uniqid(),
                'purchase_date' => $validated['purchase_date'],
                'external_reference' => $validated['external_reference'] ?? null,
                'currency' => strtoupper((string) $validated['currency']),
                'status' => PurchaseDirectPurchase::STATUS_POSTED,
                'subtotal_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);

            $directPurchase->update([
                'document_number' => 'CD-' . $directPurchase->purchase_date->format('Y') . '-' . str_pad((string) $directPurchase->id, 6, '0', STR_PAD_LEFT),
            ]);

            $lines = collect($validated['items'] ?? []);
            $itemIds = $lines->pluck('item_id')->map(fn ($value) => (int) $value)->unique()->values()->all();
            $taxRateIds = $lines->pluck('vat_rate_id')->map(fn ($value) => (int) $value)->unique()->values()->all();

            $itemsById = Item::query()
                ->whereIn('id', $itemIds)
                ->where('is_active', true)
                ->where('type', '!=', 'service')
                ->where('tracks_stock', true)
                ->where(function (Builder $query) use ($ownerId): void {
                    $query->where('owner_id', $ownerId)
                        ->orWhereNull('owner_id');
                })
                ->with('unit:id,code,name')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $taxRatesById = TaxRate::query()
                ->whereIn('id', $taxRateIds)
                ->where('is_active', true)
                ->get(['id', 'percent'])
                ->keyBy('id');

            $sortOrder = 1;
            $subtotalAmount = 0.0;
            $taxAmount = 0.0;
            $totalAmount = 0.0;
            $totalQty = 0.0;
            $stockMovementsCount = 0;

            foreach ($lines as $index => $line) {
                $itemId = (int) ($line['item_id'] ?? 0);
                $taxRateId = (int) ($line['vat_rate_id'] ?? 0);
                $quantity = round((float) ($line['quantity'] ?? 0), 3);
                $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);

                /** @var Item|null $item */
                $item = $itemsById->get($itemId);
                if (! $item) {
                    throw ValidationException::withMessages([
                        'items.' . $index . '.item_id' => 'Artigo invalido para compra direta.',
                    ]);
                }

                /** @var TaxRate|null $taxRate */
                $taxRate = $taxRatesById->get($taxRateId);
                if (! $taxRate) {
                    throw ValidationException::withMessages([
                        'items.' . $index . '.vat_rate_id' => 'Taxa de IVA invalida.',
                    ]);
                }

                $descriptionSnapshot = (string) ($line['description_snapshot'] ?? '');
                if ($descriptionSnapshot === '') {
                    $descriptionSnapshot = (string) $item->name;
                }

                $unitSnapshot = (string) ($line['unit_snapshot'] ?? '');
                if ($unitSnapshot === '') {
                    $unitSnapshot = (string) ($item->unit?->code ?? '');
                }

                $vatPercent = round((float) $taxRate->percent, 3);
                $lineSubtotal = round($quantity * $unitPrice, 2);
                $lineVatAmount = round($lineSubtotal * ($vatPercent / 100), 2);
                $lineTotal = round($lineSubtotal + $lineVatAmount, 2);

                $purchaseItem = $directPurchase->items()->create([
                    'owner_id' => $ownerId,
                    'item_id' => $item->id,
                    'tax_rate_id' => $taxRate->id,
                    'description_snapshot' => $descriptionSnapshot,
                    'unit_snapshot' => $unitSnapshot !== '' ? $unitSnapshot : null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'vat_percent' => $vatPercent,
                    'line_subtotal' => $lineSubtotal,
                    'line_vat_amount' => $lineVatAmount,
                    'line_total' => $lineTotal,
                    'notes' => $line['notes'] ?? null,
                    'sort_order' => $sortOrder++,
                ]);

                $stockBefore = round((float) $item->current_stock, 3);
                $stockAfter = round($stockBefore + $quantity, 3);

                $item->update([
                    'current_stock' => $stockAfter,
                ]);

                StockMovement::query()->create([
                    'item_id' => $item->id,
                    'movement_type' => StockMovement::TYPE_PURCHASE_DIRECT,
                    'direction' => StockMovement::DIRECTION_IN,
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'occurred_at' => now(),
                    'source_type' => 'purchase_direct_purchase',
                    'source_id' => $purchaseItem->id,
                    'notes' => 'Compra direta ' . $directPurchase->document_number,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);

                $subtotalAmount = round($subtotalAmount + $lineSubtotal, 2);
                $taxAmount = round($taxAmount + $lineVatAmount, 2);
                $totalAmount = round($totalAmount + $lineTotal, 2);
                $totalQty = round($totalQty + $quantity, 3);
                $stockMovementsCount++;
            }

            $directPurchase->update([
                'subtotal_amount' => $subtotalAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'updated_by' => $ownerId,
            ]);

            return [
                'purchase' => $directPurchase,
                'total_qty' => $totalQty,
                'stock_movements_count' => $stockMovementsCount,
            ];
        });

        /** @var PurchaseDirectPurchase $purchase */
        $purchase = $result['purchase'];

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'purchase_direct_purchase',
            entityId: $purchase->id,
            payload: [
                'document_number' => $purchase->document_number,
                'supplier_id' => $purchase->supplier_id,
                'purchase_date' => optional($purchase->purchase_date)->format('Y-m-d'),
                'status' => $purchase->status,
                'currency' => $purchase->currency,
                'subtotal_amount' => (float) $purchase->subtotal_amount,
                'tax_amount' => (float) $purchase->tax_amount,
                'total_amount' => (float) $purchase->total_amount,
                'items_count' => $purchase->items()->count(),
                'total_qty' => $result['total_qty'],
                'stock_movements_count' => $result['stock_movements_count'],
                'external_reference' => $purchase->external_reference,
            ],
            ownerId: (int) $purchase->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-direct-purchases.show', $purchase)
            ->with('success', 'Compra direta registada com sucesso.');
    }

    public function show(PurchaseDirectPurchase $directPurchase): View
    {
        $this->authorize('view', $directPurchase);

        $directPurchase->load([
            'supplier:id,code,name,email,contact_person',
            'creator:id,name',
            'updater:id,name',
            'items.item:id,code,name,unit_id',
            'items.item.unit:id,code,name',
            'items.taxRate:id,name,percent',
        ]);

        return view('purchases.direct-purchases.show', [
            'directPurchase' => $directPurchase,
        ]);
    }

    private function availableSuppliers()
    {
        return Supplier::query()
            ->where('owner_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'email', 'contact_person']);
    }

    private function activeTaxRates()
    {
        return TaxRate::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('percent')
            ->orderBy('name')
            ->get(['id', 'name', 'percent', 'saft_code', 'is_default']);
    }

    private function itemInitialOptions(): array
    {
        $itemIds = collect(old('items', []))
            ->pluck('item_id')
            ->filter()
            ->map(fn ($itemId) => (int) $itemId)
            ->unique()
            ->values()
            ->all();

        if (count($itemIds) === 0) {
            return [];
        }

        return Item::query()
            ->whereIn('id', $itemIds)
            ->with([
                'unit:id,code,name',
                'taxRate:id,percent',
            ])
            ->get(['id', 'code', 'name', 'description', 'unit_id', 'tax_rate_id'])
            ->mapWithKeys(function (Item $item): array {
                return [
                    $item->id => [
                        'id' => (int) $item->id,
                        'label' => $item->code . ' - ' . $item->name,
                        'name' => $item->name,
                        'description' => $item->description,
                        'unit' => $item->unit?->code,
                        'tax_rate_id' => $item->tax_rate_id,
                    ],
                ];
            })
            ->all();
    }
}

