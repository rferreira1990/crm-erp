<?php

namespace App\Services\Finance;

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
     * Ponto unico para futuras automacoes da conta corrente de cliente.
     * Nesta fase nao existem documentos comerciais finais no projeto para gerar divida com seguranca.
     */
    public function customerAutomationIsReady(): bool
    {
        return false;
    }
}
