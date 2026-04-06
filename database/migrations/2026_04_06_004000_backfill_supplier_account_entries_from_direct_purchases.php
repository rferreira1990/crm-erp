<?php

use App\Models\PurchaseDirectPurchase;
use App\Models\SupplierAccountEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_direct_purchases') || ! Schema::hasTable('supplier_account_entries')) {
            return;
        }

        $referenceType = PurchaseDirectPurchase::class;
        $lastId = 0;

        while (true) {
            $rows = DB::table('purchase_direct_purchases as pdp')
                ->leftJoin('supplier_account_entries as sae', function ($join) use ($referenceType): void {
                    $join->on('sae.reference_id', '=', 'pdp.id')
                        ->where('sae.reference_type', '=', $referenceType)
                        ->where('sae.type', '=', SupplierAccountEntry::TYPE_PURCHASE_INVOICE)
                        ->whereColumn('sae.owner_id', 'pdp.owner_id');
                })
                ->where('pdp.id', '>', $lastId)
                ->whereNull('sae.id')
                ->orderBy('pdp.id')
                ->limit(200)
                ->get([
                    'pdp.id',
                    'pdp.owner_id',
                    'pdp.supplier_id',
                    'pdp.purchase_date',
                    'pdp.due_date',
                    'pdp.total_amount',
                    'pdp.document_number',
                    'pdp.external_reference',
                    'pdp.created_by',
                ]);

            if ($rows->isEmpty()) {
                break;
            }

            $now = now();
            $inserts = [];

            foreach ($rows as $row) {
                $ownerId = (int) ($row->owner_id ?? 0);
                if ($ownerId <= 0) {
                    $lastId = (int) $row->id;
                    continue;
                }

                $userId = (int) ($row->created_by ?: $ownerId);

                $inserts[] = [
                    'owner_id' => $ownerId,
                    'supplier_id' => (int) $row->supplier_id,
                    'entry_date' => $row->purchase_date,
                    'type' => SupplierAccountEntry::TYPE_PURCHASE_INVOICE,
                    'amount' => round((float) $row->total_amount, 2),
                    'description' => 'Compra direta ' . (string) $row->document_number,
                    'reference_type' => $referenceType,
                    'reference_id' => (int) $row->id,
                    'user_id' => $userId,
                    'due_date' => $row->due_date,
                    'notes' => $row->external_reference
                        ? 'Referencia externa: ' . (string) $row->external_reference
                        : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $lastId = (int) $row->id;
            }

            if ($inserts !== []) {
                DB::table('supplier_account_entries')->insert($inserts);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('supplier_account_entries')) {
            return;
        }

        DB::table('supplier_account_entries')
            ->where('reference_type', PurchaseDirectPurchase::class)
            ->where('type', SupplierAccountEntry::TYPE_PURCHASE_INVOICE)
            ->delete();
    }
};
