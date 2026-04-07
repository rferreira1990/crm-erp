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

    $movementTypeLabels = [
        'work_material' => 'Material de obra',
        'purchase_direct' => 'Compra direta',
        'purchase_receipt' => 'Rececao de encomenda',
        'purchase_return' => 'Devolucao a fornecedor',
        'manual_entry' => 'Entrada manual',
        'manual_exit' => 'Saida manual',
        'manual_adjustment' => 'Ajuste manual',
    ];

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
@endphp

<div class="row mb-3">
    @foreach ($metricCards as $card)
        <div class="col-xxl-2 col-xl-4 col-md-6 mb-3">
            <section class="card card-featured-left {{ $card['class'] }} h-100 dashboard-kpi-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="summary-icon bg-primary flex-shrink-0">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="kpi-label text-muted text-uppercase">{{ $card['title'] }}</div>
                            <div class="kpi-value fw-bold mt-1">
                                {{ $card['value'] !== null ? number_format((float) $card['value'], 0, ',', '.') : '-' }}
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
                <h3 class="mb-0">
                    @if ($materialsCost !== null)
                        {{ number_format((float) $materialsCost, 2, ',', '.') }} &euro;
                    @else
                        -
                    @endif
                </h3>
            </div>
        </section>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Custos mao de obra</h2></header>
            <div class="card-body">
                <h3 class="mb-0">
                    @if ($laborCost !== null)
                        {{ number_format((float) $laborCost, 2, ',', '.') }} &euro;
                    @else
                        -
                    @endif
                </h3>
            </div>
        </section>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Outros custos</h2></header>
            <div class="card-body">
                <h3 class="mb-0">
                    @if ($otherCosts !== null)
                        {{ number_format((float) $otherCosts, 2, ',', '.') }} &euro;
                    @else
                        -
                    @endif
                </h3>
            </div>
        </section>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <section class="card h-100">
            <header class="card-header"><h2 class="card-title mb-0">Margem estimada global</h2></header>
            <div class="card-body">
                <h3 class="mb-1">
                    @if ($estimatedMarginGlobal !== null)
                        {{ number_format((float) $estimatedMarginGlobal, 2, ',', '.') }} &euro;
                    @else
                        -
                    @endif
                </h3>
                @if ($plannedRevenueGlobal !== null)
                    <div class="small text-muted">Receita prevista: {{ number_format((float) $plannedRevenueGlobal, 2, ',', '.') }} &euro;</div>
                @endif
            </div>
        </section>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12 d-flex flex-wrap gap-2">
        @can('works.view')
            <a href="{{ route('dashboard.works') }}" class="btn btn-outline-primary btn-sm">Dashboard Obras</a>
            <button type="button" class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#dashboardPrintTasksModal">
                Imprimir tarefas
            </button>
        @endcan
        @can('stock.view')
            <a href="{{ route('dashboard.stock') }}" class="btn btn-outline-primary btn-sm">Dashboard Stock</a>
        @endcan
    </div>
</div>

