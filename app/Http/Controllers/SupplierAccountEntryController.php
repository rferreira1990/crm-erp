<?php

namespace App\Http\Controllers;

use App\Http\Requests\Suppliers\StoreSupplierAccountEntryRequest;
use App\Models\Supplier;
use App\Models\SupplierAccountEntry;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SupplierAccountEntryController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(StoreSupplierAccountEntryRequest $request, Supplier $supplier): RedirectResponse
    {
        if ((int) $supplier->owner_id !== (int) Auth::id()) {
            abort(404);
        }

        $this->authorize('create', [SupplierAccountEntry::class, $supplier]);

        $validated = $request->validated();

        $entry = SupplierAccountEntry::query()->create([
            'owner_id' => Auth::id(),
            'supplier_id' => $supplier->id,
            'entry_date' => $validated['entry_date'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'reference_type' => $validated['reference_type'] ?? null,
            'reference_id' => $validated['reference_id'] ?? null,
            'user_id' => Auth::id(),
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->activityLogService->log(
            action: $entry->type === SupplierAccountEntry::TYPE_PAYMENT
                ? ActivityActions::PAYMENT_RECORDED
                : ActivityActions::CREATED,
            entity: 'supplier_account_entry',
            entityId: $entry->id,
            payload: [
                'supplier_id' => $supplier->id,
                'supplier_code' => $supplier->code,
                'supplier_name' => $supplier->name,
                'type' => $entry->type,
                'type_label' => $entry->typeLabel(),
                'amount' => (float) $entry->amount,
                'signed_amount' => $entry->signedAmount(),
                'entry_date' => $entry->entry_date?->toDateString(),
                'due_date' => $entry->due_date?->toDateString(),
                'description' => $entry->description,
                'reference_type' => $entry->reference_type,
                'reference_id' => $entry->reference_id,
            ],
            ownerId: (int) $entry->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', $entry->type === SupplierAccountEntry::TYPE_PAYMENT
                ? 'Pagamento registado com sucesso.'
                : 'Movimento de conta corrente registado com sucesso.');
    }
}

