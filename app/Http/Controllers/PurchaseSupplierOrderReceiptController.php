<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchases\StorePurchaseSupplierOrderReceiptRequest;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseSupplierOrder;
use App\Models\PurchaseSupplierOrderItem;
use App\Models\PurchaseSupplierOrderReceipt;
use App\Models\StockMovement;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseSupplierOrderReceiptController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function create(PurchaseRequest $purchaseRequest, PurchaseSupplierOrder $order): View
    {
        $this->authorize('view', $purchaseRequest);
        $this->ensureOrderRouteScope($purchaseRequest, $order);
        $this->authorize('viewAny', [PurchaseSupplierOrderReceipt::class, $purchaseRequest, $order]);

        $order->load([
            'supplier:id,code,name',
            'paymentTerm:id,name,days',
            'items.item:id,code,name,unit_id,tracks_stock',
            'items.item.unit:id,code,name',
            'receipts.user:id,name',
            'receipts.items.orderItem:id,purchase_supplier_order_id,description',
        ]);

        $orderItems = $order->items
            ->sortBy(fn (PurchaseSupplierOrderItem $item) => [(int) $item->sort_order, (int) $item->id])
            ->values();

        return view('purchases.orders.receipts.create', [
            'purchaseRequest' => $purchaseRequest,
            'order' => $order,
            'orderItems' => $orderItems,
            'orderStatuses' => PurchaseSupplierOrder::statuses(),
        ]);
    }

    public function store(
        StorePurchaseSupplierOrderReceiptRequest $request,
        PurchaseRequest $purchaseRequest,
        PurchaseSupplierOrder $order
    ): RedirectResponse {
        $this->authorize('update', $purchaseRequest);
        $this->ensureOrderRouteScope($purchaseRequest, $order);
        $this->authorize('create', [PurchaseSupplierOrderReceipt::class, $purchaseRequest, $order]);

        if (! $order->hasPendingReceipt()) {
            return redirect()
                ->route('purchase-requests.supplier-orders.receipts.create', [$purchaseRequest, $order])
                ->with('error', 'A encomenda ja se encontra totalmente recebida.');
        }

        $validated = $request->validated();
        $receiptDate = $validated['receipt_date'];
        $notes = $validated['notes'] ?? null;

        $positiveQuantities = collect($validated['quantities'] ?? [])
            ->mapWithKeys(function (mixed $qty, mixed $lineId): array {
                return [(int) $lineId => round((float) $qty, 3)];
            })
            ->filter(fn (float $qty): bool => $qty > 0);

        if ($positiveQuantities->isEmpty()) {
            return redirect()
                ->route('purchase-requests.supplier-orders.receipts.create', [$purchaseRequest, $order])
                ->with('error', 'Indica pelo menos uma quantidade para rececao.');
        }

        $stockMovementsCount = 0;
        $totalReceivedQty = 0.0;
        $orderOldStatus = (string) $order->status;
        $orderNewStatus = $orderOldStatus;
        $receipt = null;

        DB::transaction(function () use (
            $purchaseRequest,
            $order,
            $validated,
            $receiptDate,
            $notes,
            &$stockMovementsCount,
            &$totalReceivedQty,
            &$orderOldStatus,
            &$orderNewStatus,
            &$receipt
        ): void {
            $lockedOrder = PurchaseSupplierOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedItems = PurchaseSupplierOrderItem::query()
                ->where('purchase_supplier_order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $quantities = collect($validated['quantities'] ?? [])
                ->mapWithKeys(fn (mixed $qty, mixed $lineId): array => [(int) $lineId => round((float) $qty, 3)])
                ->filter(fn (float $qty): bool => $qty > 0);

            if ($quantities->isEmpty()) {
                throw ValidationException::withMessages([
                    'quantities' => 'Indica pelo menos uma linha com quantidade a receber.',
                ]);
            }

            foreach ($quantities as $lineId => $qtyNow) {
                /** @var PurchaseSupplierOrderItem|null $lockedLine */
                $lockedLine = $lockedItems->get((int) $lineId);
                if (! $lockedLine) {
                    throw ValidationException::withMessages([
                        'quantities.' . $lineId => 'Linha de encomenda invalida.',
                    ]);
                }

                $pendingQty = $lockedLine->pendingQty();
                if ($qtyNow - $pendingQty > 0.0005) {
                    throw ValidationException::withMessages([
                        'quantities.' . $lineId => 'A quantidade recebida excede o pendente da linha.',
                    ]);
                }
            }

            $receipt = PurchaseSupplierOrderReceipt::query()->create([
                'owner_id' => (int) $purchaseRequest->owner_id,
                'purchase_supplier_order_id' => $lockedOrder->id,
                'receipt_number' => 'PENDING-' . uniqid(),
                'receipt_date' => $receiptDate,
                'user_id' => (int) Auth::id(),
                'notes' => $notes,
            ]);

            $receipt->update([
                'receipt_number' => 'REC-' . $receipt->receipt_date->format('Y') . '-' . str_pad((string) $receipt->id, 6, '0', STR_PAD_LEFT),
            ]);

            $lockedStockItems = [];

            foreach ($quantities as $lineId => $qtyNow) {
                /** @var PurchaseSupplierOrderItem $lockedLine */
                $lockedLine = $lockedItems->get((int) $lineId);

                $newReceivedQty = round((float) $lockedLine->received_qty + (float) $qtyNow, 3);
                $lockedLine->update([
                    'received_qty' => $newReceivedQty,
                ]);

                $receiptItem = $receipt->items()->create([
                    'owner_id' => (int) $purchaseRequest->owner_id,
                    'purchase_supplier_order_item_id' => $lockedLine->id,
                    'item_id' => $lockedLine->item_id,
                    'quantity_received' => $qtyNow,
                ]);

                $totalReceivedQty = round($totalReceivedQty + (float) $qtyNow, 3);

                if (! $lockedLine->item_id) {
                    continue;
                }

                if (! isset($lockedStockItems[(int) $lockedLine->item_id])) {
                    $lockedStockItems[(int) $lockedLine->item_id] = Item::query()
                        ->lockForUpdate()
                        ->find((int) $lockedLine->item_id);
                }

                /** @var Item|null $item */
                $item = $lockedStockItems[(int) $lockedLine->item_id];
                if (! $item || ! $item->tracks_stock) {
                    continue;
                }

                $stockBefore = round((float) $item->current_stock, 3);
                $stockAfter = round($stockBefore + (float) $qtyNow, 3);

                $item->update([
                    'current_stock' => $stockAfter,
                ]);

                StockMovement::query()->create([
                    'item_id' => $item->id,
                    'movement_type' => StockMovement::TYPE_PURCHASE_RECEIPT,
                    'direction' => StockMovement::DIRECTION_IN,
                    'quantity' => $qtyNow,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'occurred_at' => now(),
                    'source_type' => 'purchase_receipt',
                    'source_id' => $receiptItem->id,
                    'notes' => 'Rececao da encomenda ' . $lockedOrder->id . ' / ' . $receipt->receipt_number,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                $stockMovementsCount++;
            }

            $orderOldStatus = (string) $lockedOrder->status;
            $orderNewStatus = $this->resolveOrderStatus($lockedItems->values());

            if ($orderNewStatus !== $orderOldStatus) {
                $lockedOrder->update([
                    'status' => $orderNewStatus,
                ]);
            }
        });

        if (! $receipt instanceof PurchaseSupplierOrderReceipt) {
            return redirect()
                ->route('purchase-requests.supplier-orders.receipts.create', [$purchaseRequest, $order])
                ->with('error', 'Nao foi possivel registar a rececao.');
        }

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'purchase_supplier_order_receipt',
            entityId: $receipt->id,
            payload: [
                'purchase_request_id' => $purchaseRequest->id,
                'purchase_request_code' => $purchaseRequest->code,
                'purchase_supplier_order_id' => $order->id,
                'receipt_number' => $receipt->receipt_number,
                'receipt_date' => optional($receipt->receipt_date)->format('Y-m-d'),
                'total_received_qty' => $totalReceivedQty,
                'stock_movements_count' => $stockMovementsCount,
                'notes' => $receipt->notes,
            ],
            ownerId: (int) $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        if ($orderNewStatus !== $orderOldStatus) {
            $this->activityLogService->log(
                action: $orderNewStatus === PurchaseSupplierOrder::STATUS_RECEIVED
                    ? ActivityActions::COMPLETED
                    : ActivityActions::STATUS_CHANGED,
                entity: 'purchase_supplier_order',
                entityId: $order->id,
                payload: [
                    'purchase_request_id' => $purchaseRequest->id,
                    'purchase_request_code' => $purchaseRequest->code,
                    'old_status' => $orderOldStatus,
                    'new_status' => $orderNewStatus,
                ],
                ownerId: (int) $purchaseRequest->owner_id,
                userId: Auth::id(),
            );
        }

        return redirect()
            ->route('purchase-requests.supplier-orders.receipts.create', [$purchaseRequest, $order])
            ->with('success', 'Rececao registada com sucesso.');
    }

    private function ensureOrderRouteScope(PurchaseRequest $purchaseRequest, PurchaseSupplierOrder $order): void
    {
        abort_if((int) $purchaseRequest->owner_id !== (int) Auth::id(), 404);

        if ((int) $order->purchase_request_id !== (int) $purchaseRequest->id) {
            abort(404);
        }

        if ((int) ($order->purchaseRequest?->owner_id ?? 0) !== (int) $purchaseRequest->owner_id) {
            abort(404);
        }
    }

    /**
     * @param Collection<int, PurchaseSupplierOrderItem> $items
     */
    private function resolveOrderStatus(Collection $items): string
    {
        if ($items->isEmpty()) {
            return PurchaseSupplierOrder::STATUS_PREPARED;
        }

        $hasAnyReceived = $items->contains(fn (PurchaseSupplierOrderItem $item): bool => (float) $item->received_qty > 0);
        $allReceived = $items->every(fn (PurchaseSupplierOrderItem $item): bool => $item->pendingQty() <= 0.0005);

        if ($allReceived) {
            return PurchaseSupplierOrder::STATUS_RECEIVED;
        }

        if ($hasAnyReceived) {
            return PurchaseSupplierOrder::STATUS_PARTIALLY_RECEIVED;
        }

        return PurchaseSupplierOrder::STATUS_PREPARED;
    }
}
