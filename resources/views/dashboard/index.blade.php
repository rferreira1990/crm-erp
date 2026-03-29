@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page_header')
<header class="page-header">
    <h2>Dashboard</h2>

    <div class="right-wrapper text-end">
        <ol class="breadcrumbs">
            <li>
                <a href="{{ route('dashboard') }}">
                    <i class="bx bx-home-alt"></i>
                </a>
            </li>
            <li><span>Painel</span></li>
        </ol>
    </div>
</header>
@endsection

@section('content')
@php
    $metricCards = [
        [
            'title' => 'Obras em curso',
            'value' => $worksInProgress,
            'icon' => 'bx bx-hard-hat',
            'class' => 'card-featured-primary',
        ],
        [
            'title' => 'Obras concluidas',
            'value' => $worksCompleted,
            'icon' => 'bx bx-check-circle',
            'class' => 'card-featured-success',
        ],
        [
            'title' => 'Obras suspensas',
            'value' => $worksSuspended,
            'icon' => 'bx bx-pause-circle',
            'class' => 'card-featured-warning',
        ],
        [
            'title' => 'Tarefas pendentes',
            'value' => $pendingTasks,
            'icon' => 'bx bx-task',
            'class' => 'card-featured-secondary',
        ],
        [
            'title' => 'Stock baixo',
            'value' => $lowStockCount,
            'icon' => 'bx bx-error-circle',
            'class' => 'card-featured-danger',
        ],
        [
            'title' => 'Mov. stock (7 dias)',
            'value' => $recentStockMovementsCount,
            'icon' => 'bx bx-transfer',
            'class' => 'card-featured-info',
        ],
    ];
@endphp

<div class="row mb-3">
    @foreach ($metricCards as $card)
        <div class="col-xl-2 col-md-4 mb-3">
            <section class="card card-featured-left {{ $card['class'] }} h-100">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col widget-summary-col-icon">
                            <div class="summary-icon bg-primary">
                                <i class="{{ $card['icon'] }}"></i>
                            </div>
                        </div>
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">{{ $card['title'] }}</h4>
                                <div class="info">
                                    <strong class="amount">
                                        {{ $card['value'] !== null ? number_format((float) $card['value'], 0, ',', '.') : '-' }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    @endforeach
</div>

<div class="row mb-3">
    <div class="col-xl-3 col-md-6 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Custos materiais</h2></header>
            <div class="card-body">
                <h3 class="mb-0">{{ $materialsCost !== null ? number_format((float) $materialsCost, 2, ',', '.') . ' €' : '-' }}</h3>
            </div>
        </section>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Custos mao de obra</h2></header>
            <div class="card-body">
                <h3 class="mb-0">{{ $laborCost !== null ? number_format((float) $laborCost, 2, ',', '.') . ' €' : '-' }}</h3>
            </div>
        </section>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Outros custos</h2></header>
            <div class="card-body">
                <h3 class="mb-0">{{ $otherCosts !== null ? number_format((float) $otherCosts, 2, ',', '.') . ' €' : '-' }}</h3>
            </div>
        </section>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Margem estimada global</h2></header>
            <div class="card-body">
                <h3 class="mb-1">{{ $estimatedMarginGlobal !== null ? number_format((float) $estimatedMarginGlobal, 2, ',', '.') . ' €' : '-' }}</h3>
                @if ($plannedRevenueGlobal !== null)
                    <div class="small text-muted">Receita prevista: {{ number_format((float) $plannedRevenueGlobal, 2, ',', '.') }} €</div>
                @endif
            </div>
        </section>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12 d-flex flex-wrap gap-2">
        @can('works.view')
            <a href="{{ route('dashboard.works') }}" class="btn btn-outline-primary btn-sm">Dashboard Obras</a>
        @endcan
        @can('stock.view')
            <a href="{{ route('dashboard.stock') }}" class="btn btn-outline-primary btn-sm">Dashboard Stock</a>
        @endcan
    </div>
</div>

<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Movimentos de stock recentes</h2>
                @can('stock.view')
                    <a href="{{ route('stock.index') }}" class="btn btn-light btn-sm border">Ver lista completa</a>
                @endcan
            </header>
            <div class="card-body">
                @if (! $canViewStock)
                    <div class="text-muted">Sem permissao para consultar stock.</div>
                @elseif ($recentStockMovements->isEmpty())
                    <div class="text-muted">Sem movimentos de stock recentes.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Data/hora</th>
                                    <th>Artigo</th>
                                    <th>Tipo</th>
                                    <th>Direcao</th>
                                    <th>Quantidade</th>
                                    <th>Origem</th>
                                    <th>Utilizador</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentStockMovements as $movement)
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
                                        <td>{{ str_replace('_', ' ', $movement->movement_type) }}</td>
                                        <td>{{ $movement->direction }}</td>
                                        <td>{{ number_format((float) $movement->quantity, 3, ',', '.') }}</td>
                                        <td>
                                            @if ($movement->workMaterial?->work)
                                                <a href="{{ route('works.show', $movement->workMaterial->work) }}">{{ $movement->workMaterial->work->code }}</a>
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
    </div>
</div>
@endsection
