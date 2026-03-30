<?php

namespace App\Http\Controllers;

use App\Http\Requests\Suppliers\StoreSupplierContactRequest;
use App\Http\Requests\Suppliers\UpdateSupplierContactRequest;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierContactController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(StoreSupplierContactRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $contact = DB::transaction(function () use ($request, $supplier) {
            $data = $request->validated();
            $isPrimary = (bool) ($data['is_primary'] ?? false);
            $hasAnyContact = $supplier->contacts()->exists();

            if ($isPrimary) {
                $supplier->contacts()->update(['is_primary' => false]);
            }

            if (! $hasAnyContact && ! $isPrimary) {
                $data['is_primary'] = true;
            }

            return $supplier->contacts()->create([
                ...$data,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'supplier_contact',
            entityId: $contact->id,
            payload: [
                'supplier_id' => $supplier->id,
                'supplier_code' => $supplier->code,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'mobile' => $contact->mobile,
                'is_primary' => $contact->is_primary,
                'is_active' => $contact->is_active,
            ],
            ownerId: $supplier->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Contacto criado com sucesso.');
    }

    public function update(
        UpdateSupplierContactRequest $request,
        Supplier $supplier,
        SupplierContact $contact
    ): RedirectResponse {
        $this->authorize('update', $supplier);
        $this->ensureContactBelongsToSupplier($supplier, $contact);

        $oldData = $contact->only([
            'name',
            'role',
            'department',
            'email',
            'phone',
            'mobile',
            'notes',
            'is_primary',
            'is_active',
        ]);

        DB::transaction(function () use ($request, $supplier, $contact) {
            $data = $request->validated();
            $isPrimary = (bool) ($data['is_primary'] ?? false);

            if ($isPrimary) {
                $supplier->contacts()
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);
            }

            $contact->update([
                ...$data,
                'updated_by' => Auth::id(),
            ]);
        });

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'supplier_contact',
            entityId: $contact->id,
            payload: [
                'supplier_id' => $supplier->id,
                'supplier_code' => $supplier->code,
                'old' => $oldData,
                'new' => $contact->only(array_keys($oldData)),
            ],
            ownerId: $supplier->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Contacto atualizado com sucesso.');
    }

    public function destroy(Supplier $supplier, SupplierContact $contact): RedirectResponse
    {
        $this->authorize('update', $supplier);
        $this->ensureContactBelongsToSupplier($supplier, $contact);

        $wasPrimary = (bool) $contact->is_primary;
        $payload = [
            'supplier_id' => $supplier->id,
            'supplier_code' => $supplier->code,
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'mobile' => $contact->mobile,
            'is_primary' => $contact->is_primary,
            'is_active' => $contact->is_active,
        ];

        DB::transaction(function () use ($supplier, $contact, $wasPrimary) {
            $contact->delete();

            if ($wasPrimary) {
                $nextPrimary = $supplier->contacts()
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first();

                if ($nextPrimary) {
                    $nextPrimary->update(['is_primary' => true, 'updated_by' => Auth::id()]);
                }
            }
        });

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'supplier_contact',
            entityId: $contact->id,
            payload: $payload,
            ownerId: $supplier->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Contacto removido com sucesso.');
    }

    private function ensureContactBelongsToSupplier(Supplier $supplier, SupplierContact $contact): void
    {
        abort_unless((int) $contact->supplier_id === (int) $supplier->id, 404);
    }
}

