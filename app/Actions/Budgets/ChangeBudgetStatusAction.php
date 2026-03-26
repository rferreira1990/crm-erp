<?php

namespace App\Actions\Budgets;

use App\Models\Budget;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChangeBudgetStatusAction
{
    public function execute(Budget $budget, string $newStatus): Budget
    {
        if (! in_array($newStatus, Budget::statuses(), true)) {
            throw new RuntimeException('O estado solicitado é inválido.');
        }

        if (! $budget->canChangeToStatus($newStatus)) {
            throw new RuntimeException('A transição de estado solicitada não é permitida.');
        }

        // 🔥 NOVO BLOQUEIO
        if ($budget->total <= 0 && $newStatus !== Budget::STATUS_DRAFT) {
            throw new RuntimeException('Não é possível mudar o estado de um orçamento com total igual a 0,00 €.');
        }

        if ($newStatus === Budget::STATUS_CREATED && $budget->items()->count() === 0) {
            throw new RuntimeException('Não é possível finalizar um orçamento sem linhas.');
        }

        DB::transaction(function () use ($budget, $newStatus) {
            if ($newStatus === Budget::STATUS_CREATED) {
                $budget->captureDocumentSnapshot();
            }

            $budget->update([
                'status' => $newStatus,
                'updated_by' => auth()->id(),
            ]);
        });

        return $budget->refresh();
    }
}
