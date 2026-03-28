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
    $manualOtherCosts = $work->manualOtherCosts();
    $expensesCost = $work->expensesCost();
    $otherCostsTotal = $work->otherCosts();
    $totalCosts = $work->totalCosts();
    $grossMargin = $work->estimatedGrossMargin();
    $grossMarginPercent = $plannedRevenue > 0 ? ($grossMargin / $plannedRevenue) * 100 : null;
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">{{ $work->name }}</h2>
        <div class="text-muted">Codigo: <strong>{{ $work->code }}</strong></div>
    </div>

    <div class="d-flex gap-2">
        @can('works.update')
            <a href="{{ route('works.edit', $work) }}" class="btn btn-primary">Editar</a>
        @endcan

        @can('works.delete')
            <form method="POST" action="{{ route('works.destroy', $work) }}" onsubmit="return confirm('Tens a certeza que queres apagar esta obra?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Apagar</button>
            </form>
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
        Esta obra encontra-se {{ strtolower($statusLabel) }}. Registos operacionais (tarefas, materiais, mao de obra e despesas) estao bloqueados.
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
                <strong>Materiais usados</strong>
                <span class="badge bg-light text-dark border">{{ $work->materials->count() }}</span>
            </div>

            <div class="card-body">
                @if ($canManageOperationalData)
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
                                    @if ($canManageOperationalData)
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
                                        @if ($canManageOperationalData)
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#{{ $materialEditId }}">Editar</button>
                                                    <form method="POST" action="{{ route('works.materials.destroy', [$work, $material]) }}" onsubmit="return confirm('Remover este material?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>

                                    @if ($canManageOperationalData)
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

        <div class="card shadow-sm mb-4" id="labor-section">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Mao de obra por tarefa</strong>
                <span class="badge bg-light text-dark border">{{ number_format($laborCost, 2, ',', '.') }} &euro;</span>
            </div>

            <div class="card-body">
                @if ($work->tasks->count() === 0)
                    <div class="text-muted">Cria tarefas para registar mao de obra.</div>
                @else
                    @foreach ($work->tasks as $task)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                <div>
                                    <div class="fw-semibold">{{ $task->title }}</div>
                                    <div class="small text-muted">Estado: {{ $taskStatuses[$task->status] ?? $task->status }}</div>
                                </div>
                                <div class="small fw-semibold">Custo tarefa: {{ number_format($task->laborCostTotal(), 2, ',', '.') }} &euro;</div>
                            </div>

                            @if ($canManageOperationalData)
                                @php
                                    $userFieldId = 'labor-user-' . $task->id;
                                    $roleFieldId = 'labor-role-' . $task->id;
                                    $costFieldId = 'labor-cost-' . $task->id;
                                    $saleFieldId = 'labor-sale-' . $task->id;
                                @endphp
                                <form method="POST" action="{{ route('works.tasks.assignments.store', [$work, $task]) }}" class="bg-light border rounded p-3 mb-3">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label for="{{ $userFieldId }}" class="form-label">Interveniente</label>
                                            <select name="user_id" id="{{ $userFieldId }}" class="form-select labor-user-select" data-role-target="#{{ $roleFieldId }}" data-cost-target="#{{ $costFieldId }}" data-sale-target="#{{ $saleFieldId }}" required>
                                                <option value="">Selecionar...</option>
                                                @foreach ($laborUsers as $laborUser)
                                                    <option
                                                        value="{{ $laborUser->id }}"
                                                        data-role="{{ $laborUser->job_title }}"
                                                        data-hourly-cost="{{ number_format((float) ($laborUser->hourly_cost ?? 0), 2, '.', '') }}"
                                                        data-hourly-sale="{{ $laborUser->hourly_sale_price !== null ? number_format((float) $laborUser->hourly_sale_price, 2, '.', '') : '' }}"
                                                    >
                                                        {{ $laborUser->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label for="{{ $roleFieldId }}" class="form-label">Funcao na tarefa (snapshot)</label>
                                            <input type="text" name="role_snapshot" id="{{ $roleFieldId }}" class="form-control" maxlength="120">
                                        </div>

                                        <div class="col-md-2">
                                            <label for="{{ $costFieldId }}" class="form-label">Custo hora</label>
                                            <input type="number" name="hourly_cost_snapshot" id="{{ $costFieldId }}" class="form-control" min="0" step="0.01">
                                        </div>

                                        <div class="col-md-2">
                                            <label for="{{ $saleFieldId }}" class="form-label">Preco hora</label>
                                            <input type="number" name="hourly_sale_price_snapshot" id="{{ $saleFieldId }}" class="form-control" min="0" step="0.01">
                                        </div>

                                        <div class="col-md-1">
                                            <label class="form-label">Inicio</label>
                                            <input type="time" name="start_time" class="form-control">
                                        </div>

                                        <div class="col-md-1">
                                            <label class="form-label">Fim</label>
                                            <input type="time" name="end_time" class="form-control">
                                        </div>

                                        <div class="col-md-1">
                                            <label class="form-label">Horas</label>
                                            <input type="number" name="worked_hours" class="form-control" min="0.01" max="24" step="0.01" placeholder="ex: 7.50">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Notas</label>
                                            <input type="text" name="notes" class="form-control" maxlength="5000">
                                        </div>

                                        <div class="col-12 d-flex justify-content-end">
                                            <button type="submit" class="btn btn-sm btn-primary">Adicionar interveniente</button>
                                        </div>
                                    </div>
                                </form>
                            @endif

                            @if ($task->assignments->count())
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Utilizador</th>
                                                <th>Funcao</th>
                                                <th>Periodo</th>
                                                <th>Horas</th>
                                                <th>Custo hora</th>
                                                <th>Custo total</th>
                                                <th>Venda total</th>
                                                @if ($canManageOperationalData)
                                                    <th>Acoes</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($task->assignments as $assignment)
                                                @php
                                                    $assignmentEditId = 'assignment-edit-' . $assignment->id;
                                                    $startTime = $assignment->start_time ? substr((string) $assignment->start_time, 0, 5) : '';
                                                    $endTime = $assignment->end_time ? substr((string) $assignment->end_time, 0, 5) : '';
                                                @endphp
                                                <tr>
                                                    <td>{{ $assignment->user?->name ?? '-' }}</td>
                                                    <td>{{ $assignment->role_snapshot ?: '-' }}</td>
                                                    <td>{{ $startTime ?: '--:--' }} - {{ $endTime ?: '--:--' }}</td>
                                                    <td>{{ number_format($assignment->workedHours(), 2, ',', '.') }}</td>
                                                    <td>{{ number_format((float) $assignment->hourly_cost_snapshot, 2, ',', '.') }} &euro;</td>
                                                    <td class="fw-semibold">{{ number_format((float) $assignment->labor_cost_total, 2, ',', '.') }} &euro;</td>
                                                    <td>{{ $assignment->labor_sale_total !== null ? number_format((float) $assignment->labor_sale_total, 2, ',', '.') . ' €' : '-' }}</td>
                                                    @if ($canManageOperationalData)
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#{{ $assignmentEditId }}">Editar</button>
                                                                <form method="POST" action="{{ route('works.tasks.assignments.destroy', [$work, $task, $assignment]) }}" onsubmit="return confirm('Remover este interveniente?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    @endif
                                                </tr>

                                                @if ($canManageOperationalData)
                                                    <tr class="collapse" id="{{ $assignmentEditId }}">
                                                        <td colspan="8" class="bg-light">
                                                            <form method="POST" action="{{ route('works.tasks.assignments.update', [$work, $task, $assignment]) }}">
                                                                @csrf
                                                                @method('PUT')
                                                                <div class="row g-2">
                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Interveniente</label>
                                                                        <select name="user_id" class="form-select" required>
                                                                            @foreach ($laborUsers as $laborUser)
                                                                                <option value="{{ $laborUser->id }}" @selected((int) $assignment->user_id === (int) $laborUser->id)>
                                                                                    {{ $laborUser->name }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>

                                                                    <div class="col-md-2">
                                                                        <label class="form-label">Funcao na tarefa (snapshot)</label>
                                                                        <input type="text" name="role_snapshot" class="form-control" value="{{ $assignment->role_snapshot }}" maxlength="120">
                                                                    </div>

                                                                    <div class="col-md-2">
                                                                        <label class="form-label">Custo hora</label>
                                                                        <input type="number" name="hourly_cost_snapshot" class="form-control" min="0" step="0.01" value="{{ number_format((float) $assignment->hourly_cost_snapshot, 2, '.', '') }}" required>
                                                                    </div>

                                                                    <div class="col-md-2">
                                                                        <label class="form-label">Preco hora</label>
                                                                        <input type="number" name="hourly_sale_price_snapshot" class="form-control" min="0" step="0.01" value="{{ $assignment->hourly_sale_price_snapshot !== null ? number_format((float) $assignment->hourly_sale_price_snapshot, 2, '.', '') : '' }}">
                                                                    </div>

                                                                    <div class="col-md-1">
                                                                        <label class="form-label">Inicio</label>
                                                                        <input type="time" name="start_time" class="form-control" value="{{ $startTime }}">
                                                                    </div>

                                                                    <div class="col-md-1">
                                                                        <label class="form-label">Fim</label>
                                                                        <input type="time" name="end_time" class="form-control" value="{{ $endTime }}">
                                                                    </div>

                                                                    <div class="col-md-1">
                                                                        <label class="form-label">Horas</label>
                                                                        <input type="number" name="worked_hours" class="form-control" min="0.01" max="24" step="0.01" value="{{ number_format($assignment->workedHours(), 2, '.', '') }}">
                                                                    </div>


                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Notas</label>
                                                                        <input type="text" name="notes" class="form-control" value="{{ $assignment->notes }}" maxlength="5000">
                                                                    </div>

                                                                    <div class="col-12 d-flex justify-content-end">
                                                                        <button type="submit" class="btn btn-sm btn-primary">Guardar interveniente</button>
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
                                <div class="text-muted">Sem intervencoes de mao de obra para esta tarefa.</div>
                            @endif
                        </div>
                    @endforeach
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
                                                        · {{ $expense->from_location ?: '-' }} → {{ $expense->to_location ?: '-' }}
                                                    @endif
                                                </div>
                                            @elseif ($expense->qty !== null || $expense->unit_cost !== null)
                                                <div class="small text-muted">
                                                    {{ $expense->qty !== null ? number_format((float) $expense->qty, 3, ',', '.') : '-' }}
                                                    x
                                                    {{ $expense->unit_cost !== null ? number_format((float) $expense->unit_cost, 2, ',', '.') . ' €' : '-' }}
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
                    <span>Custo materiais</span>
                    <strong>{{ number_format($materialsCost, 2, ',', '.') }} &euro;</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Custo mao de obra</span>
                    <strong>{{ number_format($laborCost, 2, ',', '.') }} &euro;</strong>
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

                    $taskLaborRanking = $work->tasks
                        ->map(function ($task) {
                            return [
                                'title' => $task->title,
                                'cost' => (float) $task->laborCostTotal(),
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

                @if ($taskLaborRanking->count())
                    <hr>
                    <div class="small text-muted mb-2">Top custo de mao de obra por tarefa</div>
                    <ul class="list-group list-group-flush">
                        @foreach ($taskLaborRanking as $taskRank)
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>{{ $taskRank['title'] }}</span>
                                <strong>{{ number_format((float) $taskRank['cost'], 2, ',', '.') }} &euro;</strong>
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
                                    'work_material' => 'material',
                                    'work_task_assignment' => 'mao de obra',
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
                                } elseif ($log->entity === 'work_material') {
                                    $materialPayload = $newPayload ?: $payload;
                                    $description = $materialPayload['description_snapshot'] ?? 'Material';
                                    $qty = isset($materialPayload['qty']) ? number_format((float) $materialPayload['qty'], 3, ',', '.') : null;
                                    $total = isset($materialPayload['total_cost']) ? number_format((float) $materialPayload['total_cost'], 2, ',', '.') . ' €' : null;
                                    $logDescription = 'Material: ' . $description . '.';
                                    $parts = [];
                                    if ($qty !== null) {
                                        $parts[] = 'Qtd: ' . $qty;
                                    }
                                    if ($total !== null) {
                                        $parts[] = 'Total: ' . $total;
                                    }
                                    $logDetail = count($parts) ? implode(' | ', $parts) : null;
                                } elseif ($log->entity === 'work_task_assignment') {
                                    $assignmentPayload = $newPayload ?: $payload;
                                    $minutes = isset($assignmentPayload['worked_minutes']) ? (int) $assignmentPayload['worked_minutes'] : null;
                                    $hours = $minutes !== null ? number_format($minutes / 60, 2, ',', '.') : null;
                                    $total = isset($assignmentPayload['labor_cost_total']) ? number_format((float) $assignmentPayload['labor_cost_total'], 2, ',', '.') . ' €' : null;
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
                                } elseif ($log->entity === 'work_expense') {
                                    $expensePayload = $newPayload ?: $payload;
                                    $expenseType = $expensePayload['type'] ?? $payload['type'] ?? null;
                                    $expenseLabel = $expenseType ? ($expenseTypes[$expenseType] ?? (string) $expenseType) : 'Custo';
                                    $description = $expensePayload['description'] ?? $payload['description'] ?? null;
                                    $total = isset($expensePayload['total_cost'])
                                        ? number_format((float) $expensePayload['total_cost'], 2, ',', '.') . ' €'
                                        : (isset($payload['total_cost']) ? number_format((float) $payload['total_cost'], 2, ',', '.') . ' €' : null);
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
        const materialItem = document.getElementById('material_item_id');
        const materialUnitCost = document.getElementById('material_unit_cost');

        if (materialItem && materialUnitCost) {
            materialItem.addEventListener('change', function () {
                if (materialUnitCost.value !== '') {
                    return;
                }

                const selected = materialItem.options[materialItem.selectedIndex];
                if (!selected) {
                    return;
                }

                const defaultCost = selected.getAttribute('data-cost');
                if (defaultCost !== null) {
                    materialUnitCost.value = defaultCost;
                }
            });
        }

        document.querySelectorAll('.labor-user-select').forEach(function (select) {
            select.addEventListener('change', function () {
                const selected = select.options[select.selectedIndex];
                if (!selected) {
                    return;
                }

                const roleTarget = document.querySelector(select.dataset.roleTarget);
                const costTarget = document.querySelector(select.dataset.costTarget);
                const saleTarget = document.querySelector(select.dataset.saleTarget);

                if (roleTarget && roleTarget.value === '') {
                    roleTarget.value = selected.dataset.role || '';
                }

                if (costTarget && (costTarget.value === '' || Number(costTarget.value) === 0)) {
                    costTarget.value = selected.dataset.hourlyCost || '';
                }

                if (saleTarget && saleTarget.value === '') {
                    saleTarget.value = selected.dataset.hourlySale || '';
                }
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

