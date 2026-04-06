<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customers\StoreCustomerReceivableRequest;
use App\Http\Requests\Customers\UpdateCustomerReceivableRequest;
use App\Models\Customer;
use App\Models\CustomerReceivable;
use App\Services\ActivityLogService;
use App\Services\Finance\OperationalAccountEntryService;
use App\Support\ActivityActions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerReceivableController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected OperationalAccountEntryService $operationalAccountEntryService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CustomerReceivable::class);

        $ownerId = (int) Auth::id();

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'customer_id' => (int) $request->input('customer_id', 0),
            'status' => trim((string) $request->input('status', '')),
            'issue_from' => trim((string) $request->input('issue_from', '')),
            'issue_to' => trim((string) $request->input('issue_to', '')),
            'due_from' => trim((string) $request->input('due_from', '')),
            'due_to' => trim((string) $request->input('due_to', '')),
        ];

        $receivables = CustomerReceivable::query()
            ->where('owner_id', $ownerId)
            ->with([
                'customer:id,code,name',
                'user:id,name',
                'issuer:id,name',
                'closer:id,name',
                'accountEntry:id,owner_id,customer_id,reference_type,reference_id,type',
            ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $subQuery) use ($filters): void {
                    $subQuery
                        ->where('document_number', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('notes', 'like', '%' . $filters['search'] . '%')
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($filters): void {
                            $customerQuery
                                ->where('name', 'like', '%' . $filters['search'] . '%')
                                ->orWhere('code', 'like', '%' . $filters['search'] . '%');
                        });
                });
            })
            ->when($filters['customer_id'] > 0, function (Builder $query) use ($filters): void {
                $query->where('customer_id', $filters['customer_id']);
            })
            ->when($filters['status'] !== '', function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when($filters['issue_from'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('issue_date', '>=', $filters['issue_from']);
            })
            ->when($filters['issue_to'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('issue_date', '<=', $filters['issue_to']);
            })
            ->when($filters['due_from'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('due_date', '>=', $filters['due_from']);
            })
            ->when($filters['due_to'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('due_date', '<=', $filters['due_to']);
            })
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $customers = Customer::query()
            ->where('owner_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('customers.receivables.index', [
            'receivables' => $receivables,
            'filters' => $filters,
            'customers' => $customers,
            'statuses' => CustomerReceivable::statuses(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', CustomerReceivable::class);

        $ownerId = (int) Auth::id();
        $preselectedCustomerId = (int) $request->input('customer_id', 0);

        $customers = Customer::query()
            ->where('owner_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'payment_terms_days']);

        if ($preselectedCustomerId > 0 && ! $customers->contains('id', $preselectedCustomerId)) {
            $preselectedCustomerId = 0;
        }

        $defaultDueDate = now()->toDateString();
        if ($preselectedCustomerId > 0) {
            /** @var Customer|null $selectedCustomer */
            $selectedCustomer = $customers->firstWhere('id', $preselectedCustomerId);
            if ($selectedCustomer) {
                $defaultDueDate = now()->addDays(max(0, (int) $selectedCustomer->payment_terms_days))->toDateString();
            }
        }

        return view('customers.receivables.create', [
            'receivable' => new CustomerReceivable([
                'issue_date' => now()->toDateString(),
                'due_date' => $defaultDueDate,
                'status' => CustomerReceivable::STATUS_DRAFT,
                'customer_id' => $preselectedCustomerId > 0 ? $preselectedCustomerId : null,
            ]),
            'customers' => $customers,
            'creatableStatuses' => CustomerReceivable::creatableStatuses(),
        ]);
    }

    public function store(StoreCustomerReceivableRequest $request): RedirectResponse
    {
        $this->authorize('create', CustomerReceivable::class);

        $validated = $request->validated();
        $ownerId = (int) Auth::id();

        $result = DB::transaction(function () use ($validated, $ownerId): array {
            $status = (string) ($validated['status'] ?? CustomerReceivable::STATUS_DRAFT);

            $receivable = CustomerReceivable::query()->create([
                'owner_id' => $ownerId,
                'customer_id' => (int) $validated['customer_id'],
                'document_number' => 'PENDING-' . uniqid(),
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'amount' => round((float) $validated['amount'], 2),
                'description' => $validated['description'],
                'reference_type' => $validated['reference_type'] ?? null,
                'reference_id' => $validated['reference_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $status,
                'user_id' => $ownerId,
                'issued_at' => $status === CustomerReceivable::STATUS_ISSUED ? now() : null,
                'issued_by' => $status === CustomerReceivable::STATUS_ISSUED ? $ownerId : null,
                'closed_at' => null,
                'closed_by' => null,
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);

            $receivable->update([
                'document_number' => 'CR-' . $receivable->issue_date->format('Y') . '-' . str_pad((string) $receivable->id, 6, '0', STR_PAD_LEFT),
            ]);

            $accountEntryResult = null;
            if ($receivable->isIssued()) {
                $accountEntryResult = $this->operationalAccountEntryService
                    ->upsertCustomerEntryFromReceivable($receivable->refresh(), $ownerId);
            }

            return [
                'receivable' => $receivable->refresh(),
                'account_entry_result' => $accountEntryResult,
            ];
        });

        /** @var CustomerReceivable $receivable */
        $receivable = $result['receivable'];
        $accountEntryResult = $result['account_entry_result'];
        $accountEntry = is_array($accountEntryResult)
            ? ($accountEntryResult['entry'] ?? null)
            : null;

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'customer_receivable',
            entityId: $receivable->id,
            payload: [
                'document_number' => $receivable->document_number,
                'customer_id' => $receivable->customer_id,
                'issue_date' => optional($receivable->issue_date)->format('Y-m-d'),
                'due_date' => optional($receivable->due_date)->format('Y-m-d'),
                'amount' => (float) $receivable->amount,
                'description' => $receivable->description,
                'status' => $receivable->status,
                'automatic_account_entry_id' => $accountEntry?->id,
                'automatic_account_entry_created' => (bool) ($accountEntryResult['created'] ?? false),
                'automatic_account_entry_changed' => (bool) ($accountEntryResult['changed'] ?? false),
            ],
            ownerId: $ownerId,
            userId: $ownerId,
        );

        return redirect()
            ->route('customer-receivables.show', $receivable)
            ->with('success', 'Conta a receber criada com sucesso.');
    }

    public function show(CustomerReceivable $receivable): View
    {
        $this->authorize('view', $receivable);

        $this->assertOwnedReceivable($receivable, (int) Auth::id());

        $receivable->load([
            'customer:id,code,name,email,phone,mobile',
            'user:id,name',
            'creator:id,name',
            'updater:id,name',
            'issuer:id,name',
            'closer:id,name',
            'accountEntry:id,owner_id,customer_id,type,amount,entry_date,due_date,reference_type,reference_id,user_id,created_at',
            'accountEntry.user:id,name',
        ]);

        return view('customers.receivables.show', [
            'receivable' => $receivable,
        ]);
    }

    public function edit(CustomerReceivable $receivable): View
    {
        $this->authorize('update', $receivable);

        $this->assertOwnedReceivable($receivable, (int) Auth::id());

        abort_if($receivable->isClosed(), 403, 'Documento fechado nao pode ser editado.');

        $customers = Customer::query()
            ->where('owner_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'payment_terms_days']);

        return view('customers.receivables.edit', [
            'receivable' => $receivable,
            'customers' => $customers,
        ]);
    }

    public function update(UpdateCustomerReceivableRequest $request, CustomerReceivable $receivable): RedirectResponse
    {
        $this->authorize('update', $receivable);

        $ownerId = (int) Auth::id();
        $this->assertOwnedReceivable($receivable, $ownerId);

        abort_if($receivable->isClosed(), 403, 'Documento fechado nao pode ser alterado.');

        $validated = $request->validated();

        $result = DB::transaction(function () use ($receivable, $validated, $ownerId): array {
            $receivable->update([
                'customer_id' => (int) $validated['customer_id'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'amount' => round((float) $validated['amount'], 2),
                'description' => $validated['description'],
                'reference_type' => $validated['reference_type'] ?? null,
                'reference_id' => $validated['reference_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => $ownerId,
            ]);

            $accountEntryResult = null;
            if ($receivable->isIssued()) {
                $accountEntryResult = $this->operationalAccountEntryService
                    ->upsertCustomerEntryFromReceivable($receivable->refresh(), $ownerId);
            }

            return [
                'receivable' => $receivable->refresh(),
                'account_entry_result' => $accountEntryResult,
            ];
        });

        /** @var CustomerReceivable $updatedReceivable */
        $updatedReceivable = $result['receivable'];
        $accountEntryResult = $result['account_entry_result'];
        $accountEntry = is_array($accountEntryResult)
            ? ($accountEntryResult['entry'] ?? null)
            : null;

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'customer_receivable',
            entityId: $updatedReceivable->id,
            payload: [
                'document_number' => $updatedReceivable->document_number,
                'customer_id' => $updatedReceivable->customer_id,
                'issue_date' => optional($updatedReceivable->issue_date)->format('Y-m-d'),
                'due_date' => optional($updatedReceivable->due_date)->format('Y-m-d'),
                'amount' => (float) $updatedReceivable->amount,
                'description' => $updatedReceivable->description,
                'status' => $updatedReceivable->status,
                'automatic_account_entry_id' => $accountEntry?->id,
                'automatic_account_entry_changed' => (bool) ($accountEntryResult['changed'] ?? false),
            ],
            ownerId: $ownerId,
            userId: $ownerId,
        );

        return redirect()
            ->route('customer-receivables.show', $updatedReceivable)
            ->with('success', 'Conta a receber atualizada com sucesso.');
    }

    public function issue(CustomerReceivable $receivable): RedirectResponse
    {
        $this->authorize('update', $receivable);

        $ownerId = (int) Auth::id();
        $this->assertOwnedReceivable($receivable, $ownerId);

        if (! $receivable->isDraft()) {
            return redirect()
                ->route('customer-receivables.show', $receivable)
                ->with('error', 'Apenas documentos em rascunho podem ser emitidos.');
        }

        $result = DB::transaction(function () use ($receivable, $ownerId): array {
            $receivable->update([
                'status' => CustomerReceivable::STATUS_ISSUED,
                'issued_at' => now(),
                'issued_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);

            $accountEntryResult = $this->operationalAccountEntryService
                ->upsertCustomerEntryFromReceivable($receivable->refresh(), $ownerId);

            return [
                'receivable' => $receivable->refresh(),
                'account_entry_result' => $accountEntryResult,
            ];
        });

        /** @var CustomerReceivable $issuedReceivable */
        $issuedReceivable = $result['receivable'];
        $accountEntryResult = $result['account_entry_result'];
        $accountEntry = $accountEntryResult['entry'] ?? null;

        $this->activityLogService->log(
            action: ActivityActions::STATUS_CHANGED,
            entity: 'customer_receivable',
            entityId: $issuedReceivable->id,
            payload: [
                'from' => CustomerReceivable::STATUS_DRAFT,
                'to' => CustomerReceivable::STATUS_ISSUED,
                'document_number' => $issuedReceivable->document_number,
                'automatic_account_entry_id' => $accountEntry?->id,
                'automatic_account_entry_created' => (bool) ($accountEntryResult['created'] ?? false),
            ],
            ownerId: $ownerId,
            userId: $ownerId,
        );

        return redirect()
            ->route('customer-receivables.show', $issuedReceivable)
            ->with('success', 'Documento emitido e lancamento automatico gerado na conta corrente.');
    }

    public function close(CustomerReceivable $receivable): RedirectResponse
    {
        $this->authorize('update', $receivable);

        $ownerId = (int) Auth::id();
        $this->assertOwnedReceivable($receivable, $ownerId);

        if (! $receivable->isIssued()) {
            return redirect()
                ->route('customer-receivables.show', $receivable)
                ->with('error', 'Apenas documentos emitidos podem ser fechados.');
        }

        $receivable->update([
            'status' => CustomerReceivable::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => $ownerId,
            'updated_by' => $ownerId,
        ]);

        $this->activityLogService->log(
            action: ActivityActions::STATUS_CHANGED,
            entity: 'customer_receivable',
            entityId: $receivable->id,
            payload: [
                'from' => CustomerReceivable::STATUS_ISSUED,
                'to' => CustomerReceivable::STATUS_CLOSED,
                'document_number' => $receivable->document_number,
            ],
            ownerId: $ownerId,
            userId: $ownerId,
        );

        return redirect()
            ->route('customer-receivables.show', $receivable)
            ->with('success', 'Documento fechado com sucesso.');
    }

    private function assertOwnedReceivable(CustomerReceivable $receivable, int $ownerId): void
    {
        abort_unless((int) $receivable->owner_id === $ownerId, 404);
    }
}
