@extends('layouts.admin')

@section('title', 'Detalhe Conta a Receber')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">{{ $receivable->document_number }}</h2>
        <div class="small text-muted">Documento interno de conta a receber</div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        @can('customers.edit')
            @if ($receivable->isDraft())
                <form method="POST" action="{{ route('customer-receivables.issue', $receivable) }}" class="js-confirm-form" data-confirm-message="Emitir este documento e gerar lancamento automatico?">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Emitir documento</button>
                </form>
            @endif

            @if ($receivable->isIssued())
                <form method="POST" action="{{ route('customer-receivables.close', $receivable) }}" class="js-confirm-form" data-confirm-message="Fechar este documento?">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-warning">Fechar documento</button>
                </form>
            @endif

            @if (! $receivable->isClosed())
                <a href="{{ route('customer-receivables.edit', $receivable) }}" class="btn btn-outline-primary">Editar</a>
            @endif
        @endcan

        <a href="{{ route('customer-receivables.index') }}" class="btn btn-outline-secondary">Voltar</a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@php
    $statusClass = match ($receivable->status) {
        \App\Models\CustomerReceivable::STATUS_DRAFT => 'bg-secondary',
        \App\Models\CustomerReceivable::STATUS_ISSUED => 'bg-primary',
        \App\Models\CustomerReceivable::STATUS_CLOSED => 'bg-success',
        default => 'bg-dark',
    };
@endphp

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <section class="card h-100">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Cabecalho</h3>
                <span class="badge {{ $statusClass }}">{{ $receivable->statusLabel() }}</span>
            </header>

            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <strong>Cliente:</strong>
                        @if ($receivable->customer)
                            <a href="{{ route('customers.show', $receivable->customer) }}" class="text-primary">
                                {{ $receivable->customer->code ? $receivable->customer->code . ' - ' . $receivable->customer->name : $receivable->customer->name }}
                            </a>
                        @else
                            -
                        @endif
                    </div>
                    <div class="col-md-3"><strong>Data emissao:</strong> {{ $receivable->issue_date?->format('d/m/Y') ?: '-' }}</div>
                    <div class="col-md-3"><strong>Vencimento:</strong> {{ $receivable->due_date?->format('d/m/Y') ?: '-' }}</div>

                    <div class="col-md-4"><strong>Valor:</strong> {{ number_format((float) $receivable->amount, 2, ',', '.') }} EUR</div>
                    <div class="col-md-4"><strong>Criado por:</strong> {{ $receivable->creator?->name ?: ($receivable->user?->name ?: '-') }}</div>
                    <div class="col-md-4"><strong>Atualizado por:</strong> {{ $receivable->updater?->name ?: '-' }}</div>

                    <div class="col-md-6"><strong>Tipo referencia:</strong> {{ $receivable->reference_type ?: '-' }}</div>
                    <div class="col-md-6"><strong>ID referencia:</strong> {{ $receivable->reference_id ?: '-' }}</div>

                    <div class="col-12"><strong>Descricao:</strong> {{ $receivable->description }}</div>
                    <div class="col-12"><strong>Notas:</strong> {!! nl2br(e($receivable->notes ?: '-')) !!}</div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-4">
        <section class="card h-100">
            <header class="card-header">
                <h3 class="card-title mb-0">Fluxo operacional</h3>
            </header>

            <div class="card-body">
                <div class="mb-2">
                    <strong>Emitido em:</strong>
                    {{ $receivable->issued_at?->format('d/m/Y H:i') ?: '-' }}
                </div>
                <div class="mb-2">
                    <strong>Emitido por:</strong>
                    {{ $receivable->issuer?->name ?: '-' }}
                </div>
                <div class="mb-2">
                    <strong>Fechado em:</strong>
                    {{ $receivable->closed_at?->format('d/m/Y H:i') ?: '-' }}
                </div>
                <div class="mb-3">
                    <strong>Fechado por:</strong>
                    {{ $receivable->closer?->name ?: '-' }}
                </div>

                <hr>

                <div class="mb-2">
                    <strong>Conta corrente cliente:</strong>
                </div>

                @if ($receivable->accountEntry)
                    <span class="badge bg-info text-dark">Lancamento automatico</span>
                    <div class="small text-muted mt-2">
                        {{ $receivable->accountEntry->typeLabel() }}
                        - {{ number_format((float) $receivable->accountEntry->amount, 2, ',', '.') }} EUR
                        @if ($receivable->accountEntry->entry_date)
                            - {{ $receivable->accountEntry->entry_date->format('d/m/Y') }}
                        @endif
                    </div>
                    @if ($receivable->customer)
                        <a href="{{ route('customers.show', $receivable->customer) }}" class="btn btn-sm btn-outline-primary mt-2">Ver conta corrente no cliente</a>
                    @endif
                @else
                    <span class="badge bg-light text-dark border">Sem lancamento automatico</span>
                    <div class="small text-muted mt-2">O lancamento e criado quando o documento e emitido.</div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
