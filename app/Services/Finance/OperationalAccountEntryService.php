<?php

namespace App\Services\Finance;

use App\Models\CustomerAccountEntry;
use App\Models\CustomerReceivable;
use App\Models\PurchaseDirectPurchase;
use App\Models\SupplierAccountEntry;

class OperationalAccountEntryService
{
    /**
     * Cria ou atualiza o lancamento operacional da conta corrente de fornecedor
     * a partir de uma compra direta. Mantem idempotencia por referencia do documento.
     *
     * @return array{entry: SupplierAccountEntry, created: bool, changed: bool}
     */
    public function upsertSupplierEntryFromDirectPurchase(PurchaseDirectPurchase $purchase, int $userId): array
    {
        $entry = SupplierAccountEntry::query()
            ->where('owner_id', (int) $purchase->owner_id)
            ->where('reference_type', PurchaseDirectPurchase::class)
            ->where('reference_id', (int) $purchase->id)
            ->where('type', SupplierAccountEntry::TYPE_PURCHASE_INVOICE)
            ->first();

        $attributes = [
            'supplier_id' => (int) $purchase->supplier_id,
            'entry_date' => $purchase->purchase_date,
            'due_date' => $purchase->due_date,
            'amount' => round((float) $purchase->total_amount, 2),
            'description' => sprintf('Compra direta %s', (string) $purchase->document_number),
            'notes' => $purchase->external_reference
                ? 'Referencia externa: ' . (string) $purchase->external_reference
                : null,
            'user_id' => $userId,
        ];

        if ($entry === null) {
            $entry = SupplierAccountEntry::query()->create([
                'owner_id' => (int) $purchase->owner_id,
                'type' => SupplierAccountEntry::TYPE_PURCHASE_INVOICE,
                'reference_type' => PurchaseDirectPurchase::class,
                'reference_id' => (int) $purchase->id,
                ...$attributes,
            ]);

            return [
                'entry' => $entry,
                'created' => true,
                'changed' => true,
            ];
        }

        $isChanged = false;
        foreach ($attributes as $field => $value) {
            if ($entry->{$field} != $value) {
                $entry->{$field} = $value;
                $isChanged = true;
            }
        }

        if ($isChanged) {
            $entry->save();
        }

        return [
            'entry' => $entry,
            'created' => false,
            'changed' => $isChanged,
        ];
    }

    /**
     * Indica se a automacao de cliente esta ativa no projeto.
     */
    public function customerAutomationIsReady(): bool
    {
        return true;
    }

    /**
     * Cria ou atualiza o lancamento operacional da conta corrente de cliente
     * a partir de um documento interno de conta a receber.
     *
     * @return array{entry: CustomerAccountEntry, created: bool, changed: bool}
     */
    public function upsertCustomerEntryFromReceivable(CustomerReceivable $receivable, int $userId): array
    {
        $entry = CustomerAccountEntry::query()
            ->where('owner_id', (int) $receivable->owner_id)
            ->where('reference_type', CustomerReceivable::class)
            ->where('reference_id', (int) $receivable->id)
            ->where('type', CustomerAccountEntry::TYPE_DEBIT)
            ->first();

        $description = mb_substr(
            sprintf('%s (%s)', (string) $receivable->description, (string) $receivable->document_number),
            0,
            255
        );

        $attributes = [
            'customer_id' => (int) $receivable->customer_id,
            'entry_date' => $receivable->issue_date,
            'due_date' => $receivable->due_date,
            'amount' => round((float) $receivable->amount, 2),
            'description' => $description,
            'notes' => $receivable->notes,
            'user_id' => $userId,
        ];

        if ($entry === null) {
            $entry = CustomerAccountEntry::query()->create([
                'owner_id' => (int) $receivable->owner_id,
                'type' => CustomerAccountEntry::TYPE_DEBIT,
                'reference_type' => CustomerReceivable::class,
                'reference_id' => (int) $receivable->id,
                ...$attributes,
            ]);

            return [
                'entry' => $entry,
                'created' => true,
                'changed' => true,
            ];
        }

        $isChanged = false;
        foreach ($attributes as $field => $value) {
            if ($entry->{$field} != $value) {
                $entry->{$field} = $value;
                $isChanged = true;
            }
        }

        if ($isChanged) {
            $entry->save();
        }

        return [
            'entry' => $entry,
            'created' => false,
            'changed' => $isChanged,
        ];
    }
}
