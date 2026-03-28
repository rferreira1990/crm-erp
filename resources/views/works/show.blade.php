@extends('layouts.admin')

@section('title', 'Detalhe da Obra')

@section('content')
<div class="container-fluid py-4">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger mb-4">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="mb-1">{{ $work->name }}</h2>
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
                      onsubmit="return confirm('Pretendes mesmo apagar esta obra?');">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        Apagar
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="row">

        {{-- Coluna esquerda --}}
        <div class="col-lg-8">

            {{-- Dados principais --}}
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Dados da obra</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label text-muted">Estado</label>
                            <div>
                                @php
                                    $badgeClass = match($work->status) {
                                        \App\Models\Work::STATUS_PLANNED => 'secondary',
                                        \App\Models\Work::STATUS_IN_PROGRESS => 'primary',
                                        \App\Models\Work::STATUS_SUSPENDED => 'warning',
                                        \App\Models\Work::STATUS_COMPLETED => 'success',
                                        \App\Models\Work::STATUS_CANCELLED => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp

                                <span class="badge bg-{{ $badgeClass }}">
                                    {{ $work->status_label }}
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Cliente</label>
                            <div>
                                {{ $work->customer?->name ?? '-' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Tipo de obra</label>
                            <div>
                                {{ $work->work_type ?: '-' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Responsável técnico</label>
                            <div>
                                {{ $work->technicalManager?->name ?? '-' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Orçamento associado</label>
                            <div>
                                @if ($work->budget)
                                    <a href="{{ route('budgets.show', $work->budget) }}">
                                        {{ $work->budget->code }}
                                    </a>
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Local</label>
                            <div>
                                {{ $work->location ?: '-' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Código Postal</label>
                            <div>
                                {{ $work->postal_code ?: '-' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Cidade</label>
                            <div>
                                {{ $work->city ?: '-' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Início previsto</label>
                            <div>
                                {{ $work->start_date_planned?->format('d/m/Y') ?? '-' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Fim previsto</label>
                            <div>
                                {{ $work->end_date_planned?->format('d/m/Y') ?? '-' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Início real</label>
                            <div>
                                {{ $work->start_date_actual?->format('d/m/Y') ?? '-' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted">Fim real</label>
                            <div>
                                {{ $work->end_date_actual?->format('d/m/Y') ?? '-' }}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-muted">Descrição</label>
                            <div>
                                {!! nl2br(e($work->description ?: '-')) !!}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-muted">Notas internas</label>
                            <div>
                                {!! nl2br(e($work->internal_notes ?: '-')) !!}
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Histórico --}}
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Histórico de estados</strong>
                </div>

                <div class="card-body">
                    @if ($work->statusHistories->count())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
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
                                            <td>{{ $history->created_at?->format('d/m/Y H:i') }}</td>
                                            <td>
                                                {{ $history->old_status ? (\App\Models\Work::statuses()[$history->old_status] ?? $history->old_status) : '-' }}
                                            </td>
                                            <td>
                                                {{ \App\Models\Work::statuses()[$history->new_status] ?? $history->new_status }}
                                            </td>
                                            <td>{{ $history->notes ?: '-' }}</td>
                                            <td>{{ $history->changedBy?->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="mb-0 text-muted">Sem histórico disponível.</p>
                    @endif
                </div>
            </div>

        </div>

        {{-- Coluna direita --}}
        <div class="col-lg-4">

            {{-- Alterar estado --}}
            <div class="card mb-4">
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
                                        <option value="{{ $status }}">
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="status_notes" class="form-label">Observações</label>
                                <textarea name="status_notes"
                                          id="status_notes"
                                          rows="4"
                                          class="form-control"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Atualizar estado
                            </button>
                        </form>
                    @else
                        <p class="mb-0 text-muted">
                            Não existem transições disponíveis.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Equipa --}}
            <div class="card">
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
                        <p class="mb-0 text-muted">Sem equipa associada.</p>
                    @endif
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