@if ($canViewFinancial && $financialMetrics)
    <section class="card mb-3">
        <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="card-title mb-0">Financeiro Operacional</h2>
            <form method="GET" action="{{ route('dashboard') }}" class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label for="financial_date_from" class="form-label mb-1">Periodo de</label>
                    <input type="date" id="financial_date_from" name="financial_date_from" class="form-control form-control-sm" value="{{ $financialFilters['financial_date_from'] }}">
                </div>
                <div>
                    <label for="financial_date_to" class="form-label mb-1">ate</label>
                    <input type="date" id="financial_date_to" name="financial_date_to" class="form-control form-control-sm" value="{{ $financialFilters['financial_date_to'] }}">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Aplicar</button>
            </form>
        </header>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Total a receber (clientes)</div>
                        <div class="h4 mb-0">{{ number_format((float) $financialMetrics['total_receivable'], 2, ',', '.') }} &euro;</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Total vencido (clientes)</div>
                        <div class="h4 mb-0 text-danger">{{ number_format((float) $financialMetrics['overdue_receivable'], 2, ',', '.') }} &euro;</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Total a pagar (fornecedores)</div>
                        <div class="h4 mb-0">{{ number_format((float) $financialMetrics['total_payable'], 2, ',', '.') }} &euro;</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Total vencido (fornecedores)</div>
                        <div class="h4 mb-0 text-danger">{{ number_format((float) $financialMetrics['overdue_payable'], 2, ',', '.') }} &euro;</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-xl-4 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Recebimentos no periodo</div>
                        <div class="h5 mb-0 text-success">{{ number_format((float) $financialMetrics['period_receipts'], 2, ',', '.') }} &euro;</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Pagamentos no periodo</div>
                        <div class="h5 mb-0 text-primary">{{ number_format((float) $financialMetrics['period_payments'], 2, ',', '.') }} &euro;</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-12">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Saldo liquido operacional</div>
                        <div class="h5 mb-0 {{ (float) $financialMetrics['net_operational_balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format((float) $financialMetrics['net_operational_balance'], 2, ',', '.') }} &euro;
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-xl-6">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th colspan="2">Aging clientes (a receber)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>0-30 dias</td><td class="text-end">{{ number_format((float) $financialMetrics['receivable_aging']['0_30'], 2, ',', '.') }} &euro;</td></tr>
                                <tr><td>31-60 dias</td><td class="text-end">{{ number_format((float) $financialMetrics['receivable_aging']['31_60'], 2, ',', '.') }} &euro;</td></tr>
                                <tr><td>61-90 dias</td><td class="text-end">{{ number_format((float) $financialMetrics['receivable_aging']['61_90'], 2, ',', '.') }} &euro;</td></tr>
                                <tr><td>90+ dias</td><td class="text-end">{{ number_format((float) $financialMetrics['receivable_aging']['90_plus'], 2, ',', '.') }} &euro;</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th colspan="2">Aging fornecedores (a pagar)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>0-30 dias</td><td class="text-end">{{ number_format((float) $financialMetrics['payable_aging']['0_30'], 2, ',', '.') }} &euro;</td></tr>
                                <tr><td>31-60 dias</td><td class="text-end">{{ number_format((float) $financialMetrics['payable_aging']['31_60'], 2, ',', '.') }} &euro;</td></tr>
                                <tr><td>61-90 dias</td><td class="text-end">{{ number_format((float) $financialMetrics['payable_aging']['61_90'], 2, ',', '.') }} &euro;</td></tr>
                                <tr><td>90+ dias</td><td class="text-end">{{ number_format((float) $financialMetrics['payable_aging']['90_plus'], 2, ',', '.') }} &euro;</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

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
                                    @php
                                        $movementTypeLabel = $movementTypeLabels[$movement->movement_type] ?? ucfirst(str_replace('_', ' ', (string) $movement->movement_type));
                                        $directionLabel = $directionLabels[$movement->direction] ?? ucfirst((string) $movement->direction);
                                        $directionClass = $directionClasses[$movement->direction] ?? 'bg-secondary';
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
                                        <td>{{ $movementTypeLabel }}</td>
                                        <td><span class="badge {{ $directionClass }}">{{ $directionLabel }}</span></td>
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

@can('works.view')
    <div class="modal fade" id="dashboardPrintTasksModal" tabindex="-1" aria-labelledby="dashboardPrintTasksModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="GET" action="{{ route('dashboard.tasks.print.view') }}" target="_blank">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dashboardPrintTasksModalLabel">Imprimir tarefas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Seleciona a data para gerar a folha de tarefas para impressao.</p>

                        <div class="mb-0">
                            <label for="dashboard_print_task_date" class="form-label">Data</label>
                            <input
                                type="date"
                                id="dashboard_print_task_date"
                                name="task_date"
                                class="form-control"
                                value="{{ now()->toDateString() }}"
                                required
                            >
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Ver para imprimir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
@endsection
