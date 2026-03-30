<?php

namespace App\Http\Controllers;

use App\Actions\Budgets\ChangeBudgetStatusAction;
use App\Actions\Budgets\RecalculateBudgetTotalsAction;
use App\Http\Requests\Budgets\StoreBudgetRequest;
use App\Http\Requests\Budgets\UpdateBudgetRequest;
use App\Mail\BudgetMail;
use App\Models\Budget;
use App\Models\BudgetEmailLog;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\DocumentSeries;
use App\Models\Item;
use App\Models\PaymentTerm;
use App\Models\TaxExemptionReason;
use App\Models\TaxRate;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class BudgetController extends Controller
{
    public const EMAIL_ATTACHMENT_MAX_KB = 5120;
    public const PDF_TEMPLATE_COMMERCIAL = 'commercial';
    public const PDF_TEMPLATE_TECHNICAL = 'technical';
    public const VAT_MODE_WITH_VAT = 'with_vat';
    public const VAT_MODE_WITHOUT_VAT_WITH_NOTICE = 'without_vat_with_notice';

    public function __construct(
        protected ActivityLogService $activityLogService,
        protected RecalculateBudgetTotalsAction $recalculateBudgetTotalsAction
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Budget::class);

        $budgets = Budget::query()
            ->with(['customer'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('code', 'like', '%' . $search . '%')
                        ->orWhere('designation', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('budgets.index', compact('budgets'));
    }

    public function create()
    {
        $this->authorize('create', Budget::class);

        $hasSeries = DocumentSeries::query()
            ->where('document_type', 'budget')
            ->where('is_active', true)
            ->exists();

        if (! $hasSeries) {
            return redirect()
                ->route('budgets.index')
                ->with('error', 'Não existe uma série ativa para orçamentos. Cria primeiro uma série em Definições > Séries de documentos.');
        }

        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        return view('budgets.create', compact('customers'));
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $this->authorize('create', Budget::class);

        $budget = DB::transaction(function () use ($request) {
            $series = DocumentSeries::query()
                ->where('document_type', 'budget')
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $series) {
                throw new RuntimeException('Não existe série ativa para orçamentos.');
            }

            $number = (int) $series->next_number;

            $code = sprintf(
                '%s-%s-%04d',
                $series->prefix,
                $series->name,
                $number
            );

            $budget = Budget::create([
                'customer_id' => $request->integer('customer_id'),
                'budget_date' => $request->input('budget_date'),
                'designation' => $request->input('designation'),
                'zone' => $request->input('zone'),
                'project_name' => $request->input('project_name'),
                'notes' => $request->input('notes'),
                'valid_until' => $request->input('valid_until'),
                'external_reference' => $request->input('external_reference'),
                'payment_term_id' => $request->input('payment_term_id') ?: null,
                'document_series_id' => $series->id,
                'serial_number' => $number,
                'code' => $code,
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $series->update([
                'next_number' => $number + 1,
            ]);

            return $budget;
        });

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'budget',
            entityId: $budget->id,
            payload: [
                'code' => $budget->code,
                'customer_id' => $budget->customer_id,
                'budget_date' => $budget->budget_date,
                'designation' => $budget->designation,
                'status' => $budget->status,
                'total' => $budget->total,
            ],
            ownerId: $budget->owner_id,
            userId: Auth::id()
        );

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Orçamento criado com sucesso.');
    }

    public function duplicate(Budget $budget): RedirectResponse
    {
        $this->authorize('view', $budget);
        $this->authorize('create', Budget::class);

        try {
            $duplicatedBudget = DB::transaction(function () use ($budget) {
                return $this->createBudgetFromSource($budget, false);
            });

            $this->activityLogService->log(
                action: ActivityActions::DUPLICATED,
                entity: 'budget',
                entityId: $duplicatedBudget->id,
                payload: [
                    'code' => $duplicatedBudget->code,
                    'source_budget_id' => $budget->id,
                    'source_budget_code' => $budget->code,
                    'mode' => 'duplicate',
                    'version_number' => $duplicatedBudget->version_number,
                ],
                ownerId: $duplicatedBudget->owner_id,
                userId: Auth::id()
            );
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'Nao foi possivel duplicar o orcamento.');
        }

        return redirect()
            ->route('budgets.show', $duplicatedBudget)
            ->with('success', 'Orcamento duplicado com sucesso.');
    }

    public function createVersion(Budget $budget): RedirectResponse
    {
        $this->authorize('view', $budget);
        $this->authorize('create', Budget::class);

        try {
            $newVersionBudget = DB::transaction(function () use ($budget) {
                return $this->createBudgetFromSource($budget, true);
            });

            $this->activityLogService->log(
                action: ActivityActions::VERSION_CREATED,
                entity: 'budget',
                entityId: $newVersionBudget->id,
                payload: [
                    'code' => $newVersionBudget->code,
                    'source_budget_id' => $budget->id,
                    'source_budget_code' => $budget->code,
                    'root_budget_id' => $newVersionBudget->root_budget_id,
                    'version_number' => $newVersionBudget->version_number,
                ],
                ownerId: $newVersionBudget->owner_id,
                userId: Auth::id()
            );
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'Nao foi possivel criar uma nova versao deste orcamento.');
        }

        return redirect()
            ->route('budgets.show', $newVersionBudget)
            ->with('success', 'Nova versao criada com sucesso.');
    }

    public function show(Budget $budget)
    {
        $this->authorize('view', $budget);

        $budget->load([
            'customer',
            'creator',
            'updater',
            'items.item.unit',
            'emailLogs.sender',
            'paymentTerm',
            'documentSeries',
            'work',
            'rootBudget',
            'parentBudget',
        ]);

        $availableItems = Item::query()
            ->with(['unit', 'taxRate'])
            ->orderBy('name')
            ->get();

        $taxRates = TaxRate::query()
            ->orderBy('percent')
            ->get();

        $taxExemptionReasons = TaxExemptionReason::query()
            ->orderBy('code')
            ->get();

        $paymentTerms = PaymentTerm::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $companyProfile = CompanyProfile::query()
            ->orderBy('id')
            ->first();

        $versionRootId = $budget->resolvedRootBudgetId();

        $budgetVersionHistory = Budget::query()
            ->where(function ($query) use ($versionRootId) {
                $query->where('id', $versionRootId)
                    ->orWhere('root_budget_id', $versionRootId);
            })
            ->orderBy('version_number')
            ->orderBy('id')
            ->get();

        $latestBudgetVersion = Budget::query()
            ->where(function ($query) use ($versionRootId) {
                $query->where('id', $versionRootId)
                    ->orWhere('root_budget_id', $versionRootId);
            })
            ->orderByDesc('version_number')
            ->orderByDesc('id')
            ->first();

        $isLatestBudgetVersion = $latestBudgetVersion?->id === $budget->id;
        $versionRootBudget = $budget->root_budget_id ? $budget->rootBudget : $budget;

        $budgetEmailAttachmentMaxKb = self::EMAIL_ATTACHMENT_MAX_KB;
        $budgetPdfTemplates = self::pdfTemplateOptions();
        $budgetVatModes = self::vatModeOptions();
        $defaultBudgetPdfTemplate = $this->resolveDefaultPdfTemplate($companyProfile);
        $defaultBudgetVatMode = $this->resolveDefaultVatMode($companyProfile);

        return view('budgets.show', compact(
            'budget',
            'availableItems',
            'taxRates',
            'taxExemptionReasons',
            'paymentTerms',
            'companyProfile',
            'budgetEmailAttachmentMaxKb',
            'budgetPdfTemplates',
            'budgetVatModes',
            'defaultBudgetPdfTemplate',
            'defaultBudgetVatMode',
            'budgetVersionHistory',
            'latestBudgetVersion',
            'isLatestBudgetVersion',
            'versionRootBudget'
        ));
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $this->authorize('update', $budget);

        if (! $budget->isEditable()) {
            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'Só é possível editar orçamentos em rascunho.');
        }

        $oldData = $budget->only([
            'budget_date',
            'designation',
            'zone',
            'project_name',
            'notes',
            'valid_until',
            'external_reference',
            'payment_term_id',
        ]);

        $budget->update([
            'budget_date' => $request->input('budget_date'),
            'designation' => $request->input('designation'),
            'zone' => $request->input('zone'),
            'project_name' => $request->input('project_name'),
            'notes' => $request->input('notes'),
            'updated_by' => Auth::id(),
            'valid_until' => $request->input('valid_until'),
            'external_reference' => $request->input('external_reference'),
            'payment_term_id' => $request->input('payment_term_id') ?: null,
        ]);

        $budget->refresh();

        $newData = $budget->only([
            'budget_date',
            'designation',
            'zone',
            'project_name',
            'notes',
            'valid_until',
            'external_reference',
            'payment_term_id',
        ]);

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'budget',
            entityId: $budget->id,
            payload: [
                'code' => $budget->code,
                'old' => $oldData,
                'new' => $newData,
            ],
            ownerId: $budget->owner_id,
            userId: Auth::id()
        );

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Cabeçalho do orçamento atualizado com sucesso.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $this->authorize('delete', $budget);

        if (! $budget->isDeletable()) {
            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'Só é possível apagar orçamentos em rascunho.');
        }

        $payload = [
            'code' => $budget->code,
            'customer_id' => $budget->customer_id,
            'budget_date' => $budget->budget_date,
            'designation' => $budget->designation,
            'status' => $budget->status,
            'total' => $budget->total,
        ];

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'budget',
            entityId: $budget->id,
            payload: $payload,
            ownerId: $budget->owner_id,
            userId: Auth::id()
        );

        $budget->delete();

        return redirect()
            ->route('budgets.index')
            ->with('success', 'Orçamento apagado com sucesso.');
    }

    public function changeStatus(Request $request, Budget $budget, ChangeBudgetStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $budget);

        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $oldStatus = $budget->status;

        try {
            $action->execute($budget, $validated['status']);

            $budget->refresh();

            $this->activityLogService->log(
                action: ActivityActions::STATUS_CHANGED,
                entity: 'budget',
                entityId: $budget->id,
                payload: [
                    'code' => $budget->code,
                    'old_status' => $oldStatus,
                    'new_status' => $budget->status,
                ],
                ownerId: $budget->owner_id,
                userId: Auth::id()
            );
        } catch (RuntimeException $exception) {
            report($exception);

            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'Não foi possível atualizar o estado do orçamento.');
        }

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Estado do orçamento atualizado com sucesso.');
    }

    public function pdf(Request $request, Budget $budget)
    {
        $this->authorize('view', $budget);

        $companyProfile = CompanyProfile::query()
            ->orderBy('id')
            ->first();

        $pdfTemplate = $this->normalizePdfTemplate((string) $request->query('template', $this->resolveDefaultPdfTemplate($companyProfile)));
        $vatMode = $this->normalizeVatMode((string) $request->query('vat_mode', $this->resolveDefaultVatMode($companyProfile)));

        $budget->load([
            'customer',
            'items.item.unit',
            'paymentTerm',
        ]);

        $pdf = $this->makeBudgetPdf(
            budget: $budget,
            companyProfile: $companyProfile,
            pdfTemplate: $pdfTemplate,
            vatMode: $vatMode,
        );

        return $pdf->stream($budget->code . '.pdf');
    }

    public function sendEmail(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorize('update', $budget);

        $budget->load([
            'customer',
            'items.item.unit',
            'paymentTerm',
        ]);

        if (! in_array($budget->status, [
            Budget::STATUS_CREATED,
            Budget::STATUS_SENT,
            Budget::STATUS_WAITING_RESPONSE,
        ], true)) {
            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'Só é possível enviar por email orçamentos nos estados Criado, Enviado ou Aguarda resposta.');
        }

        $companyProfile = CompanyProfile::query()
            ->orderBy('id')
            ->first();

        if (! $companyProfile) {
            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'Não existem dados da empresa configurados.');
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
                    ->route('budgets.show', $budget)
                    ->with('error', 'Falta configurar o campo de email da empresa: ' . $field . '.');
            }
        }

        $validator = Validator::make(
            $request->all(),
            [
                'recipient_name' => ['nullable', 'string', 'max:150'],
                'recipient_email' => ['required', 'email', 'max:150'],
                'cc_email' => ['nullable', 'email', 'max:150'],
                'bcc_email' => ['nullable', 'email', 'max:150'],
                'email_notes' => ['nullable', 'string', 'max:5000'],
                'email_attachment' => ['nullable', 'file', 'max:' . self::EMAIL_ATTACHMENT_MAX_KB],
                'pdf_template' => ['nullable', Rule::in(array_keys(self::pdfTemplateOptions()))],
                'vat_mode' => ['nullable', Rule::in(array_keys(self::vatModeOptions()))],
            ],
            [
                'email_attachment.max' => 'O anexo nao pode ultrapassar 5 MB.',
            ],
            [
                'recipient_name' => 'nome do destinatário',
                'recipient_email' => 'email do destinatário',
                'cc_email' => 'email em cc',
                'bcc_email' => 'email em bcc',
                'email_notes' => 'observações',
                'email_attachment' => 'anexo',
                'pdf_template' => 'template do pdf',
                'vat_mode' => 'modo de iva',
            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->route('budgets.show', $budget)
                ->withErrors($validator)
                ->withInput()
                ->with('open_send_email_modal', true);
        }

        $recipientName = trim((string) $request->input('recipient_name', ''));
        $recipientEmail = trim((string) $request->input('recipient_email', ''));
        $ccEmail = trim((string) $request->input('cc_email', ''));
        $bccEmail = trim((string) $request->input('bcc_email', ''));
        $emailNotes = trim((string) $request->input('email_notes', ''));
        $attachmentFile = $request->file('email_attachment');
        $pdfTemplate = $this->normalizePdfTemplate((string) $request->input('pdf_template', $this->resolveDefaultPdfTemplate($companyProfile)));
        $vatMode = $this->normalizeVatMode((string) $request->input('vat_mode', $this->resolveDefaultVatMode($companyProfile)));
        $subject = 'Orçamento ' . $budget->code;
        $oldStatus = $budget->status;

        try {
            $pdfContent = $this->makeBudgetPdf(
                budget: $budget,
                companyProfile: $companyProfile,
                pdfTemplate: $pdfTemplate,
                vatMode: $vatMode,
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

            $mailable = new BudgetMail(
                budget: $budget,
                pdfContent: $pdfContent,
                pdfFileName: $budget->code . '.pdf',
                fromAddress: $companyProfile->mail_from_address,
                fromName: $companyProfile->mail_from_name,
                recipientName: $recipientName,
                emailNotes: $emailNotes,
                companyProfile: $companyProfile,
                pdfTemplate: $pdfTemplate,
                vatMode: $vatMode,
            );

            if ($attachmentFile !== null) {
                $attachmentPath = $attachmentFile->getRealPath();

                if ($attachmentPath === false) {
                    throw new RuntimeException('Nao foi possivel ler o anexo.');
                }

                $mailable->attach($attachmentPath, [
                    'as' => $attachmentFile->getClientOriginalName(),
                    'mime' => $attachmentFile->getClientMimeType() ?: 'application/octet-stream',
                ]);
            }

            $pendingMail->send($mailable);

            BudgetEmailLog::create([
                'budget_id' => $budget->id,
                'sent_by' => Auth::id(),
                'recipient_name' => $recipientName !== '' ? $recipientName : null,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'message' => $emailNotes !== '' ? $emailNotes : null,
                'sent_at' => now(),
            ]);

            if ($budget->status === Budget::STATUS_CREATED) {
                $budget->update([
                    'status' => Budget::STATUS_SENT,
                    'updated_by' => Auth::id(),
                ]);
            }

            $budget->refresh();

            $this->activityLogService->log(
                action: ActivityActions::EMAIL_SENT,
                entity: 'budget',
                entityId: $budget->id,
                payload: [
                    'code' => $budget->code,
                    'recipient_name' => $recipientName !== '' ? $recipientName : null,
                    'recipient_email' => $recipientEmail,
                    'cc_email' => $ccEmail !== '' ? $ccEmail : null,
                    'bcc_email' => $bccEmail !== '' ? $bccEmail : null,
                    'attachment_name' => $attachmentFile?->getClientOriginalName(),
                    'pdf_template' => $pdfTemplate,
                    'vat_mode' => $vatMode,
                    'subject' => $subject,
                    'message' => $emailNotes !== '' ? $emailNotes : null,
                    'old_status' => $oldStatus,
                    'new_status' => $budget->status,
                ],
                ownerId: $budget->owner_id,
                userId: Auth::id()
            );
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'Ocorreu um erro ao enviar o email. Verifica a configuração SMTP e tenta novamente.')
                ->with('open_send_email_modal', true)
                ->withInput();
        }

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Orçamento enviado por email com sucesso.');
    }

    private function createBudgetFromSource(Budget $sourceBudget, bool $asVersion): Budget
    {
        $sourceBudget = Budget::query()
            ->with([
                'items' => function ($query) {
                    $query->orderBy('sort_order')->orderBy('id');
                },
            ])
            ->lockForUpdate()
            ->findOrFail($sourceBudget->id);

        $rootBudgetId = null;
        $parentBudgetId = null;
        $versionNumber = 1;

        if ($asVersion) {
            $rootBudgetId = $sourceBudget->resolvedRootBudgetId();
            $parentBudgetId = $sourceBudget->id;
            $versionNumber = $this->resolveNextVersionNumber($rootBudgetId);
        }

        $newBudget = $this->createBudgetWithNextSeries([
            'customer_id' => $sourceBudget->customer_id,
            'budget_date' => $sourceBudget->budget_date,
            'designation' => $sourceBudget->designation,
            'zone' => $sourceBudget->zone,
            'project_name' => $sourceBudget->project_name,
            'notes' => $sourceBudget->notes,
            'valid_until' => $sourceBudget->valid_until,
            'external_reference' => $sourceBudget->external_reference,
            'payment_term_id' => $sourceBudget->payment_term_id,
            'status' => Budget::STATUS_DRAFT,
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'root_budget_id' => $rootBudgetId,
            'parent_budget_id' => $parentBudgetId,
            'version_number' => $versionNumber,
            'owner_id' => $sourceBudget->owner_id ?? Auth::id(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->copyBudgetItemsToBudget($sourceBudget, $newBudget);
        $this->recalculateBudgetTotalsAction->execute($newBudget);

        return $newBudget->fresh([
            'customer',
            'items.item.unit',
            'paymentTerm',
            'documentSeries',
            'rootBudget',
            'parentBudget',
        ]);
    }

    private function createBudgetWithNextSeries(array $attributes): Budget
    {
        $series = DocumentSeries::query()
            ->where('document_type', 'budget')
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (! $series) {
            throw new RuntimeException('Nao existe serie ativa para orcamentos.');
        }

        $number = (int) $series->next_number;
        $code = sprintf('%s-%s-%04d', $series->prefix, $series->name, $number);

        $budget = Budget::create(array_merge($attributes, [
            'document_series_id' => $series->id,
            'serial_number' => $number,
            'code' => $code,
        ]));

        $series->update([
            'next_number' => $number + 1,
        ]);

        return $budget;
    }

    private function copyBudgetItemsToBudget(Budget $sourceBudget, Budget $targetBudget): void
    {
        foreach ($sourceBudget->items as $item) {
            $targetBudget->items()->create([
                'item_id' => $item->item_id,
                'sort_order' => $item->sort_order,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'item_type' => $item->item_type,
                'description' => $item->description,
                'unit_name' => $item->unit_name,
                'tax_rate_id' => $item->tax_rate_id,
                'tax_rate_name' => $item->tax_rate_name,
                'tax_percent' => $item->tax_percent,
                'tax_exemption_reason_id' => $item->tax_exemption_reason_id,
                'tax_exemption_reason' => $item->tax_exemption_reason,
                'notes' => $item->notes,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_percent' => $item->discount_percent,
                'subtotal' => $item->subtotal,
                'discount_total' => $item->discount_total,
                'tax_total' => $item->tax_total,
                'total' => $item->total,
            ]);
        }
    }

    private function resolveNextVersionNumber(int $rootBudgetId): int
    {
        $latestVersionNumber = (int) Budget::query()
            ->where(function ($query) use ($rootBudgetId) {
                $query->where('id', $rootBudgetId)
                    ->orWhere('root_budget_id', $rootBudgetId);
            })
            ->lockForUpdate()
            ->max('version_number');

        return max(1, $latestVersionNumber) + 1;
    }

    private function resolveDefaultPdfTemplate(?CompanyProfile $companyProfile): string
    {
        return $this->normalizePdfTemplate((string) ($companyProfile?->budget_default_pdf_template ?: self::PDF_TEMPLATE_COMMERCIAL));
    }

    private function resolveDefaultVatMode(?CompanyProfile $companyProfile): string
    {
        return $this->normalizeVatMode((string) ($companyProfile?->budget_default_vat_mode ?: self::VAT_MODE_WITH_VAT));
    }

    private static function pdfTemplateOptions(): array
    {
        return [
            self::PDF_TEMPLATE_COMMERCIAL => 'Comercial',
            self::PDF_TEMPLATE_TECHNICAL => 'Técnico',
        ];
    }

    private static function vatModeOptions(): array
    {
        return [
            self::VAT_MODE_WITH_VAT => 'Com IVA',
            self::VAT_MODE_WITHOUT_VAT_WITH_NOTICE => 'Sem IVA (com nota legal)',
        ];
    }

    private function normalizePdfTemplate(string $template): string
    {
        return array_key_exists($template, self::pdfTemplateOptions())
            ? $template
            : self::PDF_TEMPLATE_COMMERCIAL;
    }

    private function normalizeVatMode(string $vatMode): string
    {
        return array_key_exists($vatMode, self::vatModeOptions())
            ? $vatMode
            : self::VAT_MODE_WITH_VAT;
    }

    private function makeBudgetPdf(
        Budget $budget,
        ?CompanyProfile $companyProfile,
        string $pdfTemplate,
        string $vatMode
    ) {
        $template = $this->normalizePdfTemplate($pdfTemplate);
        $normalizedVatMode = $this->normalizeVatMode($vatMode);

        return Pdf::loadView('budgets.pdf', [
            'budget' => $budget,
            'companyProfile' => $companyProfile,
            'template' => $template,
            'vatMode' => $normalizedVatMode,
            'showVatValues' => $normalizedVatMode === self::VAT_MODE_WITH_VAT,
            'showVatNotice' => $normalizedVatMode === self::VAT_MODE_WITHOUT_VAT_WITH_NOTICE,
            'vatNoticeText' => 'Ao valor apresentado acresce IVA à taxa legal em vigor.',
        ])->setPaper('a4', 'portrait');
    }
}
