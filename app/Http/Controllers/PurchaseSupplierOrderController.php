<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchases\StorePurchaseSupplierOrderRequest;
use App\Http\Requests\Purchases\UpdatePurchaseSupplierOrderRequest;
use App\Models\CompanyProfile;
use App\Models\Item;
use App\Models\PaymentTerm;
use App\Models\PurchaseSupplierOrder;
use App\Models\Supplier;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseSupplierOrderController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseSupplierOrder::class);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => trim((string) $request->input('status', '')),
            'source_type' => trim((string) $request->input('source_type', '')),
            'supplier_id' => (int) $request->input('supplier_id', 0),
            'prepared_from' => trim((string) $request->input('prepared_from', '')),
            'prepared_to' => trim((string) $request->input('prepared_to', '')),
        ];

        $orders = PurchaseSupplierOrder::query()
            ->where('owner_id', Auth::id())
            ->with([
                'supplier:id,code,name',
                'purchaseRequest:id,code',
                'paymentTerm:id,name,days',
                'items:id,purchase_supplier_order_id,qty,received_qty,returned_qty',
            ])
            ->withCount(['items'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $subQuery) use ($filters): void {
                    $subQuery
                        ->where('id', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('notes', 'like', '%' . $filters['search'] . '%')
                        ->orWhereHas('supplier', function (Builder $supplierQuery) use ($filters): void {
                            $supplierQuery
                                ->where('name', 'like', '%' . $filters['search'] . '%')
                                ->orWhere('code', 'like', '%' . $filters['search'] . '%');
                        })
                        ->orWhereHas('purchaseRequest', function (Builder $rfqQuery) use ($filters): void {
                            $rfqQuery->where('code', 'like', '%' . $filters['search'] . '%');
                        });
                });
            })
            ->when($filters['status'] !== '', function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when($filters['source_type'] !== '', function (Builder $query) use ($filters): void {
                $query->where('source_type', $filters['source_type']);
            })
            ->when($filters['supplier_id'] > 0, function (Builder $query) use ($filters): void {
                $query->where('supplier_id', $filters['supplier_id']);
            })
            ->when($filters['prepared_from'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('prepared_at', '>=', $filters['prepared_from']);
            })
            ->when($filters['prepared_to'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('prepared_at', '<=', $filters['prepared_to']);
            })
            ->orderByDesc('prepared_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $supplierOptions = Supplier::query()
            ->where('owner_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('purchases.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'orderStatuses' => PurchaseSupplierOrder::statuses(),
            'sourceTypes' => PurchaseSupplierOrder::sourceTypes(),
            'supplierOptions' => $supplierOptions,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseSupplierOrder::class);

        return view('purchases.orders.create', [
            'order' => new PurchaseSupplierOrder([
                'source_type' => PurchaseSupplierOrder::SOURCE_DIRECT,
                'status' => PurchaseSupplierOrder::STATUS_PREPARED,
                'currency' => 'EUR',
            ]),
            'suppliers' => $this->availableSuppliers(),
            'paymentTerms' => $this->availablePaymentTerms(),
            'orderItemInitialOptions' => $this->orderItemInitialOptions(),
        ]);
    }

    public function store(StorePurchaseSupplierOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseSupplierOrder::class);

        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated): PurchaseSupplierOrder {
            $order = PurchaseSupplierOrder::query()->create([
                'owner_id' => Auth::id(),
                'purchase_request_id' => null,
                'award_id' => null,
                'source_type' => PurchaseSupplierOrder::SOURCE_DIRECT,
                'supplier_id' => (int) $validated['supplier_id'],
                'purchase_quote_id' => null,
                'payment_term_id' => $validated['payment_term_id'] ?? null,
                'currency' => strtoupper((string) $validated['currency']),
                'status' => PurchaseSupplierOrder::STATUS_PREPARED,
                'subtotal_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'prepared_at' => $validated['prepared_at'],
                'prepared_by' => Auth::id(),
            ]);

            $this->syncItems($order, collect($validated['items'] ?? []));

            return $order;
        });

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'purchase_supplier_order',
            entityId: $order->id,
            payload: [
                'source_type' => $order->source_type,
                'supplier_id' => $order->supplier_id,
                'payment_term_id' => $order->payment_term_id,
                'status' => $order->status,
                'currency' => $order->currency,
                'subtotal_amount' => (float) $order->subtotal_amount,
                'items_count' => $order->items()->count(),
            ],
            ownerId: (int) $order->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-orders.show', $order)
            ->with('success', 'Encomenda direta criada com sucesso.');
    }

    public function show(PurchaseSupplierOrder $order): View
    {
        $this->authorize('view', $order);

        $order->load([
            'supplier:id,code,name,email,habitual_order_email,contact_person',
            'purchaseRequest:id,code,owner_id',
            'paymentTerm:id,name,days',
            'preparedBy:id,name',
            'items.item:id,code,name,unit_id,tracks_stock',
            'items.item.unit:id,code,name',
            'receipts:id,purchase_supplier_order_id,receipt_number,receipt_date,user_id,notes',
            'receipts.user:id,name',
            'returns:id,purchase_supplier_order_id,return_number,return_date,status,supplier_confirmation_status,user_id,closed_at,closed_by',
            'returns.user:id,name',
            'returns.closedBy:id,name',
            'returns.emailLogs:id,purchase_supplier_order_return_id,sent_at',
            'returns.linkedReceipt:id,receipt_number',
            'returns.items:id,purchase_supplier_order_return_id,quantity_returned',
        ]);

        return view('purchases.orders.show', [
            'order' => $order,
            'orderStatuses' => PurchaseSupplierOrder::statuses(),
        ]);
    }

    public function edit(PurchaseSupplierOrder $order): View|RedirectResponse
    {
        $this->authorize('update', $order);

        if (! $order->isDirect()) {
            return redirect()
                ->route('purchase-orders.show', $order)
                ->with('error', 'Apenas encomendas diretas podem ser editadas neste ecrã.');
        }

        if ($this->hasOperationalMovements($order)) {
            return redirect()
                ->route('purchase-orders.show', $order)
                ->with('error', 'Nao e possivel editar a encomenda apos rececoes/devolucoes.');
        }

        $order->load(['items.item:id,code,name,description,unit_id', 'items.item.unit:id,code,name']);

        return view('purchases.orders.edit', [
            'order' => $order,
            'suppliers' => $this->availableSuppliers(),
            'paymentTerms' => $this->availablePaymentTerms(),
            'orderItemInitialOptions' => $this->orderItemInitialOptions($order),
        ]);
    }

    public function update(UpdatePurchaseSupplierOrderRequest $request, PurchaseSupplierOrder $order): RedirectResponse
    {
        $this->authorize('update', $order);

        if (! $order->isDirect()) {
            return redirect()
                ->route('purchase-orders.show', $order)
                ->with('error', 'Apenas encomendas diretas podem ser editadas neste ecrã.');
        }

        if ($this->hasOperationalMovements($order)) {
            return redirect()
                ->route('purchase-orders.show', $order)
                ->with('error', 'Nao e possivel editar a encomenda apos rececoes/devolucoes.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($order, $validated): void {
            $order->update([
                'supplier_id' => (int) $validated['supplier_id'],
                'payment_term_id' => $validated['payment_term_id'] ?? null,
                'currency' => strtoupper((string) $validated['currency']),
                'notes' => $validated['notes'] ?? null,
                'prepared_at' => $validated['prepared_at'],
            ]);

            $order->items()->delete();
            $this->syncItems($order, collect($validated['items'] ?? []));
        });

        $order->refresh();

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'purchase_supplier_order',
            entityId: $order->id,
            payload: [
                'source_type' => $order->source_type,
                'supplier_id' => $order->supplier_id,
                'payment_term_id' => $order->payment_term_id,
                'status' => $order->status,
                'currency' => $order->currency,
                'subtotal_amount' => (float) $order->subtotal_amount,
                'items_count' => $order->items()->count(),
            ],
            ownerId: (int) $order->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-orders.show', $order)
            ->with('success', 'Encomenda atualizada com sucesso.');
    }

    public function pdf(PurchaseSupplierOrder $order)
    {
        $this->authorize('view', $order);

        $order->loadMissing([
            'purchaseRequest:id,code,owner_id',
            'supplier:id,code,name,tax_number,email,habitual_order_email,address,postal_code,city,country,contact_person,phone',
            'paymentTerm:id,name,days',
            'award:id,purchase_request_id,mode,decided_at,decided_by',
            'award.decidedBy:id,name',
            'items.purchaseRequestItem:id,purchase_request_id,item_id,description,qty,unit_snapshot,sort_order,notes',
            'items.purchaseRequestItem.item:id,code,name,unit_id',
            'items.purchaseRequestItem.item.unit:id,name,code',
            'items.item:id,code,name,unit_id',
            'items.item.unit:id,name,code',
        ]);

        $companyProfile = CompanyProfile::firstForOwner((int) Auth::id());

        $pdf = Pdf::loadView('purchases.orders.pdf', [
            'purchaseRequest' => $order->purchaseRequest,
            'order' => $order,
            'companyProfile' => $companyProfile,
        ])->setPaper('a4', 'portrait');

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'purchase_supplier_order',
            entityId: $order->id,
            payload: [
                'event' => 'supplier_order_pdf_generated',
                'source_type' => $order->source_type,
                'purchase_request_id' => $order->purchase_request_id,
                'purchase_request_code' => $order->purchaseRequest?->code,
                'supplier_id' => $order->supplier_id,
                'supplier_name' => $order->supplier?->name,
            ],
            ownerId: (int) $order->owner_id,
            userId: Auth::id(),
        );

        return $pdf->stream('encomenda-' . $order->id . '.pdf');
    }

    private function hasOperationalMovements(PurchaseSupplierOrder $order): bool
    {
        return $order->receipts()->exists() || $order->returns()->exists();
    }

    /**
     * @param Collection<int, array<string, mixed>> $lines
     */
    private function syncItems(PurchaseSupplierOrder $order, Collection $lines): void
    {
        $sortOrder = 1;
        $subtotal = 0.0;

        foreach ($lines as $line) {
            $qty = round((float) ($line['qty'] ?? 0), 3);
            $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);
            $discountPercent = $line['discount_percent'] !== null
                ? round((float) $line['discount_percent'], 3)
                : null;
            $discountFactor = 1 - ((float) ($discountPercent ?? 0) / 100);
            $lineTotal = round($qty * $unitPrice * $discountFactor, 2);

            $order->items()->create([
                'purchase_request_item_id' => null,
                'purchase_quote_item_id' => null,
                'item_id' => $line['item_id'] ?? null,
                'description' => $line['description'],
                'unit_snapshot' => $line['unit_snapshot'] ?? null,
                'supplier_item_reference' => null,
                'qty' => $qty,
                'received_qty' => 0,
                'returned_qty' => 0,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'line_total' => $lineTotal,
                'notes' => $line['notes'] ?? null,
                'sort_order' => $sortOrder++,
            ]);

            $subtotal += $lineTotal;
        }

        $order->update([
            'subtotal_amount' => round($subtotal, 2),
        ]);
    }

    private function availableSuppliers()
    {
        return Supplier::query()
            ->where('owner_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'email', 'contact_person', 'habitual_order_email']);
    }

    private function availablePaymentTerms()
    {
        return PaymentTerm::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'days']);
    }

    private function orderItemInitialOptions(?PurchaseSupplierOrder $order = null): array
    {
        $itemIds = collect(old('items', []))
            ->pluck('item_id')
            ->when($order, function (Collection $ids) use ($order) {
                return $ids->merge($order->items->pluck('item_id'));
            })
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
            ->with('unit:id,code,name')
            ->get(['id', 'code', 'name', 'description', 'unit_id'])
            ->mapWithKeys(function (Item $item): array {
                return [
                    $item->id => [
                        'id' => (int) $item->id,
                        'label' => $item->code . ' - ' . $item->name,
                        'name' => $item->name,
                        'description' => $item->description,
                        'unit' => $item->unit?->code,
                    ],
                ];
            })
            ->all();
    }
}

