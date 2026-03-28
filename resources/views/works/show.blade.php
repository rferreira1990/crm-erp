@extends('layouts.admin')

@section('title', 'Detalhe da Obra')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">{{ $work->name }}</h2>
        <div class="text-muted">
            Código: <strong>{{ $work->code }}</strong>
        </div>
    </div>

    <div class="d-flex gap-2">
        @can('works.update')
            <a href="{{ route('works.edit', $work) }}" class="btn btn-primary">
                Editar
            </a>
        @endcan

        @can('works.delete')
            <form method="POST"
                  action="{{ route('works.destroy', $work) }}"
                  onsubmit="return confirm('Tens a certeza que queres apagar esta obra?');">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-outline-danger">
                    Apagar
                </button>
            </form>
        @endcan
    </div>
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

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Dados da obra</strong>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <div>
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
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cliente</label>
                        <div>{{ $work->customer?->name ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tipo de obra</label>
                        <div>{{ $work->work_type ?: '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Responsável técnico</label>
                        <div>{{ $work->technicalManager?->name ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Orçamento associado</label>
                        <div>
                            @if ($work->budget)
                                <a href="{{ route('budgets.show', $work->budget) }}">
                                    {{ $work->budget->code }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Local</label>
                        <div>{{ $work->location ?: '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Código Postal</label>
                        <div>{{ $work->postal_code ?: '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cidade</label>
                        <div>{{ $work->city ?: '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Início previsto</label>
                        <div>{{ $work->start_date_planned?->format('d/m/Y') ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Fim previsto</label>
                        <div>{{ $work->end_date_planned?->format('d/m/Y') ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Início real</label>
                        <div>{{ $work->start_date_actual?->format('d/m/Y') ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Fim real</label>
                        <div>{{ $work->end_date_actual?->format('d/m/Y') ?? '—' }}</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <div>{!! nl2br(e($work->description ?: '—')) !!}</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notas internas</label>
                        <div>{!! nl2br(e($work->internal_notes ?: '—')) !!}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Histórico de estados</strong>
            </div>

            <div class="card-body">
                @if ($work->statusHistories->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Estado anterior</th>
                                    <th>Novo estado</th>
                                    <th>Observações</th>
                                    <th>Utilizador</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($work->statusHistories as $history)
                                    <tr>
                                        <td>{{ $history->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td>
                                            @if ($history->old_status)
                                                {{ \App\Models\Work::statuses()[$history->old_status] ?? $history->old_status }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            {{ \App\Models\Work::statuses()[$history->new_status] ?? $history->new_status }}
                                        </td>
                                        <td>{{ $history->notes ?: '—' }}</td>
                                        <td>{{ $history->changedBy?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted">
                        Ainda não existe histórico de estados.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Alterar estado</strong>
            </div>

            <div class="card-body">
                @if (!empty($availableStatuses))
                    <form method="POST" action="{{ route('works.change-status', $work) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label for="status" class="form-label">Novo estado</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="">Seleciona</option>
                                @foreach ($availableStatuses as $status => $label)
                                    <option value="{{ $status }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="status_notes" class="form-label">Observações</label>
                            <textarea
                                name="status_notes"
                                id="status_notes"
                                rows="4"
                                class="form-control"
                            >{{ old('status_notes') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Atualizar estado
                        </button>
                    </form>
                @else
                    <div class="text-muted">
                        Não existem transições disponíveis para o estado atual.
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <strong>Equipa associada</strong>
            </div>

            <div class="card-body">
                @if ($work->team->count())
                    <ul class="list-group list-group-flush">
                        @foreach ($work->team as $member)
                            <li class="list-group-item px-0">
                                {{ $member->name }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted">
                        Sem equipa associada.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
