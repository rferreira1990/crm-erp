<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customers\StoreCustomerAccountEntryRequest;
use App\Models\Customer;
use App\Models\CustomerAccountEntry;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CustomerAccountEntryController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(StoreCustomerAccountEntryRequest $request, Customer $customer): RedirectResponse
    {
        $ownerId = (int) Auth::id();
        $customerOwnerId = $customer->owner_id !== null
            ? (int) $customer->owner_id
            : null;

        if ($customerOwnerId === null) {
            $createdBy = $customer->created_by !== null
                ? (int) $customer->created_by
                : null;

            if ($createdBy !== $ownerId) {
                abort(404);
            }

            $customer->forceFill(['owner_id' => $ownerId])->saveQuietly();
            $customerOwnerId = $ownerId;
        }

        if ($customerOwnerId !== $ownerId) {
            abort(404);
        }

        $this->authorize('create', [CustomerAccountEntry::class, $customer]);

        $validated = $request->validated();

        $entry = CustomerAccountEntry::query()->create([
            'owner_id' => $ownerId,
            'customer_id' => $customer->id,
            'entry_date' => $validated['entry_date'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'reference_type' => $validated['reference_type'] ?? null,
            'reference_id' => $validated['reference_id'] ?? null,
            'user_id' => $ownerId,
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->activityLogService->log(
            action: $entry->type === CustomerAccountEntry::TYPE_PAYMENT
                ? ActivityActions::RECEIPT_RECORDED
                : ActivityActions::CREATED,
            entity: 'customer_account_entry',
            entityId: $entry->id,
            payload: [
                'customer_id' => $customer->id,
                'customer_code' => $customer->code,
                'customer_name' => $customer->name,
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
            userId: $ownerId,
        );

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', $entry->type === CustomerAccountEntry::TYPE_PAYMENT
                ? 'Recebimento registado com sucesso.'
                : 'Movimento de conta corrente registado com sucesso.');
    }
}
