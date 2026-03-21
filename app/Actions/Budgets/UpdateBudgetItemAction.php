<?php

namespace App\Actions\Budgets;

use App\Models\BudgetItem;
use Illuminate\Support\Facades\DB;

class UpdateBudgetItemAction
{
    public function __construct(
        protected RecalculateBudgetTotalsAction $recalculateBudgetTotalsAction
    ) {
    }

    /**
     * Atualiza uma linha de orçamento e recalcula os seus totais.
     *
     * @param array{
     *     quantity:float,
     *     discount_percent:float
     * } $data
     */
    public function execute(BudgetItem $budgetItem, array $data): BudgetItem
    {
        return DB::transaction(function () use ($budgetItem, $data) {
            $quantity = round((float) $data['quantity'], 3);
            $discountPercent = round((float) $data['discount_percent'], 2);
            $unitPrice = round((float) $budgetItem->unit_price, 2);
            $taxPercent = round((float) $budgetItem->tax_percent, 2);

            $lineSubtotal = round($quantity * $unitPrice, 2);
            $lineDiscountTotal = round($lineSubtotal * ($discountPercent / 100), 2);
            $lineTaxableBase = round($lineSubtotal - $lineDiscountTotal, 2);
            $lineTaxTotal = round($lineTaxableBase * ($taxPercent / 100), 2);
            $lineTotal = round($lineTaxableBase + $lineTaxTotal, 2);

            $budgetItem->update([
                'quantity' => $quantity,
                'discount_percent' => $discountPercent,
                'subtotal' => $lineSubtotal,
                'discount_total' => $lineDiscountTotal,
                'tax_total' => $lineTaxTotal,
                'total' => $lineTotal,
            ]);

            $this->recalculateBudgetTotalsAction->execute($budgetItem->budget);

            return $budgetItem->fresh(['budget', 'item', 'taxRate']);
        });
    }
}
