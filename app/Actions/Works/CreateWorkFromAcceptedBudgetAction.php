<?php

namespace App\Actions\Works;

use App\Models\Budget;
use App\Models\Work;
use App\Models\WorkStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateWorkFromAcceptedBudgetAction
{
    public function execute(Budget $budget): Work
    {
        if ($budget->status !== Budget::STATUS_ACCEPTED) {
            throw new RuntimeException('So e possivel criar obra a partir de orcamento aceite.');
        }

        $existingWork = Work::query()
            ->where('budget_id', $budget->id)
            ->first();

        if ($existingWork) {
            throw new RuntimeException('Este orcamento ja tem uma obra associada.');
        }

        return DB::transaction(function () use ($budget) {
            $nextId = ((int) Work::query()->lockForUpdate()->max('id')) + 1;
            $nameBase = trim((string) ($budget->designation ?: $budget->project_name ?: 'Obra'));

            $work = Work::create([
                'owner_id' => Auth::id(),
                'customer_id' => $budget->customer_id,
                'budget_id' => $budget->id,
                'code' => Work::generateCode($nextId),
                'name' => $nameBase . ' - ' . $budget->code,
                'status' => Work::STATUS_PLANNED,
                'location' => $budget->zone,
                'city' => $budget->snapshot_customer_city ?: $budget->customer?->city,
                'description' => $budget->notes,
                'other_costs' => 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            WorkStatusHistory::create([
                'work_id' => $work->id,
                'old_status' => null,
                'new_status' => Work::STATUS_PLANNED,
                'notes' => 'Obra criada a partir do orcamento ' . $budget->code . '.',
                'changed_by' => Auth::id(),
            ]);

            return $work;
        });
    }
}
