@extends('layouts.admin')

@section('title', 'Dashboard de Obras')

@section('page_header')
<header class="page-header">
    <h2>Dashboard de Obras</h2>

    <div class="right-wrapper text-end">
        <ol class="breadcrumbs">
            <li>
                <a href="{{ route('dashboard') }}">
                    <i class="bx bx-home-alt"></i>
                </a>
            </li>
            <li><span>Dashboard</span></li>
            <li><span>Obras</span></li>
        </ol>
    </div>
</header>
@endsection

@section('content')
<section class="card mb-3">
    <header class="card-header">
        <h2 class="card-title mb-0">Filtro de periodo</h2>
    </header>
    <div class="card-body">
        <form method="GET" action="{{ route('dashboard.works') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="date_from" class="form-label">Data de</label>
                <input type="date" id="date_from" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Data ate</label>
                <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                <a href="{{ route('dashboard.works') }}" class="btn btn-light btn-sm border">Limpar</a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm ms-auto">Voltar ao dashboard</a>
            </div>
        </form>
    </div>
</section>

<div class="row mb-3">
    <div class="col-md-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Obras sem responsavel tecnico</h2></header>
            <div class="card-body">
                <h3 class="mb-0">{{ $worksWithoutTechnicalManager->count() }}</h3>
            </div>
        </section>
    </div>
    <div class="col-md-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Obras com tarefas pendentes</h2></header>
            <div class="card-body">
                <h3 class="mb-0">{{ $worksWithPendingTasks->count() }}</h3>
            </div>
        </section>
    </div>
    <div class="col-md-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Obras concluidas no periodo</h2></header>
            <div class="card-body">
                <h3 class="mb-0">{{ $completedWorksInPeriodCount }}</h3>
            </div>
        </section>
    </div>
</div>

<div class="row">
    <div class="col-xl-6 mb-3">
        <section class="card h-100">
            <header class="card-header">
                <h2 class="card-title mb-0">Top obras por custo</h2>
            </header>
            <div class="card-body">
                @if ($topWorksByCost->isEmpty())
                    <div class="text-muted">Sem obras para apresentar.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Obra</th>
                                    <th>Estado</th>
                                    <th class="text-end">Custo total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topWorksByCost as $work)
                                    <tr>
                                        <td>
                                            <a href="{{ route('works.show', $work) }}">{{ $work->code }}</a>
                                            <div class="small text-muted">{{ $work->name }}</div>
                                        </td>
                                        <td>{{ \App\Models\Work::statuses()[$work->status] ?? $work->status }}</td>
                                        <td class="text-end fw-semibold">{{ number_format((float) $work->total_cost_dashboard, 2, ',', '.') }} €</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div class="col-xl-6 mb-3">
        <section class="card h-100">
            <header class="card-header">
                <h2 class="card-title mb-0">Top obras por margem baixa</h2>
            </header>
            <div class="card-body">
                @if ($topWorksByLowMargin->isEmpty())
                    <div class="text-muted">Sem obras com receita associada para calcular margem.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Obra</th>
                                    <th class="text-end">Margem</th>
                                    <th class="text-end">% Margem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topWorksByLowMargin as $work)
                                    <tr>
                                        <td>
                                            <a href="{{ route('works.show', $work) }}">{{ $work->code }}</a>
                                            <div class="small text-muted">{{ $work->name }}</div>
                                        </td>
                                        <td class="text-end fw-semibold">{{ number_format((float) $work->gross_margin_dashboard, 2, ',', '.') }} €</td>
                                        <td class="text-end">
                                            {{ $work->gross_margin_percent_dashboard !== null ? number_format((float) $work->gross_margin_percent_dashboard, 2, ',', '.') . '%' : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

<div class="row">
    <div class="col-xl-6 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Obras sem responsavel tecnico</h2></header>
            <div class="card-body">
                @if ($worksWithoutTechnicalManager->isEmpty())
                    <div class="text-muted">Sem obras ativas sem responsavel tecnico.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($worksWithoutTechnicalManager as $work)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="{{ route('works.show', $work) }}">{{ $work->code }}</a>
                                    <div class="small text-muted">{{ $work->name }}</div>
                                </div>
                                <span class="badge bg-light text-dark border">{{ \App\Models\Work::statuses()[$work->status] ?? $work->status }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>

    <div class="col-xl-6 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Obras com tarefas pendentes</h2></header>
            <div class="card-body">
                @if ($worksWithPendingTasks->isEmpty())
                    <div class="text-muted">Sem obras com tarefas pendentes.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($worksWithPendingTasks as $work)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="{{ route('works.show', $work) }}">{{ $work->code }}</a>
                                    <div class="small text-muted">{{ $work->name }}</div>
                                </div>
                                <span class="badge bg-warning text-dark">{{ $work->pending_tasks_count }} pendentes</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>
</div>

<section class="card">
    <header class="card-header"><h2 class="card-title mb-0">Obras concluidas no periodo</h2></header>
    <div class="card-body">
        @if ($completedWorksInPeriod->isEmpty())
            <div class="text-muted">Sem obras concluidas no periodo selecionado.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Obra</th>
                            <th>Cliente</th>
                            <th>Data conclusao</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($completedWorksInPeriod as $work)
                            <tr>
                                <td>
                                    <a href="{{ route('works.show', $work) }}">{{ $work->code }}</a>
                                    <div class="small text-muted">{{ $work->name }}</div>
                                </td>
                                <td>{{ $work->customer?->name ?? '-' }}</td>
                                <td>{{ $work->end_date_actual?->format('d/m/Y') ?? $work->updated_at?->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection

