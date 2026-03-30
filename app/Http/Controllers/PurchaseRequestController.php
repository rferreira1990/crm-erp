<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchases\StorePurchaseRequestRequest;
use App\Http\Requests\Purchases\StorePurchaseRequestAwardRequest;
use App\Http\Requests\Purchases\UpdatePurchaseRequestRequest;
use App\Mail\PurchaseRequestMail;
use App\Models\CompanyProfile;
use App\Models\Item;
use App\Models\PaymentTerm;
use App\Models\PurchaseQuote;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAward;
use App\Models\PurchaseRequestEmailLog;
use App\Models\Supplier;
use App\Models\SupplierItemReference;
use App\Models\Work;
use App\Services\ActivityLogService;
use App\Services\Purchases\PurchaseRequestAwardService;
use App\Services\Purchases\RfqComparisonService;
use App\Support\ActivityActions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PurchaseRequestController extends Controller
{
    public const EMAIL_ATTACHMENT_MAX_KB = 5120;

    public function __construct(
        protected ActivityLogService $activityLogService,
        protected RfqComparisonService $rfqComparisonService,
        protected PurchaseRequestAwardService $purchaseRequestAwardService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => trim((string) $request->input('status', '')),
            'deadline_from' => trim((string) $request->input('deadline_from', '')),
            'deadline_to' => trim((string) $request->input('deadline_to', '')),
        ];

        $purchaseRequests = PurchaseRequest::query()
            ->with(['work:id,code,name'])
            ->withCount(['items', 'quotes'])
            ->search($filters['search'])
            ->when($filters['status'] !== '', function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['deadline_from'] !== '', function ($query) use ($filters) {
                $query->whereDate('deadline_at', '>=', $filters['deadline_from']);
            })
            ->when($filters['deadline_to'] !== '', function ($query) use ($filters) {
                $query->whereDate('deadline_at', '<=', $filters['deadline_to']);
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('purchases.requests.index', [
            'purchaseRequests' => $purchaseRequests,
            'filters' => $filters,
            'statuses' => PurchaseRequest::statuses(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseRequest::class);

        return view('purchases.requests.create', [
            'purchaseRequest' => new PurchaseRequest([
                'status' => PurchaseRequest::STATUS_DRAFT,
            ]),
            'works' => $this->availableWorks(),
            'itemsCatalog' => $this->availableItems(),
            'statuses' => PurchaseRequest::statuses(),
        ]);
    }

    public function store(StorePurchaseRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseRequest::class);

        $validated = $request->validated();
        $resolvedTitle = $this->resolveTitle($validated);

        $purchaseRequest = DB::transaction(function () use ($validated, $resolvedTitle) {
            $purchaseRequest = PurchaseRequest::query()->create([
                'owner_id' => Auth::id(),
                'title' => $resolvedTitle,
                'work_id' => $validated['work_id'] ?? null,
                'deadline_at' => $validated['deadline_at'] ?? null,
                'status' => PurchaseRequest::STATUS_DRAFT,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->syncItems($purchaseRequest, $validated['items']);

            return $purchaseRequest;
        });

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'purchase_request',
            entityId: $purchaseRequest->id,
            payload: [
                'code' => $purchaseRequest->code,
                'title' => $purchaseRequest->title,
                'status' => $purchaseRequest->status,
                'work_id' => $purchaseRequest->work_id,
                'items_count' => $purchaseRequest->items()->count(),
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Pedido de cotacao criado com sucesso.');
    }

    public function show(PurchaseRequest $purchaseRequest): View
    {
        $this->authorize('view', $purchaseRequest);

        $purchaseRequest->load([
            'work:id,code,name,status',
            'items.item:id,code,name,unit_id',
            'items.item.unit:id,name,code',
            'quotes.items',
            'quotes.paymentTerm:id,name,days',
            'quotes.supplier:id,name,code,email,contact_person,habitual_order_email',
            'quotes.supplier.catalogFiles:id,supplier_id,type,original_name',
            'quotes.creator:id,name',
            'creator:id,name',
            'updater:id,name',
            'emailLogs.sender:id,name',
            'activeAward.decidedBy:id,name',
            'activeAward.forcedSupplier:id,name,code',
            'activeAward.selectedQuote:id,supplier_id,supplier_name_snapshot,total_amount,currency',
            'activeAward.items',
            'activeAward.preparedOrders.supplier:id,name,code',
            'activeAward.preparedOrders.paymentTerm:id,name,days',
            'activeAward.preparedOrders.items',
        ]);

        $comparison = $this->rfqComparisonService->build($purchaseRequest);
        $awardPreview = $this->purchaseRequestAwardService->buildPreview($purchaseRequest);

        $companyProfile = CompanyProfile::query()
            ->orderBy('id')
            ->first();

        $hasMailConfig = ! empty($companyProfile?->mail_host)
            && ! empty($companyProfile?->mail_port)
            && ! empty($companyProfile?->mail_username)
            && ! empty($companyProfile?->mail_password)
            && ! empty($companyProfile?->mail_encryption)
            && ! empty($companyProfile?->mail_from_address)
            && ! empty($companyProfile?->mail_from_name);

        return view('purchases.requests.show', [
            'purchaseRequest' => $purchaseRequest,
            'comparisonQuotes' => $comparison['quotes'],
            'comparisonRows' => $comparison['rows'],
            'summaryByQuoteId' => $comparison['summaryByQuoteId'],
            'bestPriceQuoteId' => $comparison['bestPriceQuoteId'],
            'bestLeadQuoteId' => $comparison['bestLeadQuoteId'],
            'selectedQuoteId' => $comparison['selectedQuoteId'],
            'statuses' => PurchaseRequest::statuses(),
            'quoteStatuses' => PurchaseQuote::statuses(),
            'suppliers' => Supplier::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'email', 'contact_person', 'habitual_order_email']),
            'paymentTerms' => PaymentTerm::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'days']),
            'supplierItemReferenceMap' => $this->supplierItemReferenceMap($purchaseRequest),
            'companyProfile' => $companyProfile,
            'hasMailConfig' => $hasMailConfig,
            'emailAttachmentMaxMb' => max(1, (int) ceil(self::EMAIL_ATTACHMENT_MAX_KB / 1024)),
            'awardModes' => PurchaseRequestAward::modes(),
            'awardPreview' => $awardPreview,
        ]);
    }

    public function edit(PurchaseRequest $purchaseRequest): View|RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Apenas pedidos em rascunho ou enviados podem ser editados.');
        }

        $purchaseRequest->load(['items']);

        return view('purchases.requests.edit', [
            'purchaseRequest' => $purchaseRequest,
            'works' => $this->availableWorks(),
            'itemsCatalog' => $this->availableItems(),
            'statuses' => PurchaseRequest::statuses(),
        ]);
    }

    public function award(StorePurchaseRequestAwardRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        if (! ($request->user()?->can('purchases.award') ?? false)) {
            abort(403);
        }

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Nao e possivel adjudicar num pedido fechado ou cancelado.');
        }

        try {
            $award = $this->purchaseRequestAwardService->award(
                purchaseRequest: $purchaseRequest,
                user: $request->user(),
                data: $request->validated(),
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->withErrors($exception->errors())
                ->withInput()
                ->with('open_award_modal', $request->input('mode'));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Ocorreu um erro ao adjudicar o RFQ.')
                ->withInput()
                ->with('open_award_modal', $request->input('mode'));
        }

        $this->activityLogService->log(
            action: ActivityActions::AWARDED,
            entity: 'purchase_request',
            entityId: $purchaseRequest->id,
            payload: [
                'code' => $purchaseRequest->code,
                'award_id' => $award->id,
                'mode' => $award->mode,
                'mode_label' => $award->modeLabel(),
                'forced_supplier_id' => $award->forced_supplier_id,
                'selected_quote_id' => $award->selected_quote_id,
                'justification' => $award->justification,
                'allow_partial' => $award->allow_partial,
                'generated_orders_count' => $award->generated_orders_count,
                'generated_items_count' => $award->generated_items_count,
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Adjudicacao registada e encomenda(s) preparadas com sucesso.');
    }

    public function update(UpdatePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Apenas pedidos em rascunho ou enviados podem ser editados.');
        }

        $validated = $request->validated();
        $oldData = $purchaseRequest->only([
            'title',
            'work_id',
            'deadline_at',
            'status',
            'notes',
        ]);

        $newStatus = $validated['status'] ?? $purchaseRequest->status;

        if (
            $newStatus !== $purchaseRequest->status
            && ! $purchaseRequest->canChangeTo($newStatus)
        ) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Transicao de estado invalida para este pedido.');
        }

        DB::transaction(function () use ($purchaseRequest, $validated, $newStatus) {
            $purchaseRequest->update([
                'title' => $this->resolveTitle($validated, $purchaseRequest),
                'work_id' => $validated['work_id'] ?? null,
                'deadline_at' => $validated['deadline_at'] ?? null,
                'status' => $newStatus,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            $this->syncItems($purchaseRequest, $validated['items']);
        });

        $purchaseRequest->refresh();

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'purchase_request',
            entityId: $purchaseRequest->id,
            payload: [
                'code' => $purchaseRequest->code,
                'old' => $oldData,
                'new' => $purchaseRequest->only(array_keys($oldData)),
                'items_count' => $purchaseRequest->items()->count(),
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Pedido de cotacao atualizado com sucesso.');
    }

    public function changeStatus(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(PurchaseRequest::statuses()))],
        ]);

        $newStatus = (string) $validated['status'];

        if (! $purchaseRequest->canChangeTo($newStatus)) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Transicao de estado invalida para este pedido.');
        }

        $oldStatus = $purchaseRequest->status;

        $attributes = [
            'status' => $newStatus,
            'updated_by' => Auth::id(),
        ];

        if ($newStatus === PurchaseRequest::STATUS_SENT && ! $purchaseRequest->sent_at) {
            $attributes['sent_at'] = now();
        }

        $purchaseRequest->update($attributes);

        $this->activityLogService->log(
            action: ActivityActions::STATUS_CHANGED,
            entity: 'purchase_request',
            entityId: $purchaseRequest->id,
            payload: [
                'code' => $purchaseRequest->code,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Estado do pedido atualizado com sucesso.');
    }

    public function pdf(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->authorize('view', $purchaseRequest);

        $supplier = null;

        $supplierId = (int) $request->query('supplier_id', 0);
        if ($supplierId > 0) {
            $supplier = Supplier::query()
                ->where('is_active', true)
                ->find($supplierId);
        }

        $purchaseRequest->load([
            'work:id,code,name',
            'items.item:id,code,name,unit_id',
            'items.item.unit:id,name,code',
        ]);

        $companyProfile = CompanyProfile::query()
            ->orderBy('id')
            ->first();

        $pdf = $this->makeRfqPdf(
            purchaseRequest: $purchaseRequest,
            companyProfile: $companyProfile,
            supplier: $supplier,
        );

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'purchase_request',
            entityId: $purchaseRequest->id,
            payload: [
                'code' => $purchaseRequest->code,
                'event' => 'pdf_generated',
                'supplier_id' => $supplier?->id,
                'supplier_name' => $supplier?->name,
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return $pdf->stream($purchaseRequest->code . '.pdf');
    }

    public function sendEmail(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        if ($purchaseRequest->status === PurchaseRequest::STATUS_CANCELLED) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Nao e possivel enviar por email um RFQ cancelado.');
        }

        $purchaseRequest->load([
            'work:id,code,name',
            'items.item:id,code,name,unit_id',
            'items.item.unit:id,name,code',
        ]);

        $companyProfile = CompanyProfile::query()
            ->orderBy('id')
            ->first();

        if (! $companyProfile) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
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
                return redirect()
                    ->route('purchase-requests.show', $purchaseRequest)
                    ->with('error', 'Falta configurar o campo de email da empresa: ' . $field . '.')
                    ->with('open_send_email_modal', true);
            }
        }

        $supplier = null;
        $supplierId = (int) $request->input('supplier_id', 0);

        if ($supplierId > 0) {
            $supplier = Supplier::query()->find($supplierId);
        }

        $requestData = $request->all();

        if ($supplier) {
            $requestData['recipient_name'] = trim((string) ($requestData['recipient_name'] ?? ''))
                ?: ($supplier->contact_person ?: $supplier->name);
            $requestData['recipient_email'] = trim((string) ($requestData['recipient_email'] ?? ''))
                ?: ($supplier->habitual_order_email ?: $supplier->email ?: '');
        }

        $validator = Validator::make(
            $requestData,
            [
                'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
                'recipient_name' => ['nullable', 'string', 'max:150'],
                'recipient_email' => ['required', 'email', 'max:150'],
                'cc_email' => ['nullable', 'email', 'max:150'],
                'bcc_email' => ['nullable', 'email', 'max:150'],
                'email_notes' => ['nullable', 'string', 'max:5000'],
                'email_attachment' => ['nullable', 'file', 'max:' . self::EMAIL_ATTACHMENT_MAX_KB],
            ],
            [
                'email_attachment.max' => 'O anexo nao pode ultrapassar 5 MB.',
            ],
            [
                'recipient_name' => 'nome do destinatario',
                'recipient_email' => 'email do destinatario',
                'cc_email' => 'email em cc',
                'bcc_email' => 'email em bcc',
                'email_notes' => 'observacoes',
                'email_attachment' => 'anexo',
            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->withErrors($validator)
                ->withInput()
                ->with('open_send_email_modal', true);
        }

        $recipientName = trim((string) ($requestData['recipient_name'] ?? ''));
        $recipientEmail = trim((string) ($requestData['recipient_email'] ?? ''));
        $ccEmail = trim((string) ($requestData['cc_email'] ?? ''));
        $bccEmail = trim((string) ($requestData['bcc_email'] ?? ''));
        $emailNotes = trim((string) ($requestData['email_notes'] ?? ''));
        $attachmentFile = $request->file('email_attachment');
        $subject = 'Pedido de cotacao ' . $purchaseRequest->code;

        $oldStatus = $purchaseRequest->status;

        try {
            $pdfContent = $this->makeRfqPdf(
                purchaseRequest: $purchaseRequest,
                companyProfile: $companyProfile,
                supplier: $supplier,
            )->output();

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

            $pendingMail = Mail::mailer('smtp')
                ->to($recipientEmail, $recipientName !== '' ? $recipientName : null);

            if ($ccEmail !== '') {
                $pendingMail->cc($ccEmail);
            }

            if ($bccEmail !== '') {
                $pendingMail->bcc($bccEmail);
            }

            $mailable = new PurchaseRequestMail(
                purchaseRequest: $purchaseRequest,
                pdfContent: $pdfContent,
                pdfFileName: $purchaseRequest->code . '.pdf',
                fromAddress: $companyProfile->mail_from_address,
                fromName: $companyProfile->mail_from_name,
                recipientName: $recipientName,
                emailNotes: $emailNotes,
                companyProfile: $companyProfile,
                supplier: $supplier,
            );

            if ($attachmentFile !== null) {
                $attachmentPath = $attachmentFile->getRealPath();

                if ($attachmentPath === false) {
                    throw new \RuntimeException('Nao foi possivel ler o anexo.');
                }

                $mailable->attach($attachmentPath, [
                    'as' => $attachmentFile->getClientOriginalName(),
                    'mime' => $attachmentFile->getClientMimeType() ?: 'application/octet-stream',
                ]);
            }

            $pendingMail->send($mailable);

            PurchaseRequestEmailLog::query()->create([
                'purchase_request_id' => $purchaseRequest->id,
                'sent_by' => Auth::id(),
                'recipient_name' => $recipientName !== '' ? $recipientName : null,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'message' => $emailNotes !== '' ? $emailNotes : null,
                'sent_at' => now(),
            ]);

            if ($purchaseRequest->status === PurchaseRequest::STATUS_DRAFT) {
                $purchaseRequest->update([
                    'status' => PurchaseRequest::STATUS_SENT,
                    'sent_at' => now(),
                    'updated_by' => Auth::id(),
                ]);
            } elseif (! $purchaseRequest->sent_at) {
                $purchaseRequest->update([
                    'sent_at' => now(),
                    'updated_by' => Auth::id(),
                ]);
            }

            $purchaseRequest->refresh();

            $this->activityLogService->log(
                action: ActivityActions::EMAIL_SENT,
                entity: 'purchase_request',
                entityId: $purchaseRequest->id,
                payload: [
                    'code' => $purchaseRequest->code,
                    'supplier_id' => $supplier?->id,
                    'supplier_name' => $supplier?->name,
                    'recipient_name' => $recipientName !== '' ? $recipientName : null,
                    'recipient_email' => $recipientEmail,
                    'cc_email' => $ccEmail !== '' ? $ccEmail : null,
                    'bcc_email' => $bccEmail !== '' ? $bccEmail : null,
                    'attachment_name' => $attachmentFile?->getClientOriginalName(),
                    'subject' => $subject,
                    'message' => $emailNotes !== '' ? $emailNotes : null,
                    'old_status' => $oldStatus,
                    'new_status' => $purchaseRequest->status,
                ],
                ownerId: $purchaseRequest->owner_id,
                userId: Auth::id(),
            );
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Ocorreu um erro ao enviar o email. Verifica a configuracao SMTP e tenta novamente.')
                ->with('open_send_email_modal', true)
                ->withInput();
        }

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'RFQ enviado por email com sucesso.');
    }

    public function destroy(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('delete', $purchaseRequest);

        if (! in_array($purchaseRequest->status, [PurchaseRequest::STATUS_DRAFT, PurchaseRequest::STATUS_CANCELLED], true)) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Apenas pedidos em rascunho ou cancelados podem ser removidos.');
        }

        $payload = [
            'code' => $purchaseRequest->code,
            'title' => $purchaseRequest->title,
            'status' => $purchaseRequest->status,
            'quotes_count' => $purchaseRequest->quotes()->count(),
        ];

        $purchaseRequest->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'purchase_request',
            entityId: $purchaseRequest->id,
            payload: $payload,
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.index')
            ->with('success', 'Pedido de cotacao removido com sucesso.');
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function syncItems(PurchaseRequest $purchaseRequest, array $items): void
    {
        $purchaseRequest->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $purchaseRequest->items()->create([
                'item_id' => $item['item_id'] ?? null,
                'description' => $item['description'],
                'qty' => $item['qty'],
                'unit_snapshot' => $item['unit_snapshot'] ?? null,
                'notes' => $item['notes'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function availableWorks()
    {
        return Work::query()
            ->whereIn('status', [
                Work::STATUS_PLANNED,
                Work::STATUS_IN_PROGRESS,
                Work::STATUS_SUSPENDED,
            ])
            ->orderByDesc('id')
            ->get(['id', 'code', 'name', 'status']);
    }

    private function availableItems()
    {
        return Item::query()
            ->with('unit:id,name,code')
            ->where('is_active', true)
            ->where('type', '!=', 'service')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'unit_id']);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function resolveTitle(array $validated, ?PurchaseRequest $current = null): string
    {
        $rawTitle = trim((string) ($validated['title'] ?? ''));
        if ($rawTitle !== '') {
            return Str::limit($rawTitle, 255, '');
        }

        if (! empty($validated['work_id'])) {
            $work = Work::query()
                ->select(['id', 'code'])
                ->find((int) $validated['work_id']);

            if ($work) {
                return 'RFQ ' . $work->code;
            }
        }

        if ($current && $current->title !== '') {
            return $current->title;
        }

        return 'Pedido de cotacao';
    }

    private function makeRfqPdf(PurchaseRequest $purchaseRequest, ?CompanyProfile $companyProfile, ?Supplier $supplier = null)
    {
        return Pdf::loadView('purchases.requests.pdf', [
            'purchaseRequest' => $purchaseRequest,
            'companyProfile' => $companyProfile,
            'supplier' => $supplier,
        ])->setPaper('a4', 'portrait');
    }

    private function supplierItemReferenceMap(PurchaseRequest $purchaseRequest): array
    {
        $itemIds = $purchaseRequest->items
            ->pluck('item_id')
            ->filter()
            ->map(fn ($itemId) => (int) $itemId)
            ->unique()
            ->values()
            ->all();

        if (count($itemIds) === 0) {
            return [];
        }

        return SupplierItemReference::query()
            ->whereIn('item_id', $itemIds)
            ->get(['supplier_id', 'item_id', 'supplier_item_reference'])
            ->mapWithKeys(function (SupplierItemReference $row) {
                $key = (int) $row->supplier_id . ':' . (int) $row->item_id;

                return [$key => $row->supplier_item_reference];
            })
            ->all();
    }
}
