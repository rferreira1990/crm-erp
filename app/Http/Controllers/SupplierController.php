<?php

namespace App\Http\Controllers;

use App\Http\Requests\Suppliers\StoreSupplierRequest;
use App\Http\Requests\Suppliers\UpdateSupplierRequest;
use App\Models\PaymentTerm;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'is_active' => $request->has('is_active')
                ? trim((string) $request->input('is_active'))
                : '',
        ];

        $suppliers = Supplier::query()
            ->with([
                'paymentTerm:id,name,days',
                'defaultTaxRate:id,name,percent',
                'primaryContact:id,supplier_id,name,email,phone,mobile,is_primary,is_active',
            ])
            ->search($filters['search'])
            ->when($filters['is_active'] !== '', function ($query) use ($filters) {
                $query->where('is_active', (bool) $filters['is_active']);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'filters'));
    }

    public function create(): View
    {
        $this->authorize('create', Supplier::class);

        $paymentTerms = PaymentTerm::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $taxRates = TaxRate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('suppliers.create', compact('paymentTerms', 'taxRates'));
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $this->authorize('create', Supplier::class);

        $supplier = Supplier::create([
            ...$request->validated(),
            'owner_id' => Auth::id(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'supplier',
            entityId: $supplier->id,
            payload: [
                'code' => $supplier->code,
                'name' => $supplier->name,
                'tax_number' => $supplier->tax_number,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'mobile' => $supplier->mobile,
                'payment_term_id' => $supplier->payment_term_id,
                'default_tax_rate_id' => $supplier->default_tax_rate_id,
                'is_active' => $supplier->is_active,
            ],
            ownerId: $supplier->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Fornecedor criado com sucesso.');
    }

    public function show(Supplier $supplier): View
    {
        $this->authorize('view', $supplier);

        $supplier->load([
            'paymentTerm:id,name,days',
            'defaultTaxRate:id,name,percent',
            'contacts',
        ]);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);

        $paymentTerms = PaymentTerm::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $taxRates = TaxRate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('suppliers.edit', compact('supplier', 'paymentTerms', 'taxRates'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $oldData = $supplier->only([
            'code',
            'name',
            'tax_number',
            'email',
            'phone',
            'mobile',
            'contact_person',
            'website',
            'external_reference',
            'address',
            'postal_code',
            'city',
            'country',
            'payment_term_id',
            'default_tax_rate_id',
            'default_discount_percent',
            'lead_time_days',
            'minimum_order_value',
            'free_shipping_threshold',
            'preferred_payment_method',
            'default_notes_for_purchases',
            'delivery_instructions',
            'habitual_order_email',
            'preferred_contact_method',
            'notes',
            'is_active',
        ]);

        $supplier->update([
            ...$request->validated(),
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'supplier',
            entityId: $supplier->id,
            payload: [
                'code' => $supplier->code,
                'old' => $oldData,
                'new' => $supplier->only(array_keys($oldData)),
            ],
            ownerId: $supplier->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Fornecedor atualizado com sucesso.');
    }

    public function toggleActive(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $newStatus = (bool) $validated['is_active'];
        $oldStatus = (bool) $supplier->is_active;

        if ($newStatus === $oldStatus) {
            return redirect()
                ->route('suppliers.show', $supplier)
                ->with('success', 'Estado do fornecedor mantido sem alteracoes.');
        }

        $supplier->update([
            'is_active' => $newStatus,
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::STATUS_CHANGED,
            entity: 'supplier',
            entityId: $supplier->id,
            payload: [
                'code' => $supplier->code,
                'old_is_active' => $oldStatus,
                'new_is_active' => $newStatus,
            ],
            ownerId: $supplier->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', $newStatus ? 'Fornecedor ativado com sucesso.' : 'Fornecedor desativado com sucesso.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        $payload = [
            'code' => $supplier->code,
            'name' => $supplier->name,
            'tax_number' => $supplier->tax_number,
            'email' => $supplier->email,
            'is_active' => $supplier->is_active,
        ];

        $supplier->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'supplier',
            entityId: $supplier->id,
            payload: $payload,
            ownerId: $supplier->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Fornecedor removido com sucesso.');
    }
}

