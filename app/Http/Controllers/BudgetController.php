<?php

namespace App\Http\Controllers;

use App\Actions\Budgets\ChangeBudgetStatusAction;
use App\Http\Requests\Budgets\StoreBudgetRequest;
use App\Mail\BudgetMail;
use App\Models\Budget;
use App\Models\BudgetEmailLog;
use App\Models\Customer;
use App\Models\Item;
use App\Models\PaymentTerm;
use App\Models\TaxExemptionReason;
use App\Models\TaxRate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $budgets = Budget::query()
            ->with(['customer'])
            ->where('owner_id', Auth::id())
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
        $hasSeries = \App\Models\DocumentSeries::where('owner_id', Auth::id())
            ->where('document_type', 'budget')
            ->where('is_active', true)
            ->exists();

        if (! $hasSeries) {
            return redirect()
                ->route('budgets.index')
                ->with('error', 'Não existe uma série ativa para orçamentos. Cria primeiro uma série em Definições > Séries de documentos.');
        }

        $customers = Customer::where('owner_id', Auth::id())
            ->orderBy('name')
            ->get();

        return view('budgets.create', compact('customers'));
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $budget = DB::transaction(function () use ($request) {
            $series = \App\Models\DocumentSeries::where('owner_id', Auth::id())
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
                'owner_id' => Auth::id(),
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

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Orçamento criado com sucesso.');
    }

    public function show(Budget $budget)
    {
        $this->authorizeBudgetAccess($budget);

        $budget->load([
            'customer',
            'creator',
            'updater',
            'owner.companyProfile',
            'items.item.unit',
            'emailLogs.sender',
            'paymentTerm',
            'documentSeries',
        ]);

        $availableItems = Item::query()
            ->with(['unit', 'taxRate'])
            ->where('owner_id', Auth::id())
            ->orderBy('name')
            ->get();

        $taxRates = TaxRate::query()
            ->orderBy('percent')
            ->get();

        $taxExemptionReasons = TaxExemptionReason::query()
            ->orderBy('code')
            ->get();

        $paymentTerms = PaymentTerm::query()
            ->visibleForOwner(Auth::id())
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('budgets.show', compact(
            'budget',
            'availableItems',
            'taxRates',
            'taxExemptionReasons',
            'paymentTerms'
        ));
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizeBudgetAccess($budget);

        if (! $budget->isEditable()) {
            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'Só é possível editar orçamentos em rascunho.');
        }

        $validated = $request->validate([
            'budget_date' => ['required', 'date'],
            'designation' => ['nullable', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $budget->update([
            'budget_date' => $validated['budget_date'],
            'designation' => $validated['designation'] ?? null,
            'zone' => $validated['zone'] ?? null,
            'project_name' => $validated['project_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'updated_by' => Auth::id(),
            'valid_until' => $request->input('valid_until'),
            'external_reference' => $request->input('external_reference'),
            'payment_term_id' => $request->input('payment_term_id') ?: null,
        ]);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Cabeçalho do orçamento atualizado com sucesso.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $this->authorizeBudgetAccess($budget);

        if (! $budget->isDeletable()) {
            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'Só é possível apagar orçamentos em rascunho.');
        }

        $budget->delete();

        return redirect()
            ->route('budgets.index')
            ->with('success', 'Orçamento apagado com sucesso.');
    }

    public function changeStatus(Request $request, Budget $budget, ChangeBudgetStatusAction $action): RedirectResponse
    {
        $this->authorizeBudgetAccess($budget);

        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        try {
            $action->execute($budget, $validated['status']);
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

    public function pdf(Budget $budget)
    {
        $this->authorizeBudgetAccess($budget);

        $budget->load([
            'customer',
            'owner.companyProfile',
            'items.item.unit',
        ]);

        $pdf = Pdf::loadView('budgets.pdf', [
            'budget' => $budget,
        ])->setPaper('a4');

        return $pdf->stream($budget->code . '.pdf');
    }

    public function sendEmail(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizeBudgetAccess($budget);

        $budget->load([
            'customer',
            'owner.companyProfile',
            'items.item.unit',
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

        $companyProfile = $budget->owner?->companyProfile;

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
                'email_notes' => ['nullable', 'string', 'max:5000'],
            ],
            [],
            [
                'recipient_name' => 'nome do destinatário',
                'recipient_email' => 'email do destinatário',
                'email_notes' => 'observações',
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
        $emailNotes = trim((string) $request->input('email_notes', ''));
        $subject = 'Orçamento ' . $budget->code;

        try {
            $pdfContent = Pdf::loadView('budgets.pdf', [
                'budget' => $budget,
            ])->setPaper('a4')->output();

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

            Mail::mailer('smtp')
                ->to($recipientEmail, $recipientName !== '' ? $recipientName : null)
                ->send(new BudgetMail(
                    budget: $budget,
                    pdfContent: $pdfContent,
                    pdfFileName: $budget->code . '.pdf',
                    fromAddress: $companyProfile->mail_from_address,
                    fromName: $companyProfile->mail_from_name,
                    recipientName: $recipientName,
                    emailNotes: $emailNotes,
                    companyProfile: $companyProfile,
                ));

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

    private function authorizeBudgetAccess(Budget $budget): void
    {
        abort_unless((int) $budget->owner_id === (int) Auth::id(), 403);
    }
}
