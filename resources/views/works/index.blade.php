@extends('layouts.admin')

@section('title', 'Obras')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Obras</h2>

    @can('works.create')
        <a href="{{ route('works.create') }}" class="btn btn-primary">
            Nova Obra
        </a>
    @endcan
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

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('works.index') }}" method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="search" class="form-label">Pesquisar</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Código, nome da obra ou cliente"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label for="status" class="form-label">Estado</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="{{ \App\Models\Work::STATUS_PLANNED }}" {{ ($filters['status'] ?? '') === \App\Models\Work::STATUS_PLANNED ? 'selected' : '' }}>Planeada</option>
                        <option value="{{ \App\Models\Work::STATUS_IN_PROGRESS }}" {{ ($filters['status'] ?? '') === \App\Models\Work::STATUS_IN_PROGRESS ? 'selected' : '' }}>Em curso</option>
                        <option value="{{ \App\Models\Work::STATUS_SUSPENDED }}" {{ ($filters['status'] ?? '') === \App\Models\Work::STATUS_SUSPENDED ? 'selected' : '' }}>Suspensa</option>
                        <option value="{{ \App\Models\Work::STATUS_COMPLETED }}" {{ ($filters['status'] ?? '') === \App\Models\Work::STATUS_COMPLETED ? 'selected' : '' }}>Concluída</option>
                        <option value="{{ \App\Models\Work::STATUS_CANCELLED }}" {{ ($filters['status'] ?? '') === \App\Models\Work::STATUS_CANCELLED ? 'selected' : '' }}>Cancelada</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label for="technical_manager_id" class="form-label">Responsável técnico</label>
                    <select name="technical_manager_id" id="technical_manager_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ (string) ($filters['technical_manager_id'] ?? '') === (string) $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label for="date_from" class="form-label">Início previsto</label>
                    <input
                        type="date"
                        name="date_from"
                        id="date_from"
                        class="form-control"
                        value="{{ $filters['date_from'] ?? '' }}"
                    >
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    Filtrar
                </button>

                <a href="{{ route('works.index') }}" class="btn btn-outline-secondary btn-sm">
                    Limpar filtros
                </a>
            </div>
        </form>

        @if ($works->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Obra</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Responsável</th>
                            <th>Início previsto</th>
                            <th>Fim previsto</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($works as $work)
                            <tr>
                                <td>
                                    <a href="{{ route('works.show', $work) }}">
                                        <strong>{{ $work->code }}</strong>
                                    </a>
                                </td>

                                <td>
                                    <div><strong>{{ $work->name }}</strong></div>
                                    <small class="text-muted">{{ $work->work_type ?: '—' }}</small>
                                </td>

                                <td>{{ $work->customer->name ?? '—' }}</td>

                                <td>
                                    @if ($work->status === \App\Models\Work::STATUS_PLANNED)
                                        <span class="badge bg-secondary">Planeada</span>
                                    @elseif ($work->status === \App\Models\Work::STATUS_IN_PROGRESS)
                                        <span class="badge bg-primary">Em curso</span>
                                    @elseif ($work->status === \App\Models\Work::STATUS_SUSPENDED)
                                        <span class="badge bg-warning text-dark">Suspensa</span>
                                    @elseif ($work->status === \App\Models\Work::STATUS_COMPLETED)
                                        <span class="badge bg-success">Concluída</span>
                                    @elseif ($work->status === \App\Models\Work::STATUS_CANCELLED)
                                        <span class="badge bg-danger">Cancelada</span>
                                    @else
                                        <span class="badge bg-dark">{{ $work->status }}</span>
                                    @endif
                                </td>

                                <td>{{ $work->technicalManager->name ?? '—' }}</td>

                                <td>
                                    {{ $work->start_date_planned?->format('d/m/Y') ?? '—' }}
                                </td>

                                <td>
                                    {{ $work->end_date_planned?->format('d/m/Y') ?? '—' }}
                                </td>

                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('works.show', $work) }}" class="btn btn-sm btn-outline-primary">
                                            Ver
                                        </a>

                                        <a href="{{ route('works.daily-reports.index', $work) }}" class="btn btn-sm btn-outline-info">
                                            Diario
                                        </a>

                                        @can('works.update')
                                            <a href="{{ route('works.edit', $work) }}" class="btn btn-sm btn-outline-secondary">
                                                Editar
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $works->links() }}
            </div>
        @else
            <div class="text-muted">
                Não foram encontradas obras com os filtros aplicados.
            </div>
        @endif
    </div>
</div>
@endsection
