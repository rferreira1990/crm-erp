@extends('layouts.admin')

@section('title', 'Detalhe do Orçamento')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1">Orçamento {{ $budget->code }}</h2>
            <div class="text-muted">
                Cliente: <strong>{{ $budget->customer->name ?? '—' }}</strong>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary">
                Voltar
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            Existem erros no formulário. Verifica os campos e tenta novamente.
        </div>
    @endif

    @if (! $budget->isEditable())
        <div class="alert alert-warning">
            Este orçamento está em estado <strong>{{ $budget->status }}</strong> e já não pode ser editado.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Dados do orçamento</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="small text-muted">Código</div>
                            <div>{{ $budget->code }}</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Estado</div>
                            <div>
                                @if ($budget->status === 'draft')
                                    Rascunho
                                @elseif ($budget->status === 'sent')
                                    Enviado
                                @elseif ($budget->status === 'approved')
                                    Aprovado
                                @elseif ($budget->status === 'rejected')
                                    Rejeitado
                                @else
                                    {{ $budget->status }}
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Data</div>
                            <div>{{ $budget->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Criado por</div>
                            <div>{{ $budget->creator->name ?? '—' }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Atualizado por</div>
                            <div>{{ $budget->updater->name ?? '—' }}</div>
                        </div>

                        <div class="col-12">
                            <div class="small text-muted">Notas</div>
                            <div>
                                @if ($budget->notes)
                                    {!! nl2br(e($budget->notes)) !!}
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Resumo</strong>
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <strong>{{ number_format((float) $budget->subtotal, 2, ',', '.') }} €</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Desconto</span>
                        <strong>{{ number_format((float) $budget->discount_total, 2, ',', '.') }} €</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>IVA</span>
                        <strong>{{ number_format((float) $budget->tax_total, 2, ',', '.') }} €</strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <span><strong>Total</strong></span>
                        <strong>{{ number_format((float) $budget->total, 2, ',', '.') }} €</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($budget->isEditable())
        @include('budgets.partials.add-item-form')
    @endif

    @include('budgets.partials.items-table')
@endsection
