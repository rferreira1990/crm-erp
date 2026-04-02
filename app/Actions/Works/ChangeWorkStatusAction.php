<?php

namespace App\Actions\Works;

use App\Models\Work;
use App\Models\WorkStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChangeWorkStatusAction
{
    public function execute(Work $work, string $newStatus, ?string $notes = null): Work
    {
        $newStatus = trim($newStatus);
        $notes = $notes !== null ? trim($notes) : null;
        $oldStatus = $work->status;

        if ($newStatus === '') {
            throw new RuntimeException('O novo estado é obrigatório.');
        }

        if (! array_key_exists($newStatus, Work::statuses())) {
            throw new RuntimeException('O estado selecionado não é válido.');
        }

        if ($oldStatus === $newStatus) {
            throw new RuntimeException('A obra já se encontra nesse estado.');
        }

        if (! $work->canChangeTo($newStatus)) {
            throw new RuntimeException('Não é possível alterar o estado da obra para o estado selecionado.');
        }

        if ($newStatus === Work::STATUS_COMPLETED && $work->hasPendingRequiredChecklistItems()) {
            $pendingRequired = $work->pendingRequiredChecklistItemsCount();

            throw new RuntimeException(
                'Não é possível concluir a obra. Existem ' . $pendingRequired . ' item(ns) obrigatório(s) por concluir nas checklists.'
            );
        }

        DB::transaction(function () use ($work, $oldStatus, $newStatus, $notes) {
            $updateData = [
                'status' => $newStatus,
                'updated_by' => Auth::id(),
            ];

            if ($newStatus === Work::STATUS_IN_PROGRESS && ! $work->start_date_actual) {
                $updateData['start_date_actual'] = now()->toDateString();
            }

            if ($newStatus === Work::STATUS_COMPLETED && ! $work->end_date_actual) {
                $updateData['end_date_actual'] = now()->toDateString();
            }

            $work->update($updateData);

            WorkStatusHistory::create([
                'work_id' => $work->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $notes !== '' ? $notes : null,
                'changed_by' => Auth::id(),
            ]);
        });

        return $work->fresh([
            'customer',
            'budget',
            'technicalManager',
            'team',
            'statusHistories.changedBy',
        ]);
    }
}
