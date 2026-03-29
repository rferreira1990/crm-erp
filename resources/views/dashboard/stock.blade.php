@extends('layouts.admin')

@section('title', 'Dashboard de Stock')

@section('page_header')
<header class="page-header">
    <h2>Dashboard de Stock</h2>

    <div class="right-wrapper text-end">
        <ol class="breadcrumbs">
            <li>
                <a href="{{ route('dashboard') }}">
                    <i class="bx bx-home-alt"></i>
                </a>
            </li>
            <li><span>Dashboard</span></li>
            <li><span>Stock</span></li>
        </ol>
    </div>
</header>
@endsection

@section('content')
@php
    $directionLabels = [
        'in' => 'Entrada',
        'out' => 'Saida',
        'adjustment' => 'Ajuste',
    ];

    $directionClasses = [
        'in' => 'bg-success',
        'out' => 'bg-danger',
        'adjustment' => 'bg-warning text-dark',
    ];

    $movementTypeLabels = [
        'work_material' => 'Material de obra',
        'manual_entry' => 'Entrada manual',
        'manual_exit' => 'Saida manual',
        'manual_adjustment' => 'Ajuste manual',
    ];

    $manualReasonLabels = \App\Models\StockMovement::manualReasons();
@endphp

<section class="card mb-3">
    <header class="card-header">
        <h2 class="card-title mb-0">Filtro de periodo</h2>
    </header>
    <div class="card-body">
        <form method="GET" action="{{ route('dashboard.stock') }}" class="row g-3 align-items-end">
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
                <a href="{{ route('dashboard.stock') }}" class="btn btn-light btn-sm border">Limpar</a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm ms-auto">Voltar ao dashboard</a>
            </div>
        </form>
    </div>
</section>

<div class="row mb-3">
    <div class="col-xl-2 col-md-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Stock baixo</h2></header>
            <div class="card-body"><h3 class="mb-0">{{ $lowStockItems->count() }}</h3></div>
        </section>
    </div>

    <div class="col-xl-2 col-md-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Sem stock</h2></header>
            <div class="card-body"><h3 class="mb-0">{{ $outOfStockItems->count() }}</h3></div>
        </section>
    </div>

    <div class="col-xl-2 col-md-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Movimentos periodo</h2></header>
            <div class="card-body"><h3 class="mb-0">{{ $periodMovementsCount }}</h3></div>
        </section>
    </div>

    <div class="col-xl-2 col-md-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Entradas periodo</h2></header>
            <div class="card-body"><h3 class="mb-0">{{ number_format((float) $entriesQty, 3, ',', '.') }}</h3></div>
        </section>
    </div>

    <div class="col-xl-2 col-md-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Saidas periodo</h2></header>
            <div class="card-body"><h3 class="mb-0">{{ number_format((float) $exitsQty, 3, ',', '.') }}</h3></div>
        </section>
    </div>

    <div class="col-xl-2 col-md-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Mov. manuais periodo</h2></header>
            <div class="card-body">
                <h3 class="mb-1">{{ $manualMovementsInPeriodCount }}</h3>
                <div class="small text-muted">Ajustes: {{ number_format((float) $adjustmentsQty, 3, ',', '.') }}</div>
            </div>
        </section>
    </div>
</div>

