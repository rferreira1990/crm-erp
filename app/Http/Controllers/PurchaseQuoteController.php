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

        $quote = DB::transaction(function () use ($purchaseRequest, $validated, $supplier) {
            $quote = PurchaseQuote::query()->create([
                'purchase_request_id' => $purchaseRequest->id,
                'supplier_id' => $supplier->id,
                'supplier_name_snapshot' => $supplier->name,
                'lead_time_days' => $validated['lead_time_days'] ?? null,
                'payment_term_snapshot' => $validated['payment_term_snapshot'] ?? null,
                'valid_until' => $validated['valid_until'] ?? null,
                'total_amount' => $validated['total_amount'],
                'currency' => $validated['currency'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
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
                'total_amount' => $validated['total_amount'],
                'currency' => $validated['currency'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
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
}

