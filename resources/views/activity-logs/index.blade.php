@extends('layouts.admin')

@section('title', 'Auditoria')

@section('content')
<div class="justify-content-between mb-3">
            <h2>Auditoria</h2>
        </header>

        <section class="card mb-4">
            <header class="card-header">
                <h2 class="card-title mb-0">Filtros</h2>
            </header>

            <div class="card-body">
                <form method="GET" action="{{ route('activity-logs.index') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="entity" class="form-label">Entidade</label>
                            <select name="entity" id="entity" class="form-control">
                                <option value="">Todas</option>
                                @foreach ($entities as $entity)
                                    <option value="{{ $entity }}" @selected(($filters['entity'] ?? null) === $entity)>
                                        {{ $entity }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="action" class="form-label">Ação</label>
                            <select name="action" id="action" class="form-control">
                                <option value="">Todas</option>
                                @foreach ($actions as $action)
                                    <option value="{{ $action }}" @selected(($filters['action'] ?? null) === $action)>
                                        {{ $action }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Data inicial</label>
                            <input
                                type="date"
                                name="date_from"
                                id="date_from"
                                class="form-control @error('date_from') is-invalid @enderror"
                                value="{{ $filters['date_from'] ?? '' }}"
                            >
                            @error('date_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Data final</label>
                            <input
                                type="date"
                                name="date_to"
                                id="date_to"
                                class="form-control @error('date_to') is-invalid @enderror"
                                value="{{ $filters['date_to'] ?? '' }}"
                            >
                            @error('date_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-2">
                            <label for="per_page" class="form-label">Por página</label>
                            <select name="per_page" id="per_page" class="form-control">
                                @foreach ([10, 25, 50, 100] as $perPageOption)
                                    <option value="{{ $perPageOption }}" @selected((int) ($filters['per_page'] ?? 25) === $perPageOption)>
                                        {{ $perPageOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label for="search" class="form-label">Pesquisar</label>
                            <input
                                type="text"
                                name="search"
                                id="search"
                                class="form-control"
                                value="{{ $filters['search'] ?? '' }}"
                                placeholder="Entidade, ação, ID, utilizador ou email..."
                            >
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('activity-logs.index') }}" class="btn btn-default">Limpar</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <header class="card-header">
                <h2 class="card-title mb-0">Registos de atividade</h2>
            </header>

            <div class="card-body">
                @if ($activityLogs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Utilizador</th>
                                    <th>Ação</th>
                                    <th>Entidade</th>
                                    <th>Entity ID</th>
                                    <th>Payload</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activityLogs as $log)
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                        <td>
                                            @if ($log->user)
                                                <strong>{{ $log->user->name }}</strong><br>
                                                <small>{{ $log->user->email }}</small>
                                            @else
                                                <span class="text-muted">Utilizador removido</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->action }}</td>
                                        <td>{{ $log->entity }}</td>
                                        <td>{{ $log->entity_id }}</td>
                                        <td style="min-width: 320px; max-width: 520px;">
                                            @if (! empty($log->payload))
                                                <details>
                                                    <summary>Ver detalhes</summary>
                                                    <pre class="mt-2 mb-0 p-2 bg-light border rounded" style="white-space: pre-wrap; word-break: break-word;">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </details>
                                            @else
                                                <span class="text-muted">Sem payload</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($activityLogs->hasPages())
                        <div class="mt-3">
                            {{ $activityLogs->links() }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-info mb-0">
                        Não existem registos de atividade para os filtros selecionados.
                    </div>
                @endif
            </div>
        </section>
    </section>
@endsection
