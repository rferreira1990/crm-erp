@extends('layouts.admin')

@section('title', 'Registo Diario da Obra')

@section('content')
@php
    $canUpdate = auth()->user()?->can('works.update');
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Registo Diario</h2>
        <div class="text-muted">
            Obra {{ $work->code }} - {{ $work->name }} | {{ $dailyReport->report_date?->format('d/m/Y') ?? '-' }}
        </div>
    </div>

    <div class="d-flex gap-2">
        @if ($canUpdate && $work->isEditable())
            <a href="{{ route('works.daily-reports.edit', [$work, $dailyReport]) }}" class="btn btn-primary">Editar</a>
            <form method="POST" action="{{ route('works.daily-reports.destroy', [$work, $dailyReport]) }}" onsubmit="return confirm('Remover este registo diario?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Remover</button>
            </form>
        @endif
        <a href="{{ route('works.daily-reports.index', $work) }}" class="btn btn-outline-secondary">Voltar ao diario</a>
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
            <div class="card-header">
                <strong>Resumo do dia</strong>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Data</label>
                        <div>{{ $dailyReport->report_date?->format('d/m/Y') ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado do dia</label>
                        <div><span class="badge bg-light text-dark border">{{ $dailyReport->day_status_label }}</span></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Horas gastas</label>
                        <div>{{ number_format((float) $dailyReport->hours_spent, 2, ',', '.') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Custo hora (snapshot)</label>
                        <div>{{ number_format((float) ($dailyReport->user_hourly_cost_snapshot ?? 0), 2, ',', '.') }} &euro;</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Custo mao de obra (snapshot)</label>
                        <div>{{ number_format((float) ($dailyReport->labor_cost_total_snapshot ?? 0), 2, ',', '.') }} &euro;</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Trabalhos executados</label>
                        <div>{!! nl2br(e($dailyReport->work_summary)) !!}</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Ocorrencias</label>
                        <div>{!! nl2br(e($dailyReport->incidents ?: '-')) !!}</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observacoes</label>
                        <div>{!! nl2br(e($dailyReport->notes ?: '-')) !!}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Materiais aplicados</strong>
                <span class="badge bg-light text-dark border">{{ $dailyReport->items->count() }}</span>
            </div>
            <div class="card-body">
                @if ($dailyReport->items->count())
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Artigo</th>
                                    <th>Descricao</th>
                                    <th>Quantidade</th>
                                    <th>Unidade</th>
                                    <th>Custo unit. (snapshot)</th>
                                    <th>Total (snapshot)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dailyReport->items as $reportItem)
                                    <tr>
                                        <td>{{ $reportItem->item?->code ?? '-' }}</td>
                                        <td>{{ $reportItem->description_snapshot }}</td>
                                        <td>{{ number_format((float) $reportItem->quantity, 3, ',', '.') }}</td>
                                        <td>{{ $reportItem->unit_snapshot ?: '-' }}</td>
                                        <td>{{ number_format((float) ($reportItem->unit_cost_snapshot ?? 0), 2, ',', '.') }} &euro;</td>
                                        <td>{{ number_format((float) ($reportItem->total_cost_snapshot ?? 0), 2, ',', '.') }} &euro;</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">Total materiais (snapshot)</th>
                                    <th>{{ number_format((float) $dailyReport->items->sum(fn ($item) => (float) ($item->total_cost_snapshot ?? 0)), 2, ',', '.') }} &euro;</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-muted">Sem materiais registados neste dia.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Auditoria</strong>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <div class="text-muted small">Registado por</div>
                    <div>{{ $dailyReport->user?->name ?? '-' }}</div>
                </div>
                <div class="mb-2">
                    <div class="text-muted small">Criado em</div>
                    <div>{{ $dailyReport->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
                </div>
                <div class="mb-0">
                    <div class="text-muted small">Atualizado em</div>
                    <div>{{ $dailyReport->updated_at?->format('d/m/Y H:i') ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header"><strong>Navegacao</strong></div>
            <div class="card-body d-grid gap-2">
                @if ($canUpdate && $work->isEditable())
                    <a href="{{ route('works.daily-reports.create', $work) }}" class="btn btn-outline-primary">Novo registo diario</a>
                @endif
                <a href="{{ route('works.daily-reports.index', $work) }}" class="btn btn-outline-secondary">Lista do diario</a>
                <a href="{{ route('works.show', $work) }}" class="btn btn-outline-secondary">Ficha da obra</a>
            </div>
        </div>
    </div>
</div>
@endsection
