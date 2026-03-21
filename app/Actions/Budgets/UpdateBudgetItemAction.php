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
            $unitPrice = round((float) $data['unit_price'], 2);
            $discountPercent = round((float) $data['discount_percent'], 2);
            $taxPercent = round((float) $data['tax_percent'], 2);

            // Regra IVA
            $taxExemptionReason = null;
            if ($taxPercent == 0) {
                $taxExemptionReason = $data['tax_exemption_reason'] ?? null;
            }

            $lineSubtotal = round($quantity * $unitPrice, 2);
            $lineDiscountTotal = round($lineSubtotal * ($discountPercent / 100), 2);
            $lineTaxableBase = round($lineSubtotal - $lineDiscountTotal, 2);
            $lineTaxTotal = round($lineTaxableBase * ($taxPercent / 100), 2);
            $lineTotal = round($lineTaxableBase + $lineTaxTotal, 2);

            $budgetItem->update([
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'tax_percent' => $taxPercent,
                'tax_exemption_reason' => $taxExemptionReason,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $lineSubtotal,
                'discount_total' => $lineDiscountTotal,
                'tax_total' => $lineTaxTotal,
                'total' => $lineTotal,
            ]);

            $this->recalculateBudgetTotalsAction->execute($budgetItem->budget);

            return $budgetItem->fresh();
        });
    }
}
