<?php

namespace App\Actions\Budgets;

use App\Models\BudgetItem;
use App\Models\TaxExemptionReason;
use App\Models\TaxRate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpdateBudgetItemAction
{
    public function __construct(
        protected RecalculateBudgetTotalsAction $recalculateBudgetTotalsAction
    ) {
    }

    /**
     * @param array{
     *     quantity:float,
     *     unit_price:float,
     *     discount_percent:float,
     *     tax_rate_id:int,
     *     tax_exemption_reason_id:?int,
     *     notes:?string
     * } $data
     */
    public function execute(BudgetItem $budgetItem, array $data): BudgetItem
    {
        if ($budgetItem->budget && ! $budgetItem->budget->isLatestVersion()) {
            throw new RuntimeException('Esta versao do orcamento esta apenas para consulta. Cria uma nova versao para editar.');
        }

        if (! $budgetItem->budget || ! $budgetItem->budget->isEditable()) {
            throw new RuntimeException('Este orçamento já não pode ser editado porque não está em rascunho.');
        }

        return DB::transaction(function () use ($budgetItem, $data) {
            $quantity = round((float) $data['quantity'], 3);
            $unitPrice = round((float) $data['unit_price'], 2);
            $discountPercent = round((float) $data['discount_percent'], 2);

            $taxRate = TaxRate::query()->findOrFail($data['tax_rate_id']);
            $taxPercent = round((float) $taxRate->percent, 2);
            $isExempt = (bool) $taxRate->is_exempt;

            $taxExemptionReasonId = null;
            $taxExemptionReasonText = null;

            if ($isExempt) {
                $reasonId = $data['tax_exemption_reason_id'] ?: $taxRate->exemption_reason_id;

                if (! $reasonId) {
                    throw new RuntimeException('É obrigatório indicar o motivo de isenção para a taxa de IVA selecionada.');
                }

                $reason = TaxExemptionReason::query()->findOrFail($reasonId);

                $taxExemptionReasonId = $reason->id;
                $taxExemptionReasonText = $reason->code . ' - ' . $reason->description;
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
                'tax_rate_id' => $taxRate->id,
                'tax_rate_name' => $taxRate->name,
                'tax_percent' => $taxPercent,
                'tax_exemption_reason_id' => $taxExemptionReasonId,
                'tax_exemption_reason' => $taxExemptionReasonText,
                'notes' => $data['notes'] ?: null,
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
