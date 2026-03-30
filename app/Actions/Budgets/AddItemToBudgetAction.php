<?php

namespace App\Actions\Budgets;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AddItemToBudgetAction
{
    public function __construct(
        protected RecalculateBudgetTotalsAction $recalculateBudgetTotalsAction
    ) {
    }

    /**
     * Adiciona uma nova linha ao orçamento com snapshot do artigo.
     *
     * @param array{
     *     item_id:int,
     *     quantity:float,
     *     discount_percent:float
     * } $data
     */
    public function execute(Budget $budget, array $data): BudgetItem
    {
        if (! $budget->isLatestVersion()) {
            throw new RuntimeException('Esta versao do orcamento esta apenas para consulta. Cria uma nova versao para editar.');
        }

        if (! $budget->isEditable()) {
            throw new RuntimeException('Este orçamento já não pode ser editado porque não está em rascunho.');
        }

        return DB::transaction(function () use ($budget, $data) {
            /** @var Item $item */
            $item = Item::query()
                ->with(['taxRate', 'unit'])
                ->findOrFail($data['item_id']);

            if (! $item->is_active) {
                throw new RuntimeException('Não é possível adicionar um artigo inativo ao orçamento.');
            }

            $quantity = round((float) $data['quantity'], 3);
            $discountPercent = round((float) $data['discount_percent'], 2);
            $unitPrice = round((float) ($item->sale_price ?? 0), 2);
            $taxPercent = round((float) ($item->taxRate?->percent ?? 0), 2);

            $lineSubtotal = round($quantity * $unitPrice, 2);
            $lineDiscountTotal = round($lineSubtotal * ($discountPercent / 100), 2);
            $lineTaxableBase = round($lineSubtotal - $lineDiscountTotal, 2);
            $lineTaxTotal = round($lineTaxableBase * ($taxPercent / 100), 2);
            $lineTotal = round($lineTaxableBase + $lineTaxTotal, 2);

            $nextSortOrder = ((int) $budget->items()->max('sort_order')) + 1;

            $budgetItem = $budget->items()->create([
                'item_id' => $item->id,
                'sort_order' => $nextSortOrder,
                'item_code' => $item->code,
                'item_name' => $item->name,
                'item_type' => $item->type,
                'description' => $item->description,
                'unit_name' => $item->unit?->name,
                'tax_rate_id' => $item->taxRate?->id,
                'tax_rate_name' => $item->taxRate?->name,
                'tax_percent' => $taxPercent,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'subtotal' => $lineSubtotal,
                'discount_total' => $lineDiscountTotal,
                'tax_total' => $lineTaxTotal,
                'total' => $lineTotal,
            ]);

            $this->recalculateBudgetTotalsAction->execute($budget);

            return $budgetItem->fresh(['item', 'taxRate']);
        });
    }
}
