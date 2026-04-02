@extends('layouts.admin')

@section('title', 'Diario de Obra')

@section('content')
@php
    $canUpdate = auth()->user()?->can('works.update');
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Diario de Obra</h2>
        <div class="text-muted">{{ $work->code }} - {{ $work->name }}</div>
    </div>

    <div class="d-flex gap-2">
        @if ($canUpdate && $work->isEditable())
            <a href="{{ route('works.daily-reports.create', $work) }}" class="btn btn-primary">Novo registo</a>
        @endif
        <a href="{{ route('works.show', $work) }}" class="btn btn-outline-secondary">Voltar a obra</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('works.daily-reports.index', $work) }}" method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Pesquisar</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ $filters['search'] }}"
                        class="form-control"
                        placeholder="Resumo, observacoes ou ocorrencias"
                    >
                </div>
                <div class="col-md-2">
                    <label for="day_status" class="form-label">Estado do dia</label>
                    <select name="day_status" id="day_status" class="form-select">
                        <option value="">Todos</option>
                        @foreach (\App\Models\WorkDailyReport::statuses() as $statusValue => $statusLabel)
                            <option value="{{ $statusValue }}" @selected($filters['day_status'] === $statusValue)>
                                {{ $statusLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Data de</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Data ate</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-md-2">
                    <label for="user_id" class="form-label">Registado por</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <a href="{{ route('works.daily-reports.index', $work) }}" class="btn btn-outline-secondary btn-sm">Limpar filtros</a>
            </div>
        </form>

        @if ($reports->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Estado</th>
                            <th>Horas</th>
                            <th>Resumo</th>
                            <th>Materiais</th>
                            <th>Registado por</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            <tr>
                                <td>{{ $report->report_date?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $report->day_status_label }}</span>
                                </td>
                                <td>{{ number_format((float) $report->hours_spent, 2, ',', '.') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($report->work_summary, 120) }}</div>
                                    @if ($report->incidents)
                                        <small class="text-danger">Com ocorrencias</small>
                                    @endif
                                </td>
                                <td>{{ $report->items_count }}</td>
                                <td>{{ $report->user?->name ?? '-' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('works.daily-reports.show', [$work, $report]) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                        @if ($canUpdate && $work->isEditable())
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

            <div class="mt-3">
                {{ $reports->links() }}
            </div>
        @else
            <div class="text-muted">Sem registos diarios para os filtros aplicados.</div>
        @endif
    </div>
</div>
@endsection

