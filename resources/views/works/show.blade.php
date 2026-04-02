@extends('layouts.admin')

@section('title', 'Detalhe da Obra')

@section('content')
@php
    $canUpdateWork = auth()->user()?->can('works.update');
    $canManageOperationalData = $canUpdateWork && $work->isEditable();

    $statusClasses = [
        \App\Models\Work::STATUS_PLANNED => 'bg-secondary',
        \App\Models\Work::STATUS_IN_PROGRESS => 'bg-primary',
        \App\Models\Work::STATUS_SUSPENDED => 'bg-warning text-dark',
        \App\Models\Work::STATUS_COMPLETED => 'bg-success',
        \App\Models\Work::STATUS_CANCELLED => 'bg-danger',
    ];

    $taskStatuses = \App\Models\WorkTask::statuses();

    $statusClass = $statusClasses[$work->status] ?? 'bg-dark';
    $statusLabel = \App\Models\Work::statuses()[$work->status] ?? $work->status;

    $plannedRevenue = $work->plannedRevenue();
    $materialsCost = $work->materialsCost();
    $laborCost = $work->laborCost();
    $laborHours = $work->dailyLaborHours();
    $manualOtherCosts = $work->manualOtherCosts();
    $expensesCost = $work->expensesCost();
    $otherCostsTotal = $work->otherCosts();
    $totalCosts = $work->totalCosts();
    $grossMargin = $work->estimatedGrossMargin();
    $grossMarginPercent = $plannedRevenue > 0 ? ($grossMargin / $plannedRevenue) * 100 : null;
    $pendingRequiredChecklistItems = $work->pendingRequiredChecklistItemsCount();
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">{{ $work->name }}</h2>
        <div class="text-muted">Codigo: <strong>{{ $work->code }}</strong></div>
    </div>

    <div class="d-flex gap-2">
        @can('works.view')
            <a href="{{ route('works.checklists.index', $work) }}" class="btn btn-outline-primary">Checklists</a>
        @endcan

        @can('works.view')
            <a href="{{ route('works.files.index', $work) }}" class="btn btn-outline-primary">Ficheiros</a>
        @endcan

        @can('works.update')
            <a href="{{ route('works.edit', $work) }}" class="btn btn-primary">Editar</a>
        @endcan

        @can('works.delete')
            @if ($work->canBeDeleted())
            <form method="POST" action="{{ route('works.destroy', $work) }}" onsubmit="return confirm('Tens a certeza que queres apagar esta obra?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Apagar</button>
            </form>
            @endif
        @endcan

        <a href="{{ route('works.index') }}" class="btn btn-outline-secondary">Voltar</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if (! $work->isEditable())
    <div class="alert alert-info">
        Esta obra encontra-se {{ strtolower($statusLabel) }}. Registos operacionais (planeamento, diario e custos) estao bloqueados.
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><strong>Dados principais</strong></div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <div><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cliente</label>
                        <div>{{ $work->customer?->name ?? '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tipo de obra</label>
                        <div>{{ $work->work_type ?: '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Responsavel tecnico</label>
                        <div>{{ $work->technicalManager?->name ?? '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Orcamento associado</label>
                        <div>
                            @if ($work->budget)
                                <a href="{{ route('budgets.show', $work->budget) }}">{{ $work->budget->code }}</a>
                            @else
                                -
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Local</label>
                        <div>{{ $work->location ?: '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Codigo postal</label>
                        <div>{{ $work->postal_code ?: '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cidade</label>
                        <div>{{ $work->city ?: '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Inicio previsto</label>
                        <div>{{ $work->start_date_planned?->format('d/m/Y') ?? '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Fim previsto</label>
                        <div>{{ $work->end_date_planned?->format('d/m/Y') ?? '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Inicio real</label>
                        <div>{{ $work->start_date_actual?->format('d/m/Y') ?? '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Fim real</label>
                        <div>{{ $work->end_date_actual?->format('d/m/Y') ?? '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Outros custos manuais</label>
                        <div>{{ number_format((float) $manualOtherCosts, 2, ',', '.') }} &euro;</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descricao</label>
                        <div>{!! nl2br(e($work->description ?: '-')) !!}</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notas internas</label>
                        <div>{!! nl2br(e($work->internal_notes ?: '-')) !!}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Tarefas / planeamento</strong>
                <span class="badge bg-light text-dark border">{{ $work->tasks->count() }}</span>
            </div>

            <div class="card-body">
                <div class="alert alert-light border mb-4">
                    Esta area e apenas para planear/agendar tarefas. O registo de materiais aplicados e horas executadas fica no Diario de Obra.
                </div>

                @if ($canManageOperationalData)
                    <form method="POST" action="{{ route('works.tasks.store', $work) }}" class="border rounded p-3 mb-4 bg-light">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="task_title" class="form-label">Titulo <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="task_title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                            </div>

                            <div class="col-md-3">
                                <label for="task_status" class="form-label">Estado</label>
                                <select name="status" id="task_status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($taskStatuses as $status => $label)
                                        <option value="{{ $status }}" @selected(old('status', \App\Models\WorkTask::STATUS_PLANNED) === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="assigned_user_id" class="form-label">Responsavel</label>
                                <select name="assigned_user_id" id="assigned_user_id" class="form-select @error('assigned_user_id') is-invalid @enderror">
                                    <option value="">Sem atribuicao</option>
                                    @foreach ($assignableUsers as $user)
                                        <option value="{{ $user->id }}" @selected((int) old('assigned_user_id') === (int) $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="planned_date" class="form-label">Data prevista</label>
                                <input type="date" name="planned_date" id="planned_date" class="form-control @error('planned_date') is-invalid @enderror" value="{{ old('planned_date') }}">
                            </div>

                            <div class="col-md-3">
                                <label for="planned_start_time" class="form-label">Hora inicio</label>
                                <input type="time" name="planned_start_time" id="planned_start_time" class="form-control @error('planned_start_time') is-invalid @enderror" value="{{ old('planned_start_time') }}">
                            </div>

                            <div class="col-md-3">
                                <label for="planned_end_time" class="form-label">Hora fim</label>
                                <input type="time" name="planned_end_time" id="planned_end_time" class="form-control @error('planned_end_time') is-invalid @enderror" value="{{ old('planned_end_time') }}">
                            </div>

                            <div class="col-md-3">
                                <label for="sort_order" class="form-label">Ordem</label>
                                <input type="number" name="sort_order" id="sort_order" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order') }}">
                            </div>

                            <div class="col-12">
                                <label for="task_description" class="form-label">Descricao</label>
                                <textarea name="description" id="task_description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Adicionar tarefa</button>
                            </div>
                        </div>
                    </form>
                @endif

                @if ($work->tasks->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tarefa</th>
                                    <th>Estado</th>
                                    <th>Responsavel</th>
                                    <th>Planeamento</th>
                                    <th>Concluida em</th>
                                    <th>Ordem</th>
                                    @if ($canManageOperationalData)
                                        <th>Acoes</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($work->tasks as $task)
                                    @php
                                        $taskStatusLabel = $taskStatuses[$task->status] ?? $task->status;
                                        $taskStatusClass = $task->status === \App\Models\WorkTask::STATUS_COMPLETED
                                            ? 'bg-success'
                                            : ($task->status === \App\Models\WorkTask::STATUS_IN_PROGRESS
                                                ? 'bg-primary'
                                                : ($task->status === \App\Models\WorkTask::STATUS_CANCELLED ? 'bg-danger' : 'bg-secondary'));
                                        $taskEditId = 'task-edit-' . $task->id;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $task->title }}</div>
                                            @if ($task->description)
                                                <div class="small text-muted">{{ $task->description }}</div>
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $taskStatusClass }}">{{ $taskStatusLabel }}</span></td>
                                        <td>{{ $task->assignedUser?->name ?? '-' }}</td>
                                        <td>
                                            <div>{{ $task->planned_date?->format('d/m/Y') ?? '-' }}</div>
                                            @if ($task->planned_start_time || $task->planned_end_time)
                                                <div class="small text-muted">{{ $task->planned_start_time ?: '--:--' }} - {{ $task->planned_end_time ?: '--:--' }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $task->completed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td>{{ $task->sort_order }}</td>
                                        @if ($canManageOperationalData)
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#{{ $taskEditId }}">Editar</button>

                                                    @if ($task->status !== \App\Models\WorkTask::STATUS_COMPLETED)
                                                        <form method="POST" action="{{ route('works.tasks.complete', [$work, $task]) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="btn btn-sm btn-outline-success">Concluir</button>
                                                        </form>
                                                    @endif

                                                    <form method="POST" action="{{ route('works.tasks.destroy', [$work, $task]) }}" onsubmit="return confirm('Remover esta tarefa?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>

                                    @if ($canManageOperationalData)
                                        <tr class="collapse" id="{{ $taskEditId }}">
                                            <td colspan="7" class="bg-light">
                                                <form method="POST" action="{{ route('works.tasks.update', [$work, $task]) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="row g-2">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Titulo</label>
                                                            <input type="text" name="title" class="form-control" value="{{ $task->title }}" required>
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Estado</label>
                                                            <select name="status" class="form-select">
                                                                @foreach ($taskStatuses as $status => $label)
                                                                    <option value="{{ $status }}" @selected($task->status === $status)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <label class="form-label">Responsavel</label>
                                                            <select name="assigned_user_id" class="form-select">
                                                                <option value="">Sem atribuicao</option>
                                                                @foreach ($assignableUsers as $user)
                                                                    <option value="{{ $user->id }}" @selected((int) $task->assigned_user_id === (int) $user->id)>{{ $user->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="col-md-1">
                                                            <label class="form-label">Ordem</label>
                                                            <input type="number" name="sort_order" min="0" class="form-control" value="{{ $task->sort_order }}">
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Data</label>
                                                            <input type="date" name="planned_date" class="form-control" value="{{ $task->planned_date?->format('Y-m-d') }}">
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Hora inicio</label>
                                                            <input type="time" name="planned_start_time" class="form-control" value="{{ $task->planned_start_time ? substr((string) $task->planned_start_time, 0, 5) : '' }}">
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Hora fim</label>
                                                            <input type="time" name="planned_end_time" class="form-control" value="{{ $task->planned_end_time ? substr((string) $task->planned_end_time, 0, 5) : '' }}">
                                                        </div>

                                                        <div class="col-md-8">
                                                            <label class="form-label">Descricao</label>
                                                            <input type="text" name="description" class="form-control" value="{{ $task->description }}">
                                                        </div>

                                                        <div class="col-12 d-flex justify-content-end">
                                                            <button type="submit" class="btn btn-sm btn-primary">Guardar tarefa</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted">Sem tarefas registadas para esta obra.</div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Checklists</strong>
                <span class="badge bg-light text-dark border">{{ (int) $checklistsCount }}</span>
            </div>
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    Gestao de checklists foi movida para pagina propria para simplificar esta ficha.
                </div>
                <a href="{{ route('works.checklists.index', $work) }}" class="btn btn-sm btn-outline-primary">Abrir checklists</a>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Diario de obra</strong>
                <span class="badge bg-light text-dark border">{{ $dailyReports->count() }}</span>
            </div>

            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="text-muted small">
                        O Diario concentra horas executadas e materiais aplicados. A entrada separada de materiais/mao de obra deixou de ser necessaria.
                    </div>
                    <div class="d-flex gap-2">
                        @if ($canManageOperationalData)
                            <a href="{{ route('works.daily-reports.create', $work) }}" class="btn btn-sm btn-primary">Novo registo</a>
                        @endif
                        <a href="{{ route('works.daily-reports.index', $work) }}" class="btn btn-sm btn-outline-secondary">Ver diario completo</a>
                    </div>
                </div>

                @if ($dailyReports->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Estado</th>
                                    <th>Horas</th>
                                    <th>Materiais</th>
                                    <th>Custo materiais (estim.)</th>
                                    <th>Custo mao de obra (estim.)</th>
                                    <th>Registado por</th>
                                    <th>Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dailyReports as $report)
                                    @php
                                        $reportMaterialsCost = (float) $report->items->sum(function ($reportItem) {
                                            return $reportItem->total_cost_snapshot !== null
                                                ? (float) $reportItem->total_cost_snapshot
                                                : (float) $reportItem->quantity * (float) ($reportItem->unit_cost_snapshot ?? $reportItem->item?->cost_price ?? 0);
                                        });
                                        $reportLaborCost = $report->labor_cost_total_snapshot !== null
                                            ? (float) $report->labor_cost_total_snapshot
                                            : (float) $report->hours_spent * (float) ($report->user_hourly_cost_snapshot ?? $report->user?->hourly_cost ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ $report->report_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $report->day_status_label }}</span></td>
                                        <td>{{ number_format((float) $report->hours_spent, 2, ',', '.') }}</td>
                                        <td>{{ $report->items_count }}</td>
                                        <td>{{ number_format($reportMaterialsCost, 2, ',', '.') }} &euro;</td>
                                        <td>{{ number_format($reportLaborCost, 2, ',', '.') }} &euro;</td>
                                        <td>{{ $report->user?->name ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <a href="{{ route('works.daily-reports.show', [$work, $report]) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                                @if ($canManageOperationalData)
                                                    <a href="{{ route('works.daily-reports.edit', [$work, $report]) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                                    <form method="POST" action="{{ route('works.daily-reports.destroy', [$work, $report]) }}" onsubmit="return confirm('Remover este registo diario?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted">Sem registos diarios para esta obra.</div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mb-4" id="work-files-section">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Ficheiros</strong>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border">{{ $workFiles->count() }}</span>
                    <a href="{{ route('works.files.index', $work) }}" class="btn btn-sm btn-outline-secondary">Ver todos</a>
                </div>
            </div>
            <div class="card-body">
                @if ($canUpdateWork)
                    <div class="border rounded p-3 mb-4 bg-light">
                        @include('works.files.partials.upload-form', [
                            'work' => $work,
                            'dailyReportOptions' => $dailyReportOptions,
                        ])
                    </div>
                @endif

                @if ($workFiles->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Diario</th>
                                    <th>Tipo</th>
                                    <th>Tamanho</th>
                                    <th>Data</th>
                                    <th>Utilizador</th>
                                    <th>Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($workFiles as $workFile)
                                    <tr>
                                        <td>{{ $workFile->original_name }}</td>
                                        <td>{{ \App\Models\WorkFile::categories()[$workFile->category] ?? $workFile->category }}</td>
                                        <td>{{ $workFile->dailyReport?->report_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $workFile->mime_type }}</td>
                                        <td>{{ $workFile->readable_size }}</td>
                                        <td>{{ $workFile->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td>{{ $workFile->user?->name ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <a href="{{ route('works.files.download', [$work, $workFile]) }}" class="btn btn-sm btn-outline-primary">
                                                    Download
                                                </a>
                                                @if ($canUpdateWork)
                                                    <form method="POST" action="{{ route('works.files.destroy', [$work, $workFile]) }}" onsubmit="return confirm('Remover este ficheiro?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted">Sem ficheiros associados a esta obra.</div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mb-4" id="expenses-section">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Outros custos</strong>
                <span class="badge bg-light text-dark border">{{ number_format($expensesCost, 2, ',', '.') }} &euro;</span>
            </div>

            <div class="card-body">
                @if ($canManageOperationalData)
                    <form method="POST" action="{{ route('works.expenses.store', $work) }}" class="border rounded p-3 mb-4 bg-light work-expense-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select name="type" class="form-select expense-type" required>
                                    @foreach ($expenseTypes as $expenseType => $expenseTypeLabel)
                                        <option value="{{ $expenseType }}" @selected(old('type', \App\Models\WorkExpense::TYPE_OTHER) === $expenseType)>{{ $expenseTypeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Data <span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', now()->format('Y-m-d')) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Descricao <span class="text-danger">*</span></label>
                                <input type="text" name="description" class="form-control" value="{{ old('description') }}" maxlength="255" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Utilizador associado</label>
                                <select name="user_id" class="form-select">
                                    <option value="">Sem utilizador</option>
                                    @foreach ($expenseUsers as $expenseUser)
                                        <option value="{{ $expenseUser->id }}" @selected((int) old('user_id') === (int) $expenseUser->id)>
                                            {{ $expenseUser->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 expense-km-wrapper">
                                <label class="form-label">Km</label>
                                <input type="number" name="km" class="form-control expense-km" min="0.001" step="0.001" value="{{ old('km') }}">
                            </div>

                            <div class="col-md-2 expense-qty-wrapper">
                                <label class="form-label">Qtd</label>
                                <input type="number" name="qty" class="form-control expense-qty" min="0.001" step="0.001" value="{{ old('qty') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Custo unit.</label>
                                <input type="number" name="unit_cost" class="form-control expense-unit-cost" min="0" step="0.01" value="{{ old('unit_cost') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Custo total</label>
                                <input type="number" name="total_cost" class="form-control expense-total-cost" min="0" step="0.01" value="{{ old('total_cost') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Doc/recibo</label>
                                <input type="text" name="receipt_number" class="form-control" value="{{ old('receipt_number') }}" maxlength="100">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Fornecedor</label>
                                <input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name') }}" maxlength="150">
                            </div>

                            <div class="col-md-4 expense-travel-wrapper">
                                <label class="form-label">Origem</label>
                                <input type="text" name="from_location" class="form-control" value="{{ old('from_location') }}" maxlength="255">
                            </div>

                            <div class="col-md-4 expense-travel-wrapper">
                                <label class="form-label">Destino</label>
                                <input type="text" name="to_location" class="form-control" value="{{ old('to_location') }}" maxlength="255">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" maxlength="5000">
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Adicionar custo</button>
                            </div>
                        </div>
                    </form>
                @endif

                @if ($work->expenses->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Descricao</th>
                                    <th>Associado</th>
                                    <th>Custo total</th>
                                    <th>Doc</th>
                                    @if ($canManageOperationalData)
                                        <th>Acoes</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($work->expenses as $expense)
                                    @php
                                        $expenseEditId = 'expense-edit-' . $expense->id;
                                        $expenseColspan = $canManageOperationalData ? 7 : 6;
                                    @endphp
                                    <tr>
                                        <td>{{ $expense->expense_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $expenseTypes[$expense->type] ?? $expense->type }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $expense->description }}</div>
                                            @if ($expense->notes)
                                                <div class="small text-muted">{{ $expense->notes }}</div>
                                            @endif
                                            @if ($expense->type === \App\Models\WorkExpense::TYPE_TRAVEL_KM)
                                                <div class="small text-muted">
                                                    {{ number_format((float) ($expense->km ?? 0), 3, ',', '.') }} km
                                                    @if ($expense->from_location || $expense->to_location)
                                                        Â· {{ $expense->from_location ?: '-' }} ? {{ $expense->to_location ?: '-' }}
                                                    @endif
                                                </div>
                                            @elseif ($expense->qty !== null || $expense->unit_cost !== null)
                                                <div class="small text-muted">
                                                    {{ $expense->qty !== null ? number_format((float) $expense->qty, 3, ',', '.') : '-' }}
                                                    x
                                                    {{ $expense->unit_cost !== null ? number_format((float) $expense->unit_cost, 2, ',', '.') . ' â‚¬' : '-' }}
                                                </div>
                                            @endif
                                            @if ($expense->supplier_name)
                                                <div class="small text-muted">Fornecedor: {{ $expense->supplier_name }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $expense->user?->name ?? '-' }}</td>
                                        <td class="fw-semibold">{{ number_format((float) $expense->total_cost, 2, ',', '.') }} &euro;</td>
                                        <td>{{ $expense->receipt_number ?: '-' }}</td>
                                        @if ($canManageOperationalData)
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#{{ $expenseEditId }}">Editar</button>
                                                    <form method="POST" action="{{ route('works.expenses.destroy', [$work, $expense]) }}" onsubmit="return confirm('Remover este custo adicional?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>

                                    @if ($canManageOperationalData)
                                        <tr class="collapse" id="{{ $expenseEditId }}">
                                            <td colspan="{{ $expenseColspan }}" class="bg-light">
                                                <form method="POST" action="{{ route('works.expenses.update', [$work, $expense]) }}" class="work-expense-form">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="row g-2">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Tipo</label>
                                                            <select name="type" class="form-select expense-type" required>
                                                                @foreach ($expenseTypes as $expenseType => $expenseTypeLabel)
                                                                    <option value="{{ $expenseType }}" @selected($expense->type === $expenseType)>{{ $expenseTypeLabel }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Data</label>
                                                            <input type="date" name="expense_date" class="form-control" value="{{ $expense->expense_date?->format('Y-m-d') }}" required>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <label class="form-label">Descricao</label>
                                                            <input type="text" name="description" class="form-control" value="{{ $expense->description }}" maxlength="255" required>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <label class="form-label">Utilizador associado</label>
                                                            <select name="user_id" class="form-select">
                                                                <option value="">Sem utilizador</option>
                                                                @foreach ($expenseUsers as $expenseUser)
                                                                    <option value="{{ $expenseUser->id }}" @selected((int) $expense->user_id === (int) $expenseUser->id)>
                                                                        {{ $expenseUser->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="col-md-2 expense-km-wrapper">
                                                            <label class="form-label">Km</label>
                                                            <input type="number" name="km" class="form-control expense-km" min="0.001" step="0.001" value="{{ $expense->km !== null ? number_format((float) $expense->km, 3, '.', '') : '' }}">
                                                        </div>

                                                        <div class="col-md-2 expense-qty-wrapper">
                                                            <label class="form-label">Qtd</label>
                                                            <input type="number" name="qty" class="form-control expense-qty" min="0.001" step="0.001" value="{{ $expense->qty !== null ? number_format((float) $expense->qty, 3, '.', '') : '' }}">
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Custo unit.</label>
                                                            <input type="number" name="unit_cost" class="form-control expense-unit-cost" min="0" step="0.01" value="{{ $expense->unit_cost !== null ? number_format((float) $expense->unit_cost, 2, '.', '') : '' }}">
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Custo total</label>
                                                            <input type="number" name="total_cost" class="form-control expense-total-cost" min="0" step="0.01" value="{{ number_format((float) $expense->total_cost, 2, '.', '') }}">
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Doc/recibo</label>
                                                            <input type="text" name="receipt_number" class="form-control" value="{{ $expense->receipt_number }}" maxlength="100">
                                                        </div>

                                                        <div class="col-md-4">
                                                            <label class="form-label">Fornecedor</label>
                                                            <input type="text" name="supplier_name" class="form-control" value="{{ $expense->supplier_name }}" maxlength="150">
                                                        </div>

                                                        <div class="col-md-4 expense-travel-wrapper">
                                                            <label class="form-label">Origem</label>
                                                            <input type="text" name="from_location" class="form-control" value="{{ $expense->from_location }}" maxlength="255">
                                                        </div>

                                                        <div class="col-md-4 expense-travel-wrapper">
                                                            <label class="form-label">Destino</label>
                                                            <input type="text" name="to_location" class="form-control" value="{{ $expense->to_location }}" maxlength="255">
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label">Notas</label>
                                                            <input type="text" name="notes" class="form-control" value="{{ $expense->notes }}" maxlength="5000">
                                                        </div>

                                                        <div class="col-12 d-flex justify-content-end">
                                                            <button type="submit" class="btn btn-sm btn-primary">Guardar custo</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted">Sem outros custos registados para esta obra.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><strong>Equipa e estado</strong></div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="small text-muted">Responsavel tecnico</div>
                    <div class="fw-semibold">{{ $work->technicalManager?->name ?? '-' }}</div>
                </div>

                <div class="mb-3">
                    <div class="small text-muted">Equipa da obra</div>
                    @if ($work->team->count())
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach ($work->team as $teamUser)
                                <span class="badge bg-light text-dark border">{{ $teamUser->name }}</span>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted">Sem elementos de equipa definidos.</div>
                    @endif
                </div>

                <div>
                    <div class="small text-muted">Estado atual</div>
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                @if ($pendingRequiredChecklistItems > 0)
                    <div class="alert alert-warning mt-3 mb-0">
                        Existem {{ $pendingRequiredChecklistItems }} item(ns) obrigatorio(s) por concluir nas checklists.
                    </div>
                @endif

                @if ($canUpdateWork && count($availableStatuses))
                    <hr>

                    <form method="POST" action="{{ route('works.change-status', $work) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-2">
                            <label class="form-label">Novo estado</label>
                            <select name="status" class="form-select" required>
                                @foreach ($availableStatuses as $status => $label)
                                    <option value="{{ $status }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Observacoes</label>
                            <textarea name="status_notes" class="form-control" rows="2" maxlength="2000"></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Atualizar estado</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><strong>Resumo economico</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1">
                    <span>Receita prevista</span>
                    <strong>{{ number_format($plannedRevenue, 2, ',', '.') }} &euro;</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Custo materiais (diario)</span>
                    <strong>{{ number_format($materialsCost, 2, ',', '.') }} &euro;</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Custo mao de obra (diario)</span>
                    <strong>{{ number_format($laborCost, 2, ',', '.') }} &euro;</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Horas de mao de obra (diario)</span>
                    <strong>{{ number_format($laborHours, 2, ',', '.') }} h</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Outros custos (manual)</span>
                    <strong>{{ number_format($manualOtherCosts, 2, ',', '.') }} &euro;</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Outros custos registados</span>
                    <strong>{{ number_format($expensesCost, 2, ',', '.') }} &euro;</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between py-1">
                    <span>Total de custos</span>
                    <strong>{{ number_format($totalCosts, 2, ',', '.') }} &euro;</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Margem bruta estimada</span>
                    <strong class="{{ $grossMargin >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($grossMargin, 2, ',', '.') }} &euro;
                    </strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Margem (%)</span>
                    <strong class="{{ ($grossMarginPercent ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $grossMarginPercent !== null ? number_format($grossMarginPercent, 2, ',', '.') . ' %' : '-' }}
                    </strong>
                </div>

                @php
                    $expensesByType = $work->expenses
                        ->groupBy('type')
                        ->map(fn ($items) => (float) $items->sum('total_cost'))
                        ->sortDesc();

                    $dailyReportLaborRanking = $dailyReports
                        ->map(function ($report) {
                            $hours = (float) $report->hours_spent;
                            $cost = $report->labor_cost_total_snapshot !== null
                                ? (float) $report->labor_cost_total_snapshot
                                : $hours * (float) ($report->user_hourly_cost_snapshot ?? $report->user?->hourly_cost ?? 0);

                            return [
                                'title' => ($report->report_date?->format('d/m/Y') ?? '-') . ' - ' . ($report->user?->name ?? 'Sem utilizador'),
                                'hours' => $hours,
                                'cost' => $cost,
                            ];
                        })
                        ->sortByDesc('cost')
                        ->take(5);
                @endphp

                @if ($expensesByType->count())
                    <hr>
                    <div class="small text-muted mb-2">Subtotal por tipo de custo</div>
                    <ul class="list-group list-group-flush">
                        @foreach ($expensesByType as $expenseType => $amount)
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>{{ $expenseTypes[$expenseType] ?? $expenseType }}</span>
                                <strong>{{ number_format((float) $amount, 2, ',', '.') }} &euro;</strong>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($dailyReportLaborRanking->count())
                    <hr>
                    <div class="small text-muted mb-2">Top custo de mao de obra por registo diario (ultimos registos)</div>
                    <ul class="list-group list-group-flush">
                        @foreach ($dailyReportLaborRanking as $taskRank)
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>{{ $taskRank['title'] }}</span>
                                <strong>
                                    {{ number_format((float) $taskRank['cost'], 2, ',', '.') }} &euro;
                                    <span class="text-muted">/ {{ number_format((float) $taskRank['hours'], 2, ',', '.') }} h</span>
                                </strong>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><strong>Historico de estados</strong></div>
            <div class="card-body">
                @if ($work->statusHistories->count())
                    <ul class="list-group list-group-flush">
                        @foreach ($work->statusHistories as $history)
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fw-semibold">
                                            {{ \App\Models\Work::statuses()[$history->new_status] ?? $history->new_status }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $history->old_status ? (\App\Models\Work::statuses()[$history->old_status] ?? $history->old_status) : 'Estado inicial' }}
                                            -> {{ \App\Models\Work::statuses()[$history->new_status] ?? $history->new_status }}
                                        </div>
                                        @if ($history->notes)
                                            <div class="small text-muted mt-1">{{ $history->notes }}</div>
                                        @endif
                                    </div>
                                    <div class="text-end small text-muted">
                                        <div>{{ $history->created_at?->format('d/m/Y H:i') }}</div>
                                        <div>{{ $history->changedBy?->name ?? '-' }}</div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted">Sem historico de estados.</div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><strong>Historico operacional</strong></div>
            <div class="card-body">
                @if ($operationalLogs->count())
                    <ul class="list-group list-group-flush">
                        @foreach ($operationalLogs as $log)
                            @php
                                $actionClass = match ($log->action) {
                                    'created' => 'bg-success',
                                    'updated', 'status_changed', 'completed' => 'bg-primary',
                                    'deleted' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                                $actionLabel = match ($log->action) {
                                    'created' => 'criado',
                                    'updated' => 'atualizado',
                                    'deleted' => 'removido',
                                    'status_changed' => 'estado alterado',
                                    'completed' => 'concluido',
                                    'created_from_budget' => 'criado de orcamento',
                                    'technical_manager_changed' => 'resp. tecnico alterado',
                                    'team_changed' => 'equipa alterada',
                                    default => str_replace('_', ' ', $log->action),
                                };
                                $entityLabel = match ($log->entity) {
                                    'work' => 'obra',
                                    'work_task' => 'tarefa',
                                    'work_checklist' => 'checklist',
                                    'work_checklist_item' => 'item checklist',
                                    'work_material' => 'material',
                                    'work_task_assignment' => 'mao de obra',
                                    'work_daily_report' => 'diario de obra',
                                    'work_file' => 'ficheiro',
                                    'work_expense' => 'custo',
                                    default => str_replace('_', ' ', $log->entity),
                                };
                                $payload = is_array($log->payload) ? $log->payload : [];
                                $oldPayload = isset($payload['old']) && is_array($payload['old']) ? $payload['old'] : [];
                                $newPayload = isset($payload['new']) && is_array($payload['new']) ? $payload['new'] : [];
                                $workStatuses = \App\Models\Work::statuses();
                                $logDescription = 'Atualizacao operacional registada.';
                                $logDetail = null;

                                if ($log->entity === 'work' && in_array($log->action, ['status_changed', 'completed'], true)) {
                                    $oldStatus = $payload['old_status'] ?? null;
                                    $newStatus = $payload['new_status'] ?? null;
                                    $oldLabel = $oldStatus ? ($workStatuses[$oldStatus] ?? (string) $oldStatus) : 'Sem estado anterior';
                                    $newLabel = $newStatus ? ($workStatuses[$newStatus] ?? (string) $newStatus) : 'Sem estado';
                                    $logDescription = 'Estado da obra alterado: ' . $oldLabel . ' -> ' . $newLabel . '.';
                                    $logDetail = $payload['notes'] ?? null;
                                } elseif ($log->entity === 'work' && $log->action === 'created_from_budget') {
                                    $budgetCode = $payload['budget_code'] ?? ('#' . ($payload['budget_id'] ?? '-'));
                                    $logDescription = 'Obra criada a partir do orcamento ' . $budgetCode . '.';
                                } elseif ($log->entity === 'work_task') {
                                    $taskTitle = $payload['title'] ?? $payload['task_title'] ?? $newPayload['title'] ?? $oldPayload['title'] ?? 'Tarefa sem titulo';
                                    $logDescription = 'Tarefa: ' . $taskTitle . '.';
                                    $taskStatus = $payload['status'] ?? $newPayload['status'] ?? null;
                                    if ($taskStatus) {
                                        $taskStatusLabel = \App\Models\WorkTask::statuses()[$taskStatus] ?? (string) $taskStatus;
                                        $logDetail = 'Estado: ' . $taskStatusLabel . '.';
                                    }
                                } elseif ($log->entity === 'work_checklist') {
                                    $checklistName = $payload['name'] ?? $newPayload['name'] ?? $oldPayload['name'] ?? 'Checklist';
                                    $logDescription = 'Checklist: ' . $checklistName . '.';
                                    if ($log->action === 'created') {
                                        $logDescription = 'Checklist criada: ' . $checklistName . '.';
                                    } elseif ($log->action === 'deleted') {
                                        $logDescription = 'Checklist removida: ' . $checklistName . '.';
                                    }
                                    $itemsTotal = $payload['items_total'] ?? null;
                                    $logDetail = $itemsTotal !== null ? 'Itens: ' . (int) $itemsTotal : null;
                                } elseif ($log->entity === 'work_checklist_item') {
                                    $itemDescription = $payload['description'] ?? $newPayload['description'] ?? $oldPayload['description'] ?? 'Item';
                                    $logDescription = 'Item de checklist: ' . $itemDescription . '.';
                                    if ($log->action === 'created') {
                                        $logDescription = 'Item de checklist criado: ' . $itemDescription . '.';
                                    } elseif ($log->action === 'deleted') {
                                        $logDescription = 'Item de checklist removido: ' . $itemDescription . '.';
                                    } elseif ($log->action === 'completed') {
                                        $logDescription = 'Item de checklist concluido: ' . $itemDescription . '.';
                                    }

                                    $parts = [];
                                    if (array_key_exists('is_required', $payload)) {
                                        $parts[] = 'Obrigatorio: ' . ((bool) $payload['is_required'] ? 'sim' : 'nao');
                                    }
                                    if (array_key_exists('is_completed', $payload)) {
                                        $parts[] = 'Concluido: ' . ((bool) $payload['is_completed'] ? 'sim' : 'nao');
                                    }
                                    if (!empty($payload['checklist_name'])) {
                                        $parts[] = 'Checklist: ' . $payload['checklist_name'];
                                    }
                                    $logDetail = count($parts) ? implode(' | ', $parts) : null;
                                } elseif ($log->entity === 'work_material') {
                                    $materialPayload = $newPayload ?: $payload;
                                    $description = $materialPayload['description_snapshot'] ?? 'Material';
                                    $qty = isset($materialPayload['qty']) ? number_format((float) $materialPayload['qty'], 3, ',', '.') : null;
                                    $total = isset($materialPayload['total_cost']) ? number_format((float) $materialPayload['total_cost'], 2, ',', '.') . ' â‚¬' : null;
                                    $stockPayload = is_array($payload['stock'] ?? null) ? $payload['stock'] : null;
                                    $logDescription = 'Material: ' . $description . '.';
                                    $parts = [];
                                    if ($qty !== null) {
                                        $parts[] = 'Qtd: ' . $qty;
                                    }
                                    if ($total !== null) {
                                        $parts[] = 'Total: ' . $total;
                                    }
                                    if (is_array($stockPayload)) {
                                        if (($stockPayload['applied'] ?? false) && isset($stockPayload['quantity'])) {
                                            $parts[] = 'Stock descontado: ' . number_format((float) $stockPayload['quantity'], 3, ',', '.');
                                        } elseif ($stockPayload['reverted'] ?? false) {
                                            $parts[] = 'Stock reposto';
                                        } elseif (($stockPayload['applied'] ?? false) === false) {
                                            $parts[] = 'Sem movimento de stock';
                                        }
                                    }
                                    $logDetail = count($parts) ? implode(' | ', $parts) : null;
                                } elseif ($log->entity === 'work_task_assignment') {
                                    $assignmentPayload = $newPayload ?: $payload;
                                    $minutes = isset($assignmentPayload['worked_minutes']) ? (int) $assignmentPayload['worked_minutes'] : null;
                                    $hours = $minutes !== null ? number_format($minutes / 60, 2, ',', '.') : null;
                                    $total = isset($assignmentPayload['labor_cost_total']) ? number_format((float) $assignmentPayload['labor_cost_total'], 2, ',', '.') . ' â‚¬' : null;
                                    $userId = $assignmentPayload['user_id'] ?? null;
                                    $logDescription = 'Registo de mao de obra na tarefa.';
                                    $parts = [];
                                    if ($hours !== null) {
                                        $parts[] = 'Horas: ' . $hours;
                                    }
                                    if ($total !== null) {
                                        $parts[] = 'Custo: ' . $total;
                                    }
                                    if ($userId) {
                                        $parts[] = 'Utilizador ID: ' . $userId;
                                    }
                                    $logDetail = count($parts) ? implode(' | ', $parts) : null;
                                } elseif ($log->entity === 'work_daily_report') {
                                    $reportPayload = $newPayload ?: $payload;
                                    $reportDate = $reportPayload['report_date'] ?? $payload['report_date'] ?? null;
                                    $dayStatus = $reportPayload['day_status'] ?? $payload['day_status'] ?? null;
                                    $hoursSpent = $reportPayload['hours_spent'] ?? $payload['hours_spent'] ?? null;
                                    $itemsCount = $reportPayload['items_count'] ?? $payload['items_count'] ?? null;
                                    $laborCostSnapshot = $reportPayload['labor_cost_total_snapshot'] ?? $payload['labor_cost_total_snapshot'] ?? null;
                                    $itemsCostSnapshot = $reportPayload['items_total_cost_snapshot'] ?? $payload['items_total_cost_snapshot'] ?? null;

                                    $dayStatusLabel = $dayStatus
                                        ? (\App\Models\WorkDailyReport::statuses()[$dayStatus] ?? (string) $dayStatus)
                                        : null;

                                    $logDescription = 'Registo diario de obra atualizado.';
                                    if ($log->action === 'created') {
                                        $logDescription = 'Registo diario de obra criado.';
                                    } elseif ($log->action === 'deleted') {
                                        $logDescription = 'Registo diario de obra removido.';
                                    }

                                    $parts = [];
                                    if ($reportDate) {
                                        $parts[] = 'Data: ' . $reportDate;
                                    }
                                    if ($dayStatusLabel) {
                                        $parts[] = 'Estado: ' . $dayStatusLabel;
                                    }
                                    if ($hoursSpent !== null) {
                                        $parts[] = 'Horas: ' . number_format((float) $hoursSpent, 2, ',', '.');
                                    }
                                    if ($itemsCount !== null) {
                                        $parts[] = 'Materiais: ' . (int) $itemsCount;
                                    }
                                    if ($itemsCostSnapshot !== null) {
                                        $parts[] = 'Custo materiais: ' . number_format((float) $itemsCostSnapshot, 2, ',', '.') . ' â‚¬';
                                    }
                                    if ($laborCostSnapshot !== null) {
                                        $parts[] = 'Custo mao de obra: ' . number_format((float) $laborCostSnapshot, 2, ',', '.') . ' â‚¬';
                                    }

                                    $logDetail = count($parts) ? implode(' | ', $parts) : null;
                                } elseif ($log->entity === 'work_file') {
                                    $filePayload = $newPayload ?: $payload;
                                    $originalName = $filePayload['original_name'] ?? 'Ficheiro';
                                    $category = $filePayload['category'] ?? null;
                                    $mimeType = $filePayload['mime_type'] ?? null;
                                    $fileSize = $filePayload['file_size'] ?? null;
                                    $linkedReportId = $filePayload['work_daily_report_id'] ?? null;

                                    $categoryLabel = $category
                                        ? (\App\Models\WorkFile::categories()[$category] ?? (string) $category)
                                        : null;

                                    $logDescription = 'Ficheiro da obra atualizado: ' . $originalName . '.';
                                    if ($log->action === 'created') {
                                        $logDescription = 'Ficheiro carregado: ' . $originalName . '.';
                                    } elseif ($log->action === 'deleted') {
                                        $logDescription = 'Ficheiro removido: ' . $originalName . '.';
                                    }

                                    $parts = [];
                                    if ($categoryLabel) {
                                        $parts[] = 'Categoria: ' . $categoryLabel;
                                    }
                                    if ($mimeType) {
                                        $parts[] = 'Tipo: ' . $mimeType;
                                    }
                                    if ($fileSize !== null) {
                                        $parts[] = 'Tamanho: ' . number_format(((float) $fileSize) / 1024, 2, ',', '.') . ' KB';
                                    }
                                    if ($linkedReportId !== null) {
                                        $parts[] = 'Diario ID: ' . (int) $linkedReportId;
                                    }

                                    $logDetail = count($parts) ? implode(' | ', $parts) : null;
                                } elseif ($log->entity === 'work_expense') {
                                    $expensePayload = $newPayload ?: $payload;
                                    $expenseType = $expensePayload['type'] ?? $payload['type'] ?? null;
                                    $expenseLabel = $expenseType ? ($expenseTypes[$expenseType] ?? (string) $expenseType) : 'Custo';
                                    $description = $expensePayload['description'] ?? $payload['description'] ?? null;
                                    $total = isset($expensePayload['total_cost'])
                                        ? number_format((float) $expensePayload['total_cost'], 2, ',', '.') . ' â‚¬'
                                        : (isset($payload['total_cost']) ? number_format((float) $payload['total_cost'], 2, ',', '.') . ' â‚¬' : null);
                                    $logDescription = $expenseLabel . ($description ? ': ' . $description . '.' : '.');
                                    $logDetail = $total ? 'Total: ' . $total : null;
                                } elseif ($log->action === 'technical_manager_changed') {
                                    $logDescription = 'Responsavel tecnico da obra alterado.';
                                } elseif ($log->action === 'team_changed') {
                                    $oldTeamCount = is_array($payload['old_team'] ?? null) ? count($payload['old_team']) : 0;
                                    $newTeamCount = is_array($payload['new_team'] ?? null) ? count($payload['new_team']) : 0;
                                    $logDescription = 'Equipa da obra alterada.';
                                    $logDetail = 'Elementos: ' . $oldTeamCount . ' -> ' . $newTeamCount . '.';
                                }
                            @endphp
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge {{ $actionClass }}">{{ $actionLabel }}</span>
                                            <span class="badge bg-light text-dark border">{{ $entityLabel }}</span>
                                        </div>
                                        <div class="fw-semibold">
                                            {{ $logDescription }}
                                        </div>
                                        @if ($logDetail)
                                            <div class="small text-muted">{{ $logDetail }}</div>
                                        @endif
                                    </div>
                                    <div class="text-end small text-muted">
                                        <div>{{ $log->created_at?->format('d/m/Y H:i') }}</div>
                                        <div>{{ $log->user?->name ?? '-' }}</div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted">Sem eventos operacionais recentes.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : @json(csrf_token());

        document.querySelectorAll('.js-checklist-toggle').forEach(function (toggleInput) {
            toggleInput.addEventListener('change', function () {
                const url = toggleInput.dataset.url;
                const checklistId = toggleInput.dataset.checklistId;
                const itemId = toggleInput.dataset.itemId;
                const nextValue = toggleInput.checked;

                if (!url || !checklistId || !itemId) {
                    return;
                }

                toggleInput.disabled = true;

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        is_completed: nextValue ? 1 : 0,
                    }),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Erro ao atualizar item.');
                        }

                        return response.json();
                    })
                    .then(function (payload) {
                        if (!payload || payload.ok !== true) {
                            throw new Error('Resposta invalida do servidor.');
                        }

                        const descriptionElement = document.querySelector('[data-checklist-item-description="' + itemId + '"]');
                        const metaElement = document.querySelector('[data-checklist-item-meta="' + itemId + '"]');
                        const progressElement = document.querySelector('[data-checklist-progress="' + checklistId + '"]');
                        const requiredElement = document.querySelector('[data-checklist-required="' + checklistId + '"]');

                        if (descriptionElement) {
                            descriptionElement.classList.toggle('text-decoration-line-through', payload.item.is_completed);
                            descriptionElement.classList.toggle('text-muted', payload.item.is_completed);
                        }

                        if (metaElement) {
                            if (payload.item.is_completed) {
                                const byName = payload.item.completed_by_name || '-';
                                const at = payload.item.completed_at ? (' Â· ' + payload.item.completed_at) : '';
                                metaElement.textContent = byName + at;
                            } else {
                                metaElement.textContent = '-';
                            }
                        }

                        if (progressElement) {
                            progressElement.textContent = payload.checklist.completed_items + '/' + payload.checklist.total_items;
                        }

                        if (requiredElement) {
                            const pendingRequired = Number(payload.checklist.pending_required_items || 0);
                            if (pendingRequired > 0) {
                                requiredElement.classList.remove('bg-success');
                                requiredElement.classList.add('bg-danger');
                                requiredElement.textContent = pendingRequired + ' obrigatorio(s) pendente(s)';
                            } else {
                                requiredElement.classList.remove('bg-danger');
                                requiredElement.classList.add('bg-success');
                                requiredElement.textContent = 'Obrigatorios ok';
                            }
                        }
                    })
                    .catch(function () {
                        toggleInput.checked = !nextValue;
                        window.alert('Nao foi possivel atualizar o estado do item da checklist.');
                    })
                    .finally(function () {
                        toggleInput.disabled = false;
                    });
            });
        });

        const toggleExpenseFields = function (form) {
            const typeInput = form.querySelector('.expense-type');
            const kmWrappers = form.querySelectorAll('.expense-km-wrapper');
            const qtyWrappers = form.querySelectorAll('.expense-qty-wrapper');
            const travelWrappers = form.querySelectorAll('.expense-travel-wrapper');
            const kmInput = form.querySelector('.expense-km');
            const qtyInput = form.querySelector('.expense-qty');
            const unitCostInput = form.querySelector('.expense-unit-cost');
            const totalCostInput = form.querySelector('.expense-total-cost');

            if (!typeInput) {
                return;
            }

            const isTravelKm = typeInput.value === 'travel_km';

            kmWrappers.forEach(function (wrapper) {
                wrapper.classList.toggle('d-none', !isTravelKm);
            });
            travelWrappers.forEach(function (wrapper) {
                wrapper.classList.toggle('d-none', !isTravelKm);
            });
            qtyWrappers.forEach(function (wrapper) {
                wrapper.classList.toggle('d-none', isTravelKm);
            });

            if (isTravelKm && qtyInput) {
                qtyInput.value = '';
            }

            if (isTravelKm && totalCostInput) {
                const kmValue = Number(kmInput ? kmInput.value : 0);
                const unitValue = Number(unitCostInput ? unitCostInput.value : 0);
                if (!Number.isNaN(kmValue) && !Number.isNaN(unitValue)) {
                    totalCostInput.value = (kmValue * unitValue).toFixed(2);
                }
            }
        };

        document.querySelectorAll('.work-expense-form').forEach(function (form) {
            const typeInput = form.querySelector('.expense-type');
            const kmInput = form.querySelector('.expense-km');
            const unitCostInput = form.querySelector('.expense-unit-cost');

            if (typeInput) {
                typeInput.addEventListener('change', function () {
                    toggleExpenseFields(form);
                });
            }

            [kmInput, unitCostInput].forEach(function (input) {
                if (input) {
                    input.addEventListener('input', function () {
                        toggleExpenseFields(form);
                    });
                }
            });

            toggleExpenseFields(form);
        });
    });
</script>
@endsection


