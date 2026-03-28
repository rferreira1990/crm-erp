@extends('layouts.admin')

@section('title', 'Detalhe da Obra')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">{{ $work->name }}</h2>
        <div class="text-muted">
            Código: <strong>{{ $work->code }}</strong>
        </div>
    </div>

    <div class="d-flex gap-2">
        @can('works.update')
            <a href="{{ route('works.edit', $work) }}" class="btn btn-primary">
                Editar
            </a>
        @endcan

        @can('works.delete')
            <form method="POST"
                  action="{{ route('works.destroy', $work) }}"
                  onsubmit="return confirm('Tens a certeza que queres apagar esta obra?');">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-outline-danger">
                    Apagar
                </button>
            </form>
        @endcan
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@php
    $canUpdateWork = auth()->user()?->can('works.update');
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
    $otherCosts = $work->otherCosts();
    $grossMargin = $work->estimatedGrossMargin();
    $grossMarginPercent = $plannedRevenue > 0 ? ($grossMargin / $plannedRevenue) * 100 : null;
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Dados da obra</strong>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <div>
                            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cliente</label>
                        <div>{{ $work->customer?->name ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tipo de obra</label>
                        <div>{{ $work->work_type ?: '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Responsável técnico</label>
                        <div>{{ $work->technicalManager?->name ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Orçamento associado</label>
                        <div>
                            @if ($work->budget)
                                <a href="{{ route('budgets.show', $work->budget) }}">
                                    {{ $work->budget->code }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Local</label>
                        <div>{{ $work->location ?: '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Código Postal</label>
                        <div>{{ $work->postal_code ?: '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cidade</label>
                        <div>{{ $work->city ?: '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Outros custos</label>
                        <div>{{ number_format((float) $work->other_costs, 2, ',', '.') }} &euro;</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Início previsto</label>
                        <div>{{ $work->start_date_planned?->format('d/m/Y') ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Fim previsto</label>
                        <div>{{ $work->end_date_planned?->format('d/m/Y') ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Início real</label>
                        <div>{{ $work->start_date_actual?->format('d/m/Y') ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Fim real</label>
                        <div>{{ $work->end_date_actual?->format('d/m/Y') ?? '—' }}</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <div>{!! nl2br(e($work->description ?: '—')) !!}</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notas internas</label>
                        <div>{!! nl2br(e($work->internal_notes ?: '—')) !!}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Histórico de estados</strong>
            </div>

            <div class="card-body">
                @if ($work->statusHistories->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Estado anterior</th>
                                    <th>Novo estado</th>
                                    <th>Observações</th>
                                    <th>Utilizador</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($work->statusHistories as $history)
                                    @php
                                        $oldStatusLabel = $history->old_status
                                            ? (\App\Models\Work::statuses()[$history->old_status] ?? $history->old_status)
                                            : '—';

                                        $newStatusLabel = \App\Models\Work::statuses()[$history->new_status] ?? $history->new_status;
                                    @endphp

                                    <tr>
                                        <td>{{ $history->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td>{{ $oldStatusLabel }}</td>
                                        <td>{{ $newStatusLabel }}</td>
                                        <td>{{ $history->notes ?: '—' }}</td>
                                        <td>{{ $history->changedBy?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted">
                        Ainda não existe histórico de estados.
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Resumo económico</strong>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted">Receita prevista</div>
                            <div class="h5 mb-0">{{ number_format($plannedRevenue, 2, ',', '.') }} &euro;</div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted">Materiais</div>
                            <div class="h5 mb-0">{{ number_format($materialsCost, 2, ',', '.') }} &euro;</div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted">Outros custos</div>
                            <div class="h5 mb-0">{{ number_format($otherCosts, 2, ',', '.') }} &euro;</div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100 {{ $grossMargin >= 0 ? 'border-success' : 'border-danger' }}">
                            <div class="small text-muted">Margem bruta</div>
                            <div class="h5 mb-0 {{ $grossMargin >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($grossMargin, 2, ',', '.') }} &euro;
                            </div>
                            @if (! is_null($grossMarginPercent))
                                <div class="small {{ $grossMargin >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($grossMarginPercent, 2, ',', '.') }}%
                                </div>
                            @endif
                        </div>
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
                @if ($canUpdateWork)
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
                                    @if ($canUpdateWork)
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
                                        @if ($canUpdateWork)
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#{{ $taskEditId }}">
                                                        Editar
                                                    </button>

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

                                    @if ($canUpdateWork)
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
                                                            <input type="time" name="planned_start_time" class="form-control" value="{{ $task->planned_start_time }}">
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Hora fim</label>
                                                            <input type="time" name="planned_end_time" class="form-control" value="{{ $task->planned_end_time }}">
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
                <strong>Materiais usados</strong>
                <span class="badge bg-light text-dark border">{{ $work->materials->count() }}</span>
            </div>

            <div class="card-body">
                @if ($canUpdateWork)
                    <form method="POST" action="{{ route('works.materials.store', $work) }}" class="border rounded p-3 mb-4 bg-light">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="material_item_id" class="form-label">Artigo <span class="text-danger">*</span></label>
                                <select name="item_id" id="material_item_id" class="form-select @error('item_id') is-invalid @enderror" required>
                                    <option value="">Selecionar...</option>
                                    @foreach ($availableItems as $item)
                                        <option value="{{ $item->id }}" @selected((int) old('item_id') === (int) $item->id) data-cost="{{ number_format((float) ($item->cost_price ?? 0), 2, '.', '') }}">
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="material_qty" class="form-label">Qtd <span class="text-danger">*</span></label>
                                <input type="number" name="qty" id="material_qty" step="0.001" min="0.001" class="form-control @error('qty') is-invalid @enderror" value="{{ old('qty', '1.000') }}" required>
                            </div>

                            <div class="col-md-2">
                                <label for="material_unit_cost" class="form-label">Custo unit.</label>
                                <input type="number" name="unit_cost" id="material_unit_cost" step="0.01" min="0" class="form-control @error('unit_cost') is-invalid @enderror" value="{{ old('unit_cost') }}" placeholder="Auto">
                            </div>

                            <div class="col-md-3">
                                <label for="material_notes" class="form-label">Notas</label>
                                <input type="text" name="notes" id="material_notes" class="form-control @error('notes') is-invalid @enderror" value="{{ old('notes') }}">
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Adicionar material</button>
                            </div>
                        </div>
                    </form>
                @endif

                @if ($work->materials->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Artigo</th>
                                    <th>Descricao snapshot</th>
                                    <th>Un</th>
                                    <th>Qtd</th>
                                    <th>Custo unit.</th>
                                    <th>Total</th>
                                    @if ($canUpdateWork)
                                        <th>Acoes</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($work->materials as $material)
                                    @php
                                        $materialEditId = 'material-edit-' . $material->id;
                                    @endphp
                                    <tr>
                                        <td>{{ $material->item?->code ?? '-' }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $material->description_snapshot }}</div>
                                            @if ($material->notes)
                                                <div class="small text-muted">{{ $material->notes }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $material->unit_snapshot ?: '-' }}</td>
                                        <td>{{ number_format((float) $material->qty, 3, ',', '.') }}</td>
                                        <td>{{ number_format((float) $material->unit_cost, 2, ',', '.') }} &euro;</td>
                                        <td class="fw-semibold">{{ number_format((float) $material->total_cost, 2, ',', '.') }} &euro;</td>
                                        @if ($canUpdateWork)
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#{{ $materialEditId }}">
                                                        Editar
                                                    </button>

                                                    <form method="POST" action="{{ route('works.materials.destroy', [$work, $material]) }}" onsubmit="return confirm('Remover este material?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>

                                    @if ($canUpdateWork)
                                        <tr class="collapse" id="{{ $materialEditId }}">
                                            <td colspan="7" class="bg-light">
                                                <form method="POST" action="{{ route('works.materials.update', [$work, $material]) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="row g-2">
                                                        <div class="col-md-5">
                                                            <label class="form-label">Artigo</label>
                                                            <select name="item_id" class="form-select" required>
                                                                @foreach ($availableItems as $item)
                                                                    <option value="{{ $item->id }}" @selected((int) $material->item_id === (int) $item->id)>
                                                                        {{ $item->code }} - {{ $item->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Qtd</label>
                                                            <input type="number" name="qty" min="0.001" step="0.001" class="form-control" value="{{ number_format((float) $material->qty, 3, '.', '') }}" required>
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Custo unit.</label>
                                                            <input type="number" name="unit_cost" min="0" step="0.01" class="form-control" value="{{ number_format((float) $material->unit_cost, 2, '.', '') }}" required>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <label class="form-label">Notas</label>
                                                            <input type="text" name="notes" class="form-control" value="{{ $material->notes }}">
                                                        </div>

                                                        <div class="col-12 d-flex justify-content-end">
                                                            <button type="submit" class="btn btn-sm btn-primary">Guardar material</button>
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
                    <div class="text-muted">Sem materiais registados para esta obra.</div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Historico operacional</strong>
            </div>

            <div class="card-body">
                @if ($operationalLogs->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Acao</th>
                                    <th>Detalhe</th>
                                    <th>Utilizador</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($operationalLogs as $log)
                                    @php
                                        $actionLabels = [
                                            'created' => 'Criado',
                                            'updated' => 'Atualizado',
                                            'deleted' => 'Removido',
                                            'status_changed' => 'Estado alterado',
                                            'created_from_budget' => 'Criado de orcamento',
                                            'technical_manager_changed' => 'Responsavel tecnico alterado',
                                            'team_changed' => 'Equipa alterada',
                                            'completed' => 'Concluido',
                                        ];

                                        $payload = $log->payload ?? [];
                                        $detail = '-';

                                        if ($log->entity === 'work') {
                                            if ($log->action === 'created_from_budget') {
                                                $budgetCode = $payload['budget_code'] ?? null;
                                                $budgetId = $payload['budget_id'] ?? null;
                                                $detail = 'Orcamento: ' . ($budgetCode ?: ($budgetId ? ('#' . $budgetId) : '-'));
                                            } elseif ($log->action === 'status_changed') {
                                                $detail = 'Estado ' . ($payload['old_status'] ?? '-') . ' -> ' . ($payload['new_status'] ?? '-');
                                            } elseif ($log->action === 'technical_manager_changed') {
                                                $detail = 'Tecnico alterado';
                                            } elseif ($log->action === 'team_changed') {
                                                $detail = 'Equipa atualizada';
                                            } elseif ($log->action === 'created') {
                                                $detail = $payload['name'] ?? ($payload['work_name'] ?? '-');
                                            } elseif ($log->action === 'updated') {
                                                $detail = 'Dados da obra atualizados';
                                            } elseif ($log->action === 'completed') {
                                                $detail = 'Obra concluida';
                                            }
                                        } elseif ($log->entity === 'work_task') {
                                            $detail = 'Tarefa: ' . ($payload['title'] ?? '-');
                                        } elseif ($log->entity === 'work_material') {
                                            $detail = 'Material: ' . ($payload['description_snapshot'] ?? '-');
                                        }

                                        $label = $actionLabels[$log->action] ?? $log->action;
                                    @endphp
                                    <tr>
                                        <td>{{ $log->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td>{{ $label }}</td>
                                        <td>{{ $detail }}</td>
                                        <td>{{ $log->user?->name ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted">Ainda nao existem registos operacionais para esta obra.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Alterar estado</strong>
            </div>

            <div class="card-body">
                @if ($canUpdateWork && !empty($availableStatuses))
                    <form method="POST" action="{{ route('works.change-status', $work) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label for="status" class="form-label">Novo estado</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="">Seleciona</option>
                                @foreach ($availableStatuses as $status => $label)
                                    <option value="{{ $status }}" @selected(old('status') === $status)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status_notes" class="form-label">Observações</label>
                            <textarea
                                name="status_notes"
                                id="status_notes"
                                rows="4"
                                class="form-control @error('status_notes') is-invalid @enderror"
                            >{{ old('status_notes') }}</textarea>
                            @error('status_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Atualizar estado
                        </button>
                    </form>
                @else
                    <div class="text-muted">
                        Não existem transições disponíveis para o estado atual.
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Equipa associada</strong>
                <span class="badge bg-light text-dark border">
                    {{ $work->team->count() }}
                </span>
            </div>

            <div class="card-body">
                @if ($work->technicalManager)
                    <div class="mb-3">
                        <div class="small text-muted text-uppercase fw-semibold mb-1">
                            Responsável técnico
                        </div>
                        <div class="border rounded px-3 py-2 bg-light">
                            {{ $work->technicalManager->name }}
                        </div>
                    </div>
                @endif

                <div>
                    <div class="small text-muted text-uppercase fw-semibold mb-2">
                        Elementos da equipa
                    </div>

                    @if ($work->team->count())
                        <ul class="list-group list-group-flush">
                            @foreach ($work->team as $member)
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <span>{{ $member->name }}</span>

                                    @if ($work->technical_manager_id === $member->id)
                                        <span class="badge bg-primary">Técnico</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-muted">
                            Sem equipa associada.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <strong>Resumo rapido</strong>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tarefas totais</span>
                    <strong>{{ $work->tasks->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tarefas concluidas</span>
                    <strong>{{ $work->tasks->where('status', \App\Models\WorkTask::STATUS_COMPLETED)->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Materiais registados</span>
                    <strong>{{ $work->materials->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Custo materiais</span>
                    <strong>{{ number_format($materialsCost, 2, ',', '.') }} &euro;</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemSelect = document.getElementById('material_item_id');
    const unitCostInput = document.getElementById('material_unit_cost');

    if (!itemSelect || !unitCostInput) {
        return;
    }

    itemSelect.addEventListener('change', function () {
        if (unitCostInput.value) {
            return;
        }

        const selected = itemSelect.options[itemSelect.selectedIndex];
        const cost = selected ? selected.dataset.cost : '';

        if (cost) {
            unitCostInput.value = cost;
        }
    });
});
</script>
@endpush