<div class="row mb-3">
    <div class="col-xl-6 mb-3">
        <section class="card h-100">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Artigos abaixo do minimo</h2>
                <span class="badge bg-light text-dark border">{{ $lowStockItems->count() }}</span>
            </header>
            <div class="card-body">
                @if ($lowStockItems->isEmpty())
                    <div class="text-muted">Sem artigos abaixo do minimo.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Artigo</th>
                                    <th class="text-end">Stock atual</th>
                                    <th class="text-end">Stock minimo</th>
                                    <th class="text-end">Diferenca</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lowStockItems as $item)
                                    <tr>
                                        <td>
                                            @can('items.view')
                                                <a href="{{ route('items.show', $item) }}">{{ $item->code }}</a>
                                            @else
                                                <span class="fw-semibold">{{ $item->code }}</span>
                                            @endcan
                                            <div class="small text-muted">{{ $item->name }}</div>
                                        </td>
                                        <td class="text-end">{{ number_format((float) $item->current_stock, 3, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format((float) $item->min_stock, 3, ',', '.') }}</td>
                                        <td class="text-end text-danger">
                                            {{ number_format((float) ($item->min_stock - $item->current_stock), 3, ',', '.') }}
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

    <div class="col-xl-6 mb-3">
        <section class="card h-100">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Artigos sem stock</h2>
                <span class="badge bg-light text-dark border">{{ $outOfStockItems->count() }}</span>
            </header>
            <div class="card-body">
                @if ($outOfStockItems->isEmpty())
                    <div class="text-muted">Sem artigos esgotados.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($outOfStockItems as $item)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    @can('items.view')
                                        <a href="{{ route('items.show', $item) }}">{{ $item->code }}</a>
                                    @else
                                        <span class="fw-semibold">{{ $item->code }}</span>
                                    @endcan
                                    <div class="small text-muted">{{ $item->name }}</div>
                                </div>
                                <span class="badge bg-danger">{{ number_format((float) $item->current_stock, 3, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>
</div>

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Ultimos movimentos de stock</h2>
        <a href="{{ route('stock.index') }}" class="btn btn-light btn-sm border">Ver lista completa</a>
    </header>
    <div class="card-body">
        @if ($latestMovements->isEmpty())
            <div class="text-muted">Sem movimentos recentes.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Data/hora</th>
                            <th>Artigo</th>
                            <th>Tipo</th>
                            <th>Direcao</th>
                            <th class="text-end">Quantidade</th>
                            <th>Origem</th>
                            <th>Utilizador</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($latestMovements as $movement)
                            @php
                                $work = $movement->workMaterial?->work;
                                $directionLabel = $directionLabels[$movement->direction] ?? ucfirst((string) $movement->direction);
                                $directionClass = $directionClasses[$movement->direction] ?? 'bg-secondary';
                                $typeLabel = $movementTypeLabels[$movement->movement_type] ?? ucfirst(str_replace('_', ' ', (string) $movement->movement_type));
                            @endphp
                            <tr>
                                <td>{{ $movement->occurred_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>
                                    @if ($movement->item)
                                        <div class="fw-semibold">{{ $movement->item->code }}</div>
                                        <div class="small text-muted">{{ $movement->item->name }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $typeLabel }}</td>
                                <td><span class="badge {{ $directionClass }}">{{ $directionLabel }}</span></td>
                                <td class="text-end">{{ number_format((float) $movement->quantity, 3, ',', '.') }}</td>
                                <td>
                                    @if ($work)
                                        <a href="{{ route('works.show', $work) }}">Obra {{ $work->code }}</a>
                                        @if ($movement->work_material_id)
                                            <div class="small text-muted">Material #{{ $movement->work_material_id }}</div>
                                        @endif
                                    @else
                                        {{ $movement->source_type ?: '-' }}
                                    @endif
                                </td>
                                <td>{{ $movement->creator?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Movimentos manuais recentes</h2>
    </header>
    <div class="card-body">
        @if ($manualRecentMovements->isEmpty())
            <div class="text-muted">Sem movimentos manuais recentes.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Data/hora</th>
                            <th>Artigo</th>
                            <th>Tipo</th>
                            <th>Direcao</th>
                            <th class="text-end">Quantidade</th>
                            <th>Motivo</th>
                            <th>Justificacao</th>
                            <th>Utilizador</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($manualRecentMovements as $movement)
                            @php
                                $directionLabel = $directionLabels[$movement->direction] ?? ucfirst((string) $movement->direction);
                                $directionClass = $directionClasses[$movement->direction] ?? 'bg-secondary';
                                $typeLabel = $movementTypeLabels[$movement->movement_type] ?? ucfirst(str_replace('_', ' ', (string) $movement->movement_type));
                                $manualReasonLabel = $manualReasonLabels[$movement->manual_reason] ?? null;
                            @endphp
                            <tr>
                                <td>{{ $movement->occurred_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>
                                    @if ($movement->item)
                                        <div class="fw-semibold">{{ $movement->item->code }}</div>
                                        <div class="small text-muted">{{ $movement->item->name }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $typeLabel }}</td>
                                <td><span class="badge {{ $directionClass }}">{{ $directionLabel }}</span></td>
                                <td class="text-end">{{ number_format((float) $movement->quantity, 3, ',', '.') }}</td>
                                <td>{{ $manualReasonLabel ?: '-' }}</td>
                                <td>{{ $movement->notes ?: '-' }}</td>
                                <td>{{ $movement->creator?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection
