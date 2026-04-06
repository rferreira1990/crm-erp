<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchases\SendPurchaseSupplierOrderReturnEmailRequest;
use App\Http\Requests\Purchases\StorePurchaseSupplierOrderReturnRequest;
use App\Http\Requests\Purchases\UpdatePurchaseSupplierOrderReturnConfirmationRequest;
use App\Mail\PurchaseSupplierOrderReturnMail;
use App\Models\CompanyProfile;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseSupplierOrder;
use App\Models\PurchaseSupplierOrderItem;
use App\Models\PurchaseSupplierOrderReturn;
use App\Models\PurchaseSupplierOrderReturnEmailLog;
use App\Models\StockMovement;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PurchaseSupplierOrderReturnController extends Controller
{
    public function __construct(protected ActivityLogService $activityLogService)
    {
    }

    public function create(PurchaseRequest $purchaseRequest, PurchaseSupplierOrder $order): View
    {
        return $this->createForContext($order, $purchaseRequest);
    }

    public function createDirect(PurchaseSupplierOrder $order): View
    {
        return $this->createForContext($order, null);
    }

    public function show(PurchaseRequest $purchaseRequest, PurchaseSupplierOrder $order, PurchaseSupplierOrderReturn $purchaseReturn): View
    {
        return $this->showForContext($order, $purchaseReturn, $purchaseRequest);
    }

    public function showDirect(PurchaseSupplierOrder $order, PurchaseSupplierOrderReturn $purchaseReturn): View
    {
        return $this->showForContext($order, $purchaseReturn, null);
    }

    public function store(StorePurchaseSupplierOrderReturnRequest $request, PurchaseRequest $purchaseRequest, PurchaseSupplierOrder $order): RedirectResponse
    {
        return $this->storeForContext($request, $order, $purchaseRequest);
    }

    public function storeDirect(StorePurchaseSupplierOrderReturnRequest $request, PurchaseSupplierOrder $order): RedirectResponse
    {
        return $this->storeForContext($request, $order, null);
    }

    public function pdf(PurchaseRequest $purchaseRequest, PurchaseSupplierOrder $order, PurchaseSupplierOrderReturn $purchaseReturn): \Symfony\Component\HttpFoundation\Response
    {
        return $this->pdfForContext($order, $purchaseReturn, $purchaseRequest);
    }

    public function pdfDirect(PurchaseSupplierOrder $order, PurchaseSupplierOrderReturn $purchaseReturn): \Symfony\Component\HttpFoundation\Response
    {
        return $this->pdfForContext($order, $purchaseReturn, null);
    }

    public function sendEmail(
        SendPurchaseSupplierOrderReturnEmailRequest $request,
        PurchaseRequest $purchaseRequest,
        PurchaseSupplierOrder $order,
        PurchaseSupplierOrderReturn $purchaseReturn
    ): RedirectResponse {
        return $this->sendEmailForContext($request, $order, $purchaseReturn, $purchaseRequest);
    }

    public function sendEmailDirect(
        SendPurchaseSupplierOrderReturnEmailRequest $request,
        PurchaseSupplierOrder $order,
        PurchaseSupplierOrderReturn $purchaseReturn
    ): RedirectResponse {
        return $this->sendEmailForContext($request, $order, $purchaseReturn, null);
    }

    public function updateConfirmation(
        UpdatePurchaseSupplierOrderReturnConfirmationRequest $request,
        PurchaseRequest $purchaseRequest,
        PurchaseSupplierOrder $order,
        PurchaseSupplierOrderReturn $purchaseReturn
    ): RedirectResponse {
        return $this->updateConfirmationForContext($request, $order, $purchaseReturn, $purchaseRequest);
    }

    public function updateConfirmationDirect(
        UpdatePurchaseSupplierOrderReturnConfirmationRequest $request,
        PurchaseSupplierOrder $order,
        PurchaseSupplierOrderReturn $purchaseReturn
    ): RedirectResponse {
        return $this->updateConfirmationForContext($request, $order, $purchaseReturn, null);
    }

    public function close(PurchaseRequest $purchaseRequest, PurchaseSupplierOrder $order, PurchaseSupplierOrderReturn $purchaseReturn): RedirectResponse
    {
        return $this->closeForContext($order, $purchaseReturn, $purchaseRequest);
    }

    public function closeDirect(PurchaseSupplierOrder $order, PurchaseSupplierOrderReturn $purchaseReturn): RedirectResponse
    {
        return $this->closeForContext($order, $purchaseReturn, null);
    }

    private function createForContext(PurchaseSupplierOrder $order, ?PurchaseRequest $purchaseRequest): View
    {
        $this->authorizeOrderView($order, $purchaseRequest);
        $this->authorize('viewAny', [PurchaseSupplierOrderReturn::class, $order]);

        $order->load([
            'supplier:id,code,name,email,habitual_order_email,contact_person',
            'paymentTerm:id,name,days',
            'items.item:id,code,name,unit_id,tracks_stock,current_stock',
            'items.item.unit:id,code,name',
            'receipts:id,purchase_supplier_order_id,receipt_number,receipt_date',
            'returns.user:id,name',
            'returns.closedBy:id,name',
            'returns.confirmedBy:id,name',
            'returns.linkedReceipt:id,receipt_number',
            'returns.items:id,purchase_supplier_order_return_id,quantity_returned',
            'returns.emailLogs:id,purchase_supplier_order_return_id,sent_at',
        ]);

        $orderItems = $order->items
            ->sortBy(fn (PurchaseSupplierOrderItem $item) => [(int) $item->sort_order, (int) $item->id])
            ->values();

        return view('purchases.orders.returns.create', [
            'purchaseRequest' => $purchaseRequest,
            'order' => $order,
            'orderItems' => $orderItems,
            'orderStatuses' => PurchaseSupplierOrder::statuses(),
        ]);
    }

    private function showForContext(PurchaseSupplierOrder $order, PurchaseSupplierOrderReturn $purchaseReturn, ?PurchaseRequest $purchaseRequest): View
    {
        $this->authorizeOrderView($order, $purchaseRequest);
        $this->ensureReturnRouteScope($order, $purchaseReturn, $purchaseRequest);
        $this->authorize('view', $purchaseReturn);

        $order->loadMissing([
            'supplier:id,code,name,email,habitual_order_email,contact_person,tax_number,address,postal_code,city,country,phone',
            'paymentTerm:id,name,days',
            'purchaseRequest:id,code,owner_id',
        ]);

        $purchaseReturn->loadMissing([
            'user:id,name',
            'closedBy:id,name',
            'confirmedBy:id,name',
            'linkedReceipt:id,receipt_number,receipt_date',
            'items.orderItem:id,purchase_supplier_order_id,item_id,description,unit_snapshot,sort_order,qty,received_qty,returned_qty',
            'items.orderItem.item:id,code,name,unit_id',
            'items.orderItem.item.unit:id,name,code',
            'emailLogs.sender:id,name',
        ]);

        $companyProfile = CompanyProfile::firstForOwner((int) Auth::id());
        $hasMailConfig = ! empty($companyProfile?->mail_host)
            && ! empty($companyProfile?->mail_port)
            && ! empty($companyProfile?->mail_username)
            && ! empty($companyProfile?->mail_password)
            && ! empty($companyProfile?->mail_encryption)
            && ! empty($companyProfile?->mail_from_address)
            && ! empty($companyProfile?->mail_from_name);

        return view('purchases.orders.returns.show', [
            'purchaseRequest' => $purchaseRequest,
            'order' => $order,
            'purchaseReturn' => $purchaseReturn,
            'companyProfile' => $companyProfile,
            'hasMailConfig' => $hasMailConfig,
            'defaultRecipientName' => old('recipient_name', $order->supplier?->contact_person ?: $order->supplier?->name ?: ''),
            'defaultRecipientEmail' => old('recipient_email', $order->supplier?->habitual_order_email ?: $order->supplier?->email ?: ''),
            'defaultCcEmail' => old('cc_email', $companyProfile?->mail_default_cc ?: ''),
            'defaultBccEmail' => old('bcc_email', $companyProfile?->mail_default_bcc ?: ''),
            'defaultSubject' => old('subject', $this->buildEmailSubject($purchaseReturn)),
            'defaultEmailNotes' => old('email_notes', ''),
        ]);
    }

    private function storeForContext(
        StorePurchaseSupplierOrderReturnRequest $request,
        PurchaseSupplierOrder $order,
        ?PurchaseRequest $purchaseRequest
    ): RedirectResponse {
        $this->authorizeOrderUpdate($order, $purchaseRequest);
        $this->authorize('create', [PurchaseSupplierOrderReturn::class, $order]);

        if (! $order->hasReturnableQty()) {
            return $this->redirectToReturnCreate($order, $purchaseRequest)
                ->with('error', 'Nao existem quantidades disponiveis para devolucao nesta encomenda.');
        }

        $validated = $request->validated();
        $returnDate = $validated['return_date'];
        $linkedReceiptId = (int) ($validated['purchase_supplier_order_receipt_id'] ?? 0);
        $notes = $validated['notes'] ?? null;

        $positiveQuantities = collect($validated['quantities'] ?? [])
            ->mapWithKeys(fn (mixed $qty, mixed $lineId): array => [(int) $lineId => round((float) $qty, 3)])
            ->filter(fn (float $qty): bool => $qty > 0);

        if ($positiveQuantities->isEmpty()) {
            return $this->redirectToReturnCreate($order, $purchaseRequest)
                ->with('error', 'Indica pelo menos uma quantidade para devolucao.');
        }

        $return = null;
        $stockMovementsCount = 0;
        $totalReturnedQty = 0.0;

        DB::transaction(function () use (
            $order,
            $validated,
            $returnDate,
            $linkedReceiptId,
            $notes,
            &$return,
            &$stockMovementsCount,
            &$totalReturnedQty
        ): void {
            $lockedOrder = PurchaseSupplierOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lockedItems = PurchaseSupplierOrderItem::query()
                ->where('purchase_supplier_order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($linkedReceiptId > 0) {
                $receiptExists = $lockedOrder->receipts()->whereKey($linkedReceiptId)->lockForUpdate()->exists();
                if (! $receiptExists) {
                    throw ValidationException::withMessages([
                        'purchase_supplier_order_receipt_id' => 'A rececao selecionada nao pertence a esta encomenda.',
                    ]);
                }
            }

            $quantities = collect($validated['quantities'] ?? [])
                ->mapWithKeys(fn (mixed $qty, mixed $lineId): array => [(int) $lineId => round((float) $qty, 3)])
                ->filter(fn (float $qty): bool => $qty > 0);
            $reasons = collect($validated['reasons'] ?? [])
                ->mapWithKeys(fn (mixed $reason, mixed $lineId): array => [(int) $lineId => trim((string) $reason)]);

            if ($quantities->isEmpty()) {
                throw ValidationException::withMessages([
                    'quantities' => 'Indica pelo menos uma linha com quantidade a devolver.',
                ]);
            }

            foreach ($quantities as $lineId => $qtyNow) {
                /** @var PurchaseSupplierOrderItem|null $lockedLine */
                $lockedLine = $lockedItems->get((int) $lineId);
                if (! $lockedLine) {
                    throw ValidationException::withMessages(['quantities.' . $lineId => 'Linha de encomenda invalida.']);
                }

                if ($qtyNow - $lockedLine->returnableQty() > 0.0005) {
                    throw ValidationException::withMessages([
                        'quantities.' . $lineId => 'A quantidade devolvida excede o disponivel para devolucao.',
                    ]);
                }
            }

            $return = PurchaseSupplierOrderReturn::query()->create([
                'owner_id' => (int) $lockedOrder->owner_id,
                'purchase_supplier_order_id' => $lockedOrder->id,
                'purchase_supplier_order_receipt_id' => $linkedReceiptId > 0 ? $linkedReceiptId : null,
                'return_number' => 'PENDING-' . uniqid(),
                'return_date' => $returnDate,
                'user_id' => (int) Auth::id(),
                'notes' => $notes,
                'status' => PurchaseSupplierOrderReturn::STATUS_OPEN,
                'supplier_confirmation_status' => PurchaseSupplierOrderReturn::CONFIRMATION_PENDING,
            ]);

            $return->update([
                'return_number' => 'DEV-' . $return->return_date->format('Y') . '-' . str_pad((string) $return->id, 6, '0', STR_PAD_LEFT),
            ]);
            $lockedStockItems = [];
            foreach ($quantities as $lineId => $qtyNow) {
                /** @var PurchaseSupplierOrderItem $lockedLine */
                $lockedLine = $lockedItems->get((int) $lineId);
                $lockedLine->update(['returned_qty' => round((float) $lockedLine->returned_qty + (float) $qtyNow, 3)]);

                $reason = $reasons->get((int) $lineId);
                $returnItem = $return->items()->create([
                    'owner_id' => (int) $lockedOrder->owner_id,
                    'purchase_supplier_order_item_id' => $lockedLine->id,
                    'item_id' => $lockedLine->item_id,
                    'quantity_returned' => $qtyNow,
                    'reason' => $reason !== '' ? $reason : null,
                ]);

                $totalReturnedQty = round($totalReturnedQty + (float) $qtyNow, 3);

                if (! $lockedLine->item_id) {
                    continue;
                }
                if (! isset($lockedStockItems[(int) $lockedLine->item_id])) {
                    $lockedStockItems[(int) $lockedLine->item_id] = Item::query()->lockForUpdate()->find((int) $lockedLine->item_id);
                }

                /** @var Item|null $item */
                $item = $lockedStockItems[(int) $lockedLine->item_id];
                if (! $item || ! $item->tracks_stock) {
                    continue;
                }

                $stockBefore = round((float) $item->current_stock, 3);
                $stockAfter = round($stockBefore - (float) $qtyNow, 3);
                if ($stockAfter < -0.0005) {
                    throw ValidationException::withMessages([
                        'quantities.' . $lineId => 'Stock insuficiente para devolver esta quantidade do artigo ' . $item->code . '.',
                    ]);
                }

                $stockAfter = max(0, $stockAfter);
                $item->update(['current_stock' => $stockAfter]);

                StockMovement::query()->create([
                    'item_id' => $item->id,
                    'movement_type' => StockMovement::TYPE_PURCHASE_RETURN,
                    'direction' => StockMovement::DIRECTION_OUT,
                    'quantity' => $qtyNow,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'occurred_at' => now(),
                    'source_type' => 'purchase_return',
                    'source_id' => $returnItem->id,
                    'notes' => 'Devolucao da encomenda ' . $lockedOrder->id . ' / ' . $return->return_number,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                $stockMovementsCount++;
            }
        });

        if (! $return instanceof PurchaseSupplierOrderReturn) {
            return $this->redirectToReturnCreate($order, $purchaseRequest)->with('error', 'Nao foi possivel registar a devolucao.');
        }

        $payload = [
            'source_type' => $order->source_type,
            'purchase_supplier_order_id' => $order->id,
            'return_number' => $return->return_number,
            'return_date' => optional($return->return_date)->format('Y-m-d'),
            'linked_receipt_id' => $return->purchase_supplier_order_receipt_id,
            'total_returned_qty' => $totalReturnedQty,
            'stock_movements_count' => $stockMovementsCount,
            'notes' => $return->notes,
        ];
        if ($purchaseRequest) {
            $payload['purchase_request_id'] = $purchaseRequest->id;
            $payload['purchase_request_code'] = $purchaseRequest->code;
        }

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'purchase_supplier_order_return',
            entityId: $return->id,
            payload: $payload,
            ownerId: (int) $order->owner_id,
            userId: Auth::id(),
        );

        return $this->redirectToReturnCreate($order, $purchaseRequest)->with('success', 'Devolucao registada com sucesso.');
    }

    private function pdfForContext(PurchaseSupplierOrder $order, PurchaseSupplierOrderReturn $purchaseReturn, ?PurchaseRequest $purchaseRequest): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeOrderView($order, $purchaseRequest);
        $this->ensureReturnRouteScope($order, $purchaseReturn, $purchaseRequest);
        $this->authorize('view', $purchaseReturn);

        $order->loadMissing([
            'supplier:id,code,name,tax_number,email,habitual_order_email,address,postal_code,city,country,contact_person,phone',
            'paymentTerm:id,name,days',
            'purchaseRequest:id,code,owner_id',
        ]);
        $purchaseReturn->loadMissing([
            'user:id,name',
            'closedBy:id,name',
            'confirmedBy:id,name',
            'linkedReceipt:id,receipt_number,receipt_date',
            'items.orderItem:id,purchase_supplier_order_id,item_id,description,unit_snapshot,sort_order',
            'items.orderItem.item:id,code,name,unit_id',
            'items.orderItem.item.unit:id,name,code',
        ]);

        $companyProfile = CompanyProfile::firstForOwner((int) Auth::id());
        $pdf = $this->makeReturnPdf($purchaseRequest, $order, $purchaseReturn, $companyProfile);

        $payload = [
            'source_type' => $order->source_type,
            'purchase_supplier_order_id' => $order->id,
            'event' => 'return_pdf_generated',
            'return_number' => $purchaseReturn->return_number,
        ];
        if ($purchaseRequest) {
            $payload['purchase_request_id'] = $purchaseRequest->id;
            $payload['purchase_request_code'] = $purchaseRequest->code;
        }

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'purchase_supplier_order_return',
            entityId: $purchaseReturn->id,
            payload: $payload,
            ownerId: (int) $order->owner_id,
            userId: Auth::id(),
        );

        return $pdf->stream('devolucao-' . $purchaseReturn->return_number . '.pdf');
    }

    private function sendEmailForContext(
        SendPurchaseSupplierOrderReturnEmailRequest $request,
        PurchaseSupplierOrder $order,
        PurchaseSupplierOrderReturn $purchaseReturn,
        ?PurchaseRequest $purchaseRequest
    ): RedirectResponse {
        $this->authorizeOrderUpdate($order, $purchaseRequest);
        $this->ensureReturnRouteScope($order, $purchaseReturn, $purchaseRequest);
        $this->authorize('sendEmail', $purchaseReturn);

        $order->loadMissing([
            'supplier:id,code,name,email,habitual_order_email,contact_person,tax_number,address,postal_code,city,country,phone',
            'paymentTerm:id,name,days',
            'purchaseRequest:id,code,owner_id',
        ]);
        $purchaseReturn->loadMissing([
            'user:id,name',
            'closedBy:id,name',
            'confirmedBy:id,name',
            'linkedReceipt:id,receipt_number,receipt_date',
            'items.orderItem:id,purchase_supplier_order_id,item_id,description,unit_snapshot,sort_order',
            'items.orderItem.item:id,code,name,unit_id',
            'items.orderItem.item.unit:id,name,code',
            'emailLogs:id,purchase_supplier_order_return_id,sent_at',
        ]);

        $companyProfile = CompanyProfile::firstForOwner((int) Auth::id());
        if (! $companyProfile) {
            return $this->redirectToReturnShow($order, $purchaseReturn, $purchaseRequest)
                ->with('error', 'Nao existem dados da empresa configurados.');
        }

        $requiredMailFields = [
            'mail_host' => $companyProfile->mail_host,
            'mail_port' => $companyProfile->mail_port,
            'mail_username' => $companyProfile->mail_username,
            'mail_password' => $companyProfile->mail_password,
            'mail_encryption' => $companyProfile->mail_encryption,
            'mail_from_address' => $companyProfile->mail_from_address,
            'mail_from_name' => $companyProfile->mail_from_name,
        ];
        foreach ($requiredMailFields as $field => $value) {
            if (empty($value)) {
                return $this->redirectToReturnShow($order, $purchaseReturn, $purchaseRequest)
                    ->with('error', 'Falta configurar o campo de email da empresa: ' . $field . '.')
                    ->with('open_send_return_email_modal', true)
                    ->withInput();
            }
        }

        $validated = $request->validated();
        $recipientName = trim((string) ($validated['recipient_name'] ?? ''));
        $recipientEmail = trim((string) ($validated['recipient_email'] ?? ''));
        $ccEmail = trim((string) ($validated['cc_email'] ?? ''));
        $bccEmail = trim((string) ($validated['bcc_email'] ?? ''));
        $emailNotes = trim((string) ($validated['email_notes'] ?? ''));
        $subject = trim((string) ($validated['subject'] ?? ''));
        $isResend = (bool) ($validated['is_resend'] ?? false) || $purchaseReturn->emailLogs->isNotEmpty();

        try {
            $pdfContent = $this->makeReturnPdf($purchaseRequest, $order, $purchaseReturn, $companyProfile)->output();

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $companyProfile->mail_host,
                'mail.mailers.smtp.port' => (int) $companyProfile->mail_port,
                'mail.mailers.smtp.encryption' => $companyProfile->mail_encryption,
                'mail.mailers.smtp.username' => $companyProfile->mail_username,
                'mail.mailers.smtp.password' => $companyProfile->mail_password,
                'mail.from.address' => $companyProfile->mail_from_address,
                'mail.from.name' => $companyProfile->mail_from_name,
            ]);
            app('mail.manager')->forgetMailers();

            $pendingMail = Mail::mailer('smtp')->to($recipientEmail, $recipientName !== '' ? $recipientName : null);
            if ($ccEmail !== '') {
                $pendingMail->cc($ccEmail);
            }
            if ($bccEmail !== '') {
                $pendingMail->bcc($bccEmail);
            }
            $pendingMail->send(new PurchaseSupplierOrderReturnMail(
                purchaseRequest: $purchaseRequest,
                order: $order,
                purchaseReturn: $purchaseReturn,
                pdfContent: $pdfContent,
                pdfFileName: 'devolucao-' . $purchaseReturn->return_number . '.pdf',
                fromAddress: $companyProfile->mail_from_address,
                fromName: $companyProfile->mail_from_name,
                subjectLine: $subject,
                recipientName: $recipientName,
                emailNotes: $emailNotes,
                companyProfile: $companyProfile,
            ));

            PurchaseSupplierOrderReturnEmailLog::query()->create([
                'owner_id' => (int) $order->owner_id,
                'purchase_supplier_order_return_id' => $purchaseReturn->id,
                'user_id' => Auth::id(),
                'recipient_name' => $recipientName !== '' ? $recipientName : null,
                'recipient_email' => $recipientEmail,
                'cc_email' => $ccEmail !== '' ? $ccEmail : null,
                'bcc_email' => $bccEmail !== '' ? $bccEmail : null,
                'subject' => $subject,
                'body_snapshot' => $emailNotes !== '' ? $emailNotes : null,
                'is_resend' => $isResend,
                'sent_at' => now(),
            ]);

            $payload = [
                'source_type' => $order->source_type,
                'purchase_supplier_order_id' => $order->id,
                'return_number' => $purchaseReturn->return_number,
                'recipient_name' => $recipientName !== '' ? $recipientName : null,
                'recipient_email' => $recipientEmail,
                'cc_email' => $ccEmail !== '' ? $ccEmail : null,
                'bcc_email' => $bccEmail !== '' ? $bccEmail : null,
                'subject' => $subject,
                'message' => $emailNotes !== '' ? $emailNotes : null,
                'is_resend' => $isResend,
            ];
            if ($purchaseRequest) {
                $payload['purchase_request_id'] = $purchaseRequest->id;
                $payload['purchase_request_code'] = $purchaseRequest->code;
            }

            $this->activityLogService->log(
                action: ActivityActions::EMAIL_SENT,
                entity: 'purchase_supplier_order_return',
                entityId: $purchaseReturn->id,
                payload: $payload,
                ownerId: (int) $order->owner_id,
                userId: Auth::id(),
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectToReturnShow($order, $purchaseReturn, $purchaseRequest)
                ->with('error', 'Ocorreu um erro ao enviar o email. Verifica a configuracao SMTP e tenta novamente.')
                ->with('open_send_return_email_modal', true)
                ->withInput();
        }

        return $this->redirectToReturnShow($order, $purchaseReturn, $purchaseRequest)
            ->with('success', 'Devolucao enviada por email com sucesso.');
    }

    private function updateConfirmationForContext(
        UpdatePurchaseSupplierOrderReturnConfirmationRequest $request,
        PurchaseSupplierOrder $order,
        PurchaseSupplierOrderReturn $purchaseReturn,
        ?PurchaseRequest $purchaseRequest
    ): RedirectResponse {
        $this->authorizeOrderUpdate($order, $purchaseRequest);
        $this->ensureReturnRouteScope($order, $purchaseReturn, $purchaseRequest);
        $this->authorize('updateConfirmation', $purchaseReturn);

        $validated = $request->validated();
        $oldStatus = (string) $purchaseReturn->supplier_confirmation_status;
        $newStatus = (string) $validated['supplier_confirmation_status'];
        $confirmationNotes = $validated['confirmation_notes'] ?? null;

        DB::transaction(function () use ($purchaseReturn, $newStatus, $confirmationNotes): void {
            $lockedReturn = PurchaseSupplierOrderReturn::query()->whereKey($purchaseReturn->id)->lockForUpdate()->firstOrFail();

            $updatePayload = [
                'supplier_confirmation_status' => $newStatus,
                'confirmation_notes' => $confirmationNotes,
            ];
            if ($newStatus === PurchaseSupplierOrderReturn::CONFIRMATION_PENDING) {
                $updatePayload['confirmation_at'] = null;
                $updatePayload['confirmed_by'] = null;
            } else {
                $updatePayload['confirmation_at'] = now();
                $updatePayload['confirmed_by'] = Auth::id();
            }

            $lockedReturn->update($updatePayload);
        });

        $purchaseReturn->refresh();
        $payload = [
            'source_type' => $order->source_type,
            'purchase_supplier_order_id' => $order->id,
            'return_number' => $purchaseReturn->return_number,
            'old_supplier_confirmation_status' => $oldStatus,
            'new_supplier_confirmation_status' => $newStatus,
            'confirmation_at' => optional($purchaseReturn->confirmation_at)->format('Y-m-d H:i:s'),
            'confirmed_by' => $purchaseReturn->confirmed_by,
            'confirmation_notes' => $purchaseReturn->confirmation_notes,
        ];
        if ($purchaseRequest) {
            $payload['purchase_request_id'] = $purchaseRequest->id;
            $payload['purchase_request_code'] = $purchaseRequest->code;
        }

        $this->activityLogService->log(
            action: ActivityActions::STATUS_CHANGED,
            entity: 'purchase_supplier_order_return',
            entityId: $purchaseReturn->id,
            payload: $payload,
            ownerId: (int) $order->owner_id,
            userId: Auth::id(),
        );

        return $this->redirectToReturnShow($order, $purchaseReturn, $purchaseRequest)
            ->with('success', 'Confirmacao do fornecedor atualizada com sucesso.');
    }

    private function closeForContext(PurchaseSupplierOrder $order, PurchaseSupplierOrderReturn $purchaseReturn, ?PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorizeOrderUpdate($order, $purchaseRequest);
        $this->ensureReturnRouteScope($order, $purchaseReturn, $purchaseRequest);
        $this->authorize('close', $purchaseReturn);

        if ($purchaseReturn->isClosed()) {
            return $this->redirectToReturnShow($order, $purchaseReturn, $purchaseRequest)
                ->with('error', 'A devolucao ja se encontra fechada.');
        }

        $alreadyClosed = false;
        DB::transaction(function () use ($purchaseReturn, &$alreadyClosed): void {
            $lockedReturn = PurchaseSupplierOrderReturn::query()->whereKey($purchaseReturn->id)->lockForUpdate()->firstOrFail();
            if ($lockedReturn->isClosed()) {
                $alreadyClosed = true;

                return;
            }
            $lockedReturn->update([
                'status' => PurchaseSupplierOrderReturn::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by' => Auth::id(),
            ]);
        });

        if ($alreadyClosed) {
            return $this->redirectToReturnShow($order, $purchaseReturn, $purchaseRequest)
                ->with('error', 'A devolucao ja se encontra fechada.');
        }

        $purchaseReturn->refresh();

        $payload = [
            'source_type' => $order->source_type,
            'purchase_supplier_order_id' => $order->id,
            'return_number' => $purchaseReturn->return_number,
            'old_status' => PurchaseSupplierOrderReturn::STATUS_OPEN,
            'new_status' => PurchaseSupplierOrderReturn::STATUS_CLOSED,
            'closed_at' => optional($purchaseReturn->closed_at)->format('Y-m-d H:i:s'),
            'closed_by' => $purchaseReturn->closed_by,
        ];
        if ($purchaseRequest) {
            $payload['purchase_request_id'] = $purchaseRequest->id;
            $payload['purchase_request_code'] = $purchaseRequest->code;
        }

        $this->activityLogService->log(
            action: ActivityActions::STATUS_CHANGED,
            entity: 'purchase_supplier_order_return',
            entityId: $purchaseReturn->id,
            payload: $payload,
            ownerId: (int) $order->owner_id,
            userId: Auth::id(),
        );

        return $this->redirectToReturnShow($order, $purchaseReturn, $purchaseRequest)->with('success', 'Devolucao fechada com sucesso.');
    }

    private function buildEmailSubject(PurchaseSupplierOrderReturn $purchaseReturn): string
    {
        return 'Devolucao a fornecedor ' . $purchaseReturn->return_number;
    }

    private function authorizeOrderView(PurchaseSupplierOrder $order, ?PurchaseRequest $purchaseRequest): void
    {
        if ($purchaseRequest) {
            $this->authorize('view', $purchaseRequest);
            $this->ensureOrderRouteScope($purchaseRequest, $order);

            return;
        }

        $this->authorize('view', $order);
        $this->ensureDirectOrderScope($order);
    }

    private function authorizeOrderUpdate(PurchaseSupplierOrder $order, ?PurchaseRequest $purchaseRequest): void
    {
        if ($purchaseRequest) {
            $this->authorize('update', $purchaseRequest);
            $this->ensureOrderRouteScope($purchaseRequest, $order);

            return;
        }
        $this->authorize('update', $order);
        $this->ensureDirectOrderScope($order);
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

    private function ensureDirectOrderScope(PurchaseSupplierOrder $order): void
    {
        abort_if((int) $order->owner_id !== (int) Auth::id(), 404);
    }

    private function ensureReturnRouteScope(PurchaseSupplierOrder $order, PurchaseSupplierOrderReturn $purchaseReturn, ?PurchaseRequest $purchaseRequest): void
    {
        if ((int) $purchaseReturn->purchase_supplier_order_id !== (int) $order->id) {
            abort(404);
        }

        if ($purchaseRequest) {
            if ((int) $purchaseReturn->owner_id !== (int) $purchaseRequest->owner_id) {
                abort(404);
            }

            return;
        }

        if ((int) $purchaseReturn->owner_id !== (int) $order->owner_id) {
            abort(404);
        }
    }

    private function redirectToReturnCreate(PurchaseSupplierOrder $order, ?PurchaseRequest $purchaseRequest): RedirectResponse
    {
        if ($purchaseRequest) {
            return redirect()->route('purchase-requests.supplier-orders.returns.create', [$purchaseRequest, $order]);
        }

        return redirect()->route('purchase-orders.returns.create', $order);
    }

    private function redirectToReturnShow(PurchaseSupplierOrder $order, PurchaseSupplierOrderReturn $purchaseReturn, ?PurchaseRequest $purchaseRequest): RedirectResponse
    {
        if ($purchaseRequest) {
            return redirect()->route('purchase-requests.supplier-orders.returns.show', [$purchaseRequest, $order, $purchaseReturn]);
        }

        return redirect()->route('purchase-orders.returns.show', [$order, $purchaseReturn]);
    }

    private function makeReturnPdf(
        ?PurchaseRequest $purchaseRequest,
        PurchaseSupplierOrder $order,
        PurchaseSupplierOrderReturn $purchaseReturn,
        ?CompanyProfile $companyProfile
    ) {
        return Pdf::loadView('purchases.orders.returns.pdf', [
            'purchaseRequest' => $purchaseRequest,
            'order' => $order,
            'purchaseReturn' => $purchaseReturn,
            'companyProfile' => $companyProfile,
        ])->setPaper('a4', 'portrait');
    }
}

