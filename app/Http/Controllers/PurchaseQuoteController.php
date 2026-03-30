<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchases\StorePurchaseQuoteRequest;
use App\Http\Requests\Purchases\UpdatePurchaseQuoteRequest;
use App\Models\PaymentTerm;
use App\Models\PurchaseQuote;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\SupplierItemReference;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $paymentTerm = ! empty($validated['payment_term_id'])
            ? PaymentTerm::query()->find((int) $validated['payment_term_id'])
            : null;

        $purchaseRequest->loadMissing('items:id,purchase_request_id,item_id,qty');

        $result = DB::transaction(function () use ($purchaseRequest, $validated, $supplier, $paymentTerm, $request) {
            $quote = PurchaseQuote::query()->create([
                'purchase_request_id' => $purchaseRequest->id,
                'supplier_id' => $supplier->id,
                'supplier_name_snapshot' => $supplier->name,
                'supplier_quote_reference' => $validated['supplier_quote_reference'] ?? null,
                'lead_time_days' => $validated['lead_time_days'] ?? null,
                'payment_term_id' => $paymentTerm?->id,
                'payment_term_snapshot' => $this->resolvePaymentTermSnapshot($paymentTerm),
                'total_amount' => 0,
                'currency' => $validated['currency'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $syncResult = $this->syncQuoteItems(
                quote: $quote,
                purchaseRequest: $purchaseRequest,
                supplierId: (int) $supplier->id,
                items: $validated['items'] ?? []
            );

            $quote->update([
                'total_amount' => $syncResult['total_amount'],
                'updated_by' => Auth::id(),
            ]);

            $pdfUploaded = false;
            if ($request->hasFile('quote_pdf')) {
                $pdfUploaded = $this->replaceQuotePdf(
                    quote: $quote,
                    file: $request->file('quote_pdf')
                );
            }

            if ($quote->status === PurchaseQuote::STATUS_SELECTED) {
                PurchaseQuote::query()
                    ->where('purchase_request_id', $purchaseRequest->id)
                    ->whereKeyNot($quote->id)
                    ->where('status', PurchaseQuote::STATUS_SELECTED)
                    ->update([
                        'status' => PurchaseQuote::STATUS_RECEIVED,
                        'updated_by' => Auth::id(),
                    ]);

                $purchaseRequest->update([
                    'status' => PurchaseRequest::STATUS_CLOSED,
                    'updated_by' => Auth::id(),
                ]);
            }

            return [
                'quote' => $quote,
                'sync_result' => $syncResult,
                'pdf_uploaded' => $pdfUploaded,
            ];
        });

        /** @var PurchaseQuote $quote */
        $quote = $result['quote'];

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'purchase_quote',
            entityId: $quote->id,
            payload: [
                'purchase_request_id' => $purchaseRequest->id,
                'purchase_request_code' => $purchaseRequest->code,
                'supplier_id' => $quote->supplier_id,
                'supplier_name' => $quote->supplier_name_snapshot,
                'supplier_quote_reference' => $quote->supplier_quote_reference,
                'payment_term_id' => $quote->payment_term_id,
                'payment_term_snapshot' => $quote->payment_term_snapshot,
                'total_amount' => $quote->total_amount,
                'currency' => $quote->currency,
                'lead_time_days' => $quote->lead_time_days,
                'status' => $quote->status,
                'quoted_lines_count' => $quote->items()->count(),
                'supplier_item_references_synced' => $result['sync_result']['supplier_item_references_synced'],
                'quote_pdf_uploaded' => $result['pdf_uploaded'],
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
        $paymentTerm = ! empty($validated['payment_term_id'])
            ? PaymentTerm::query()->find((int) $validated['payment_term_id'])
            : null;

        $purchaseRequest->loadMissing('items:id,purchase_request_id,item_id,qty');

        $oldData = $quote->only([
            'supplier_id',
            'supplier_name_snapshot',
            'supplier_quote_reference',
            'payment_term_id',
            'payment_term_snapshot',
            'lead_time_days',
            'total_amount',
            'currency',
            'status',
            'notes',
            'quote_pdf_path',
        ]);

        $result = DB::transaction(function () use ($purchaseRequest, $quote, $validated, $supplier, $paymentTerm, $request) {
            $quote->update([
                'supplier_id' => $supplier->id,
                'supplier_name_snapshot' => $supplier->name,
                'supplier_quote_reference' => $validated['supplier_quote_reference'] ?? null,
                'payment_term_id' => $paymentTerm?->id,
                'payment_term_snapshot' => $this->resolvePaymentTermSnapshot($paymentTerm),
                'lead_time_days' => $validated['lead_time_days'] ?? null,
                'currency' => $validated['currency'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            $syncResult = $this->syncQuoteItems(
                quote: $quote,
                purchaseRequest: $purchaseRequest,
                supplierId: (int) $supplier->id,
                items: $validated['items'] ?? []
            );

            $quote->update([
                'total_amount' => $syncResult['total_amount'],
                'updated_by' => Auth::id(),
            ]);

            $pdfUploaded = false;
            if ($request->hasFile('quote_pdf')) {
                $pdfUploaded = $this->replaceQuotePdf(
                    quote: $quote,
                    file: $request->file('quote_pdf')
                );
            }

            if ($quote->status === PurchaseQuote::STATUS_SELECTED) {
                PurchaseQuote::query()
                    ->where('purchase_request_id', $purchaseRequest->id)
                    ->whereKeyNot($quote->id)
                    ->where('status', PurchaseQuote::STATUS_SELECTED)
                    ->update([
                        'status' => PurchaseQuote::STATUS_RECEIVED,
                        'updated_by' => Auth::id(),
                    ]);

                $purchaseRequest->update([
                    'status' => PurchaseRequest::STATUS_CLOSED,
                    'updated_by' => Auth::id(),
                ]);
            }

            return [
                'sync_result' => $syncResult,
                'pdf_uploaded' => $pdfUploaded,
            ];
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
                'supplier_item_references_synced' => $result['sync_result']['supplier_item_references_synced'],
                'quote_pdf_uploaded' => $result['pdf_uploaded'],
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Proposta atualizada com sucesso.');
    }

    public function showPdf(PurchaseRequest $purchaseRequest, PurchaseQuote $quote): StreamedResponse
    {
        $this->authorize('view', $purchaseRequest);

        if ((int) $quote->purchase_request_id !== (int) $purchaseRequest->id) {
            abort(404);
        }

        if (! $quote->quote_pdf_path) {
            abort(404);
        }

        $disk = $quote->quote_pdf_disk ?: 'local';
        if (! Storage::disk($disk)->exists($quote->quote_pdf_path)) {
            abort(404);
        }

        return Storage::disk($disk)->response(
            $quote->quote_pdf_path,
            $quote->quote_pdf_original_name ?: ('proposta-' . $quote->id . '.pdf'),
            [
                'Content-Type' => $quote->quote_pdf_mime_type ?: 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }

    public function removePdf(PurchaseRequest $purchaseRequest, PurchaseQuote $quote): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        if ((int) $quote->purchase_request_id !== (int) $purchaseRequest->id) {
            abort(404);
        }

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Nao e possivel remover anexos num pedido fechado ou cancelado.');
        }

        $hadPdf = $this->clearQuotePdf($quote, true);

        if ($hadPdf) {
            $this->activityLogService->log(
                action: ActivityActions::UPDATED,
                entity: 'purchase_quote',
                entityId: $quote->id,
                payload: [
                    'purchase_request_id' => $purchaseRequest->id,
                    'purchase_request_code' => $purchaseRequest->code,
                    'event' => 'quote_pdf_removed',
                ],
                ownerId: $purchaseRequest->owner_id,
                userId: Auth::id(),
            );
        }

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', $hadPdf ? 'PDF da proposta removido com sucesso.' : 'Esta proposta nao tem PDF anexado.');
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
            'supplier_quote_reference' => $quote->supplier_quote_reference,
            'has_quote_pdf' => $quote->hasQuotePdf(),
        ];

        $this->clearQuotePdf($quote, true);
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
     * @return array{total_amount: float, supplier_item_references_synced: int}
     */
    private function syncQuoteItems(
        PurchaseQuote $quote,
        PurchaseRequest $purchaseRequest,
        int $supplierId,
        array $items
    ): array {
        $requestItems = $purchaseRequest->items->keyBy('id');

        $quote->items()->delete();

        $total = 0.0;
        $supplierItemReferencesSynced = 0;

        foreach ($items as $row) {
            $requestItemId = (int) ($row['purchase_request_item_id'] ?? 0);

            if ($requestItemId <= 0) {
                continue;
            }

            $requestItem = $requestItems->get($requestItemId);
            if (! $requestItem) {
                continue;
            }

            $supplierItemReference = $this->nullableTrim($row['supplier_item_reference'] ?? null);
            $quotedQty = $row['quoted_qty'] ?? null;
            $unitPrice = $row['unit_price'] ?? null;
            $discountPercent = $row['discount_percent'] ?? null;
            $notes = $this->nullableTrim($row['notes'] ?? null);

            $hasOperationalContent = $unitPrice !== null;

            if (! $hasOperationalContent) {
                continue;
            }

            $normalizedDiscount = $discountPercent !== null ? (float) $discountPercent : 0.0;
            $calculationQty = $quotedQty !== null ? (float) $quotedQty : (float) $requestItem->qty;

            $lineTotal = null;
            if ($unitPrice !== null) {
                $lineTotal = round(
                    $calculationQty * (float) $unitPrice * (1 - ($normalizedDiscount / 100)),
                    2
                );

                $total += $lineTotal;
            }

            $quote->items()->create([
                'purchase_request_item_id' => $requestItemId,
                'supplier_item_reference' => $supplierItemReference,
                'quoted_qty' => $calculationQty,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'line_total' => $lineTotal,
                'lead_time_days' => null,
                'notes' => $notes,
            ]);

            if ($supplierItemReference !== null && ! empty($requestItem->item_id)) {
                SupplierItemReference::query()->updateOrCreate(
                    [
                        'supplier_id' => $supplierId,
                        'item_id' => (int) $requestItem->item_id,
                    ],
                    [
                        'supplier_item_reference' => $supplierItemReference,
                    ]
                );

                $supplierItemReferencesSynced++;
            }
        }

        return [
            'total_amount' => round($total, 2),
            'supplier_item_references_synced' => $supplierItemReferencesSynced,
        ];
    }

    private function resolvePaymentTermSnapshot(?PaymentTerm $paymentTerm): ?string
    {
        if (! $paymentTerm) {
            return null;
        }

        return $paymentTerm->displayLabel();
    }

    private function replaceQuotePdf(PurchaseQuote $quote, ?UploadedFile $file): bool
    {
        if (! $file) {
            return false;
        }

        $disk = 'local';
        $folder = 'purchases/quotes/' . $quote->id . '/supplier-pdf';
        $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $generatedFileName = Str::uuid()->toString() . '.' . $extension;

        $storedPath = $file->storeAs($folder, $generatedFileName, $disk);

        $this->clearQuotePdf($quote, true);

        $quote->update([
            'quote_pdf_disk' => $disk,
            'quote_pdf_path' => $storedPath,
            'quote_pdf_original_name' => $file->getClientOriginalName(),
            'quote_pdf_mime_type' => $file->getClientMimeType() ?: 'application/pdf',
            'quote_pdf_file_size' => $file->getSize() ?: 0,
            'updated_by' => Auth::id(),
        ]);

        return true;
    }

    private function clearQuotePdf(PurchaseQuote $quote, bool $deleteFromDisk): bool
    {
        if (! $quote->quote_pdf_path) {
            return false;
        }

        $disk = $quote->quote_pdf_disk ?: 'local';
        $path = $quote->quote_pdf_path;

        if ($deleteFromDisk) {
            Storage::disk($disk)->delete($path);
        }

        $quote->update([
            'quote_pdf_disk' => null,
            'quote_pdf_path' => null,
            'quote_pdf_original_name' => null,
            'quote_pdf_mime_type' => null,
            'quote_pdf_file_size' => null,
            'updated_by' => Auth::id(),
        ]);

        return true;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
