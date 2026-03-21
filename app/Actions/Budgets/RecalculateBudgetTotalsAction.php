<?php

namespace App\Actions\Budgets;

use App\Models\Budget;

class RecalculateBudgetTotalsAction
{
    /**
     * Recalcula todos os totais do orçamento com base nas linhas.
     */
    public function execute(Budget $budget): Budget
    {
        $budget->loadMissing('items');

        $subtotal = (float) $budget->items->sum(fn ($line) => (float) $line->subtotal);
        $discountTotal = (float) $budget->items->sum(fn ($line) => (float) $line->discount_total);
        $taxTotal = (float) $budget->items->sum(fn ($line) => (float) $line->tax_total);
        $total = (float) $budget->items->sum(fn ($line) => (float) $line->total);

        $budget->update([
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'total' => round($total, 2),
        ]);

        return $budget->fresh(['customer', 'items']);
    }
}
