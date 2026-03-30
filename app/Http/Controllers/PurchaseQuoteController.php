<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchases\StorePurchaseQuoteRequest;
use App\Http\Requests\Purchases\UpdatePurchaseQuoteRequest;
use App\Models\PurchaseQuote;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseQuoteController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(StorePurchaseQuoteRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Nao e possivel registar propostas num pedido fechado ou cancelado.');
        }

        $validated = $request->validated();
        $supplier = Supplier::query()->findOrFail((int) $validated['supplier_id']);
        $purchaseRequest->loadMissing('items');

        $quote = DB::transaction(function () use ($purchaseRequest, $validated, $supplier) {
            $quote = PurchaseQuote::query()->create([
                'purchase_request_id' => $purchaseRequest->id,
                'supplier_id' => $supplier->id,
                'supplier_name_snapshot' => $supplier->name,
                'lead_time_days' => $validated['lead_time_days'] ?? null,
                'payment_term_snapshot' => $validated['payment_term_snapshot'] ?? null,
                'valid_until' => $validated['valid_until'] ?? null,
                'total_amount' => 0,
                'currency' => $validated['currency'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $calculatedTotal = $this->syncQuoteItems(
                quote: $quote,
                purchaseRequest: $purchaseRequest,
                items: $validated['items'] ?? []
            );

            $quote->update([
                'total_amount' => $validated['total_amount'] ?? $calculatedTotal,
            ]);

            if ($quote->status === PurchaseQuote::STATUS_SELECTED) {
                PurchaseQuote::query()
                    ->where('purchase_request_id', $purchaseRequest->id)
                    ->whereKeyNot($quote->id)
                    ->where('status', PurchaseQuote::STATUS_SELECTED)
                    ->update(['status' => PurchaseQuote::STATUS_RECEIVED, 'updated_by' => Auth::id()]);

                $purchaseRequest->update([
                    'status' => PurchaseRequest::STATUS_CLOSED,
                    'updated_by' => Auth::id(),
                ]);
            }

            return $quote;
        });

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'purchase_quote',
            entityId: $quote->id,
            payload: [
                'purchase_request_id' => $purchaseRequest->id,
                'purchase_request_code' => $purchaseRequest->code,
                'supplier_id' => $quote->supplier_id,
                'supplier_name' => $quote->supplier_name_snapshot,
                'total_amount' => $quote->total_amount,
                'currency' => $quote->currency,
                'lead_time_days' => $quote->lead_time_days,
                'status' => $quote->status,
                'quoted_lines_count' => $quote->items()->count(),
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Proposta registada com sucesso.');
    }

    public function update(
        UpdatePurchaseQuoteRequest $request,
        PurchaseRequest $purchaseRequest,
        PurchaseQuote $quote
    ): RedirectResponse {
        $this->authorize('update', $purchaseRequest);

        if ((int) $quote->purchase_request_id !== (int) $purchaseRequest->id) {
            abort(404);
        }

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Nao e possivel alterar propostas num pedido fechado ou cancelado.');
        }

        $validated = $request->validated();
        $supplier = Supplier::query()->findOrFail((int) $validated['supplier_id']);
        $purchaseRequest->loadMissing('items');

        $oldData = $quote->only([
            'supplier_id',
            'supplier_name_snapshot',
            'lead_time_days',
            'payment_term_snapshot',
            'valid_until',
            'total_amount',
            'currency',
            'status',
            'notes',
        ]);

        DB::transaction(function () use ($purchaseRequest, $quote, $validated, $supplier) {
            $quote->update([
                'supplier_id' => $supplier->id,
                'supplier_name_snapshot' => $supplier->name,
                'lead_time_days' => $validated['lead_time_days'] ?? null,
                'payment_term_snapshot' => $validated['payment_term_snapshot'] ?? null,
                'valid_until' => $validated['valid_until'] ?? null,
                'currency' => $validated['currency'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            $calculatedTotal = $this->syncQuoteItems(
                quote: $quote,
                purchaseRequest: $purchaseRequest,
                items: $validated['items'] ?? []
            );

            $quote->update([
                'total_amount' => $validated['total_amount'] ?? $calculatedTotal,
                'updated_by' => Auth::id(),
            ]);

            if ($quote->status === PurchaseQuote::STATUS_SELECTED) {
                PurchaseQuote::query()
                    ->where('purchase_request_id', $purchaseRequest->id)
                    ->whereKeyNot($quote->id)
                    ->where('status', PurchaseQuote::STATUS_SELECTED)
                    ->update(['status' => PurchaseQuote::STATUS_RECEIVED, 'updated_by' => Auth::id()]);

                $purchaseRequest->update([
                    'status' => PurchaseRequest::STATUS_CLOSED,
                    'updated_by' => Auth::id(),
                ]);
            }
        });

        $quote->refresh();

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'purchase_quote',
            entityId: $quote->id,
            payload: [
                'purchase_request_id' => $purchaseRequest->id,
                'purchase_request_code' => $purchaseRequest->code,
                'old' => $oldData,
                'new' => $quote->only(array_keys($oldData)),
                'quoted_lines_count' => $quote->items()->count(),
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Proposta atualizada com sucesso.');
    }

    public function destroy(PurchaseRequest $purchaseRequest, PurchaseQuote $quote): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        if ((int) $quote->purchase_request_id !== (int) $purchaseRequest->id) {
            abort(404);
        }

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Nao e possivel remover propostas num pedido fechado ou cancelado.');
        }

        $payload = [
            'purchase_request_id' => $purchaseRequest->id,
            'purchase_request_code' => $purchaseRequest->code,
            'supplier_id' => $quote->supplier_id,
            'supplier_name' => $quote->supplier_name_snapshot,
            'total_amount' => $quote->total_amount,
            'currency' => $quote->currency,
            'status' => $quote->status,
        ];

        $quote->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'purchase_quote',
            entityId: $quote->id,
            payload: $payload,
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Proposta removida com sucesso.');
    }

    public function select(PurchaseRequest $purchaseRequest, PurchaseQuote $quote): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        if ((int) $quote->purchase_request_id !== (int) $purchaseRequest->id) {
            abort(404);
        }

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Nao e possivel selecionar proposta num pedido fechado ou cancelado.');
        }

        DB::transaction(function () use ($purchaseRequest, $quote) {
            PurchaseQuote::query()
                ->where('purchase_request_id', $purchaseRequest->id)
                ->whereKeyNot($quote->id)
                ->where('status', PurchaseQuote::STATUS_SELECTED)
                ->update([
                    'status' => PurchaseQuote::STATUS_RECEIVED,
                    'updated_by' => Auth::id(),
                ]);

            $quote->update([
                'status' => PurchaseQuote::STATUS_SELECTED,
                'updated_by' => Auth::id(),
            ]);

            $purchaseRequest->update([
                'status' => PurchaseRequest::STATUS_CLOSED,
                'updated_by' => Auth::id(),
            ]);
        });

        $this->activityLogService->log(
            action: ActivityActions::STATUS_CHANGED,
            entity: 'purchase_quote',
            entityId: $quote->id,
            payload: [
                'purchase_request_id' => $purchaseRequest->id,
                'purchase_request_code' => $purchaseRequest->code,
                'supplier_id' => $quote->supplier_id,
                'supplier_name' => $quote->supplier_name_snapshot,
                'new_status' => PurchaseQuote::STATUS_SELECTED,
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Proposta selecionada com sucesso. RFQ marcado como fechado.');
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function syncQuoteItems(PurchaseQuote $quote, PurchaseRequest $purchaseRequest, array $items): float
    {
        $requestItems = $purchaseRequest->items->keyBy('id');

        $quote->items()->delete();

        $total = 0.0;

        foreach ($items as $row) {
            $requestItemId = (int) ($row['purchase_request_item_id'] ?? 0);

            if ($requestItemId <= 0) {
                continue;
            }

            $requestItem = $requestItems->get($requestItemId);
            if (! $requestItem) {
                continue;
            }

            $quotedQty = $row['quoted_qty'] ?? null;
            $unitPrice = $row['unit_price'] ?? null;
            $discountPercent = $row['discount_percent'] ?? null;
            $lineTotal = $row['line_total'] ?? null;
            $lineLeadTime = $row['lead_time_days'] ?? null;
            $notes = $row['notes'] ?? null;

            $hasContent = $quotedQty !== null
                || $unitPrice !== null
                || $discountPercent !== null
                || $lineTotal !== null
                || $lineLeadTime !== null
                || $notes !== null;

            if (! $hasContent) {
                continue;
            }

            $normalizedDiscount = $discountPercent !== null ? (float) $discountPercent : 0.0;
            $calculationQty = $quotedQty !== null ? (float) $quotedQty : (float) $requestItem->qty;

            if ($lineTotal === null && $unitPrice !== null) {
                $lineTotal = round(
                    $calculationQty * (float) $unitPrice * (1 - ($normalizedDiscount / 100)),
                    2
                );
            }

            if ($lineTotal !== null) {
                $total += (float) $lineTotal;
            }

            $quote->items()->create([
                'purchase_request_item_id' => $requestItemId,
                'quoted_qty' => $quotedQty,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'line_total' => $lineTotal,
                'lead_time_days' => $lineLeadTime,
                'notes' => $notes,
            ]);
        }

        return round($total, 2);
    }
}
