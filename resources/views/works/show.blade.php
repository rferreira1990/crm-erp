@extends('layouts.admin')

@section('title', 'Detalhe da Obra')

@section('content')
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
