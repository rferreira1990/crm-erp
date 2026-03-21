<?php

namespace App\Actions\Budgets;

use App\Models\Budget;
use RuntimeException;

class ChangeBudgetStatusAction
{
    /**
     * Altera o estado do orçamento se a transição for válida.
     */
    public function execute(Budget $budget, string $newStatus): Budget
    {
        $newStatus = trim($newStatus);

        if (! $budget->canChangeToStatus($newStatus)) {
            throw new RuntimeException(
                "Não é permitido mudar o orçamento de {$budget->statusLabel()} para {$this->statusLabel($newStatus)}."
            );
        }

        if ($newStatus === 'sent' && $budget->items()->count() === 0) {
            throw new RuntimeException('Não é possível enviar um orçamento sem linhas.');
        }

        $budget->update([
            'status' => $newStatus,
        ]);

        return $budget->fresh([
            'customer',
            'creator',
            'updater',
            'items.item',
            'items.taxRate',
        ]);
    }

    /**
     * Nome legível para mensagens.
     */
    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Rascunho',
            'sent' => 'Enviado',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            default => $status,
        };
    }
}
