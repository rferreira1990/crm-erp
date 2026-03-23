@extends('layouts.admin')

@section('title', 'Ficha de Orçamento')

@section('content')
    @php
        $canUpdateBudget = auth()->user()?->can('budgets.update');
        $isEditable = $budget->isEditable();
        $canEditLines = $canUpdateBudget && $isEditable;

        $budgetNumber = str_replace('ORC-', '', (string) $budget->code);
        $statusLabel = method_exists($budget, 'statusLabel') ? $budget->statusLabel() : ucfirst((string) $budget->status);

        $subtotalValue = (float) $budget->subtotal;
        $taxValue = (float) $budget->tax_total;
        $totalValue = (float) $budget->total;
    @endphp

    <style>
        .budget-sheet-card {
            border: 1px solid #d9d9d9;
            background: #fff;
            border-radius: 0.35rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            margin-bottom: 1.5rem;
        }

        .budget-sheet-tab {
            display: inline-block;
            background: #3d3d3d;
            color: #fff;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
            text-transform: uppercase;
        }

        .budget-sheet-body {
            padding: 1.25rem;
        }

        .budget-field {
            margin-bottom: 1.5rem;
        }

        .budget-field-label {
            display: block;
            font-size: 0.82rem;
            color: #2ea3db;
            margin-bottom: 0.35rem;
        }

        .budget-field-value,
        .budget-field .form-control-plaintext {
            min-height: 38px;
            padding: 0.45rem 0;
            border-bottom: 1px solid #dcdcdc;
            color: #222;
        }

        .budget-field-readonly {
            min-height: 38px;
            padding: 0.45rem 0;
            border-bottom: 1px solid #dcdcdc;
            color: #222;
        }

        .budget-inline-action {
            display: flex;
            justify-content: flex-end;
            align-items: flex-start;
            height: 100%;
        }

        .budget-primary-btn {
            background: #2ea3db;
            border-color: #2ea3db;
            color: #fff;
            font-weight: 600;
        }

        .budget-primary-btn:hover,
        .budget-primary-btn:focus {
            background: #258fc1;
            border-color: #258fc1;
            color: #fff;
        }

        .budget-section-toggle {
            width: 100%;
            background: transparent;
            border: 0;
            padding: 0.95rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            color: #222;
        }

        .budget-section-toggle:hover {
            background: #fafafa;
        }

        .budget-section-toggle .budget-chevron {
            transition: transform 0.2s ease;
            font-size: 0.9rem;
        }

        .budget-section-toggle[aria-expanded="true"] .budget-chevron {
            transform: rotate(180deg);
        }

        .budget-section-content {
            border-top: 1px solid #e6e6e6;
            padding: 1.25rem;
        }

        .budget-articles-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e6e6e6;
        }

        .budget-articles-title {
            font-weight: 700;
            margin-bottom: 0.15rem;
        }

        .budget-articles-subtitle {
            font-size: 0.82rem;
            color: #666;
        }

        .budget-articles-table thead th {
            background: #8d8d8d;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
            border-color: #8d8d8d;
            white-space: nowrap;
        }

        .budget-articles-table td {
            vertical-align: middle;
        }

        .budget-articles-search-row td {
            background: #fafafa;
        }

        .budget-total-box {
            padding: 1rem 1.25rem;
        }

        .budget-total-label {
            font-size: 0.85rem;
            color: #6b6b6b;
            margin-bottom: 0.35rem;
        }

        .budget-total-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #222;
        }

        .budget-total-strip {
            background: #2ea3db;
            color: #fff;
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: 1.15rem;
        }

        .budget-muted-line {
            font-size: 0.85rem;
            color: #7a7a7a;
        }

        .budget-status-badge {
            display: inline-block;
            padding: 0.45rem 0.7rem;
            border-radius: 0.35rem;
            font-weight: 600;
            font-size: 0.85rem;
            background: #f2f2f2;
            color: #333;
        }

        .budget-status-draft { background: #e9ecef; color: #495057; }
        .budget-status-sent { background: #d1ecf1; color: #0c5460; }
        .budget-status-approved { background: #d4edda; color: #155724; }
        .budget-status-rejected { background: #f8d7da; color: #721c24; }

        .budget-line-note {
            white-space: pre-line;
            font-size: 0.9rem;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1">Ficha de Orçamento</h2>
            <div class="text-muted">
                Documento {{ $budget->code }}
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
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

    @if (! $isEditable)
        <div class="alert alert-warning">
            Este orçamento está em estado <strong>{{ $statusLabel }}</strong> e já não pode ser editado.
        </div>
    @endif

    <div class="budget-sheet-card">
        <div class="budget-sheet-tab">GERAL</div>

        <div class="budget-sheet-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div class="h5 mb-0">Ficha de Orçamento</div>

                @if ($canUpdateBudget)
                    <div class="d-flex flex-wrap gap-2">
                        @if ($budget->canChangeToStatus('sent'))
                            <form method="POST" action="{{ route('budgets.change-status', $budget) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="sent">
                                <button type="submit" class="btn btn-sm budget-primary-btn">
                                    Marcar como enviado
                                </button>
                            </form>
                        @endif

                        @if ($budget->canChangeToStatus('approved'))
                            <form method="POST" action="{{ route('budgets.change-status', $budget) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    Aprovar
                                </button>
                            </form>
                        @endif

                        @if ($budget->canChangeToStatus('rejected'))
                            <form method="POST" action="{{ route('budgets.change-status', $budget) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    Rejeitar
                                </button>
                            </form>
                        @endif

                        @if ($budget->canChangeToStatus('draft'))
                            <form method="POST" action="{{ route('budgets.change-status', $budget) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="draft">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    Voltar a rascunho
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="budget-field">
                        <label class="budget-field-label">Série</label>
                        <div class="budget-field-readonly">39 Orçamento</div>
                    </div>
                </div>

                <div class="col-lg-2">
                    <div class="budget-field">
                        <label class="budget-field-label">Nº</label>
                        <div class="budget-field-readonly">{{ ltrim($budgetNumber, '0') !== '' ? ltrim($budgetNumber, '0') : '0' }}</div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="budget-inline-action">
                        <button type="button" class="btn budget-primary-btn">
                            Adicionar contacto
                        </button>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="budget-field">
                        <label class="budget-field-label">Designação</label>
                        <div class="budget-field-readonly">
                            {{ $budget->notes ? \Illuminate\Support\Str::limit(trim(strip_tags($budget->notes)), 80) : '—' }}
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="budget-field">
                        <label class="budget-field-label">Cliente</label>
                        <div class="budget-field-readonly">
                            {{ $budget->customer->name ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-top">
            <button
                class="budget-section-toggle"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#budget-details-section"
                aria-expanded="false"
                aria-controls="budget-details-section"
            >
                <span class="budget-chevron">▼</span>
                <span>Detalhes do Orçamento</span>
            </button>

            <div id="budget-details-section" class="collapse">
                <div class="budget-section-content">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="budget-field">
                                <label class="budget-field-label">Vendedor</label>
                                <div class="budget-field-readonly">
                                    {{ $budget->creator->name ?? 'Vendedor geral' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="budget-field">
                                <label class="budget-field-label">Zona</label>
                                <div class="budget-field-readonly">Zona 1</div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="budget-field">
                                <label class="budget-field-label">Situação</label>
                                <div class="budget-field-readonly">
                                    <span class="budget-status-badge budget-status-{{ $budget->status }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="budget-field">
                                <label class="budget-field-label">Data da Situação</label>
                                <div class="budget-field-readonly">
                                    {{ $budget->updated_at?->format('Y-m-d') ?? $budget->created_at?->format('Y-m-d') ?? '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="budget-field">
                                <label class="budget-field-label">Projeto</label>
                                <div class="budget-field-readonly">—</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-top">
            <button
                class="budget-section-toggle"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#budget-notes-section"
                aria-expanded="false"
                aria-controls="budget-notes-section"
            >
                <span class="budget-chevron">▼</span>
                <span>Observações</span>
            </button>

            <div id="budget-notes-section" class="collapse">
                <div class="budget-section-content">
                    <div class="budget-field mb-0">
                        <label class="budget-field-label">Observações</label>
                        <div class="budget-field-readonly" style="min-height: 120px;">
                            {!! nl2br(e($budget->notes ?: '—')) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="budget-sheet-card">
        <div class="budget-articles-header">
            <div class="budget-articles-title">Artigos</div>
            <div class="budget-articles-subtitle">Preços sem IVA incluído</div>
        </div>

        <div class="budget-sheet-body pt-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 budget-articles-table">
                    <thead>
                        <tr>
                            <th style="min-width: 220px;">Artigo</th>
                            <th style="min-width: 260px;">Designação</th>
                            <th style="min-width: 90px;">Qtd.</th>
                            <th style="min-width: 100px;">Unidade</th>
                            <th style="min-width: 130px;">Preço Unitário</th>
                            <th style="min-width: 90px;">%Desc.</th>
                            <th style="min-width: 90px;">%IVA</th>
                            <th style="min-width: 130px;">Valor</th>
                            @if ($canEditLines)
                                <th style="min-width: 220px;">Ações</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @if ($canEditLines)
                            <tr class="budget-articles-search-row">
                                <td colspan="{{ $canEditLines ? 9 : 8 }}">
                                    <form method="POST" action="{{ route('budgets.items.store', $budget) }}" class="row g-2 align-items-end">
                                        @csrf

                                        <div class="col-xl-4 col-lg-5">
                                            <label for="item_id" class="form-label mb-1">Pesquisar Artigo</label>
                                            <select
                                                name="item_id"
                                                id="item_id"
                                                class="form-select @error('item_id') is-invalid @enderror"
                                                required
                                            >
                                                <option value="">Selecionar artigo</option>
                                                @foreach ($availableItems as $item)
                                                    <option
                                                        value="{{ $item->id }}"
                                                        {{ old('item_id') == $item->id ? 'selected' : '' }}
                                                    >
                                                        {{ $item->code }} - {{ $item->name }}
                                                        @if ($item->sale_price !== null)
                                                            | {{ number_format((float) $item->sale_price, 2, ',', '.') }} €
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-xl-1 col-lg-2">
                                            <label for="quantity" class="form-label mb-1">Qtd.</label>
                                            <input
                                                type="number"
                                                name="quantity"
                                                id="quantity"
                                                class="form-control @error('quantity') is-invalid @enderror"
                                                value="{{ old('quantity', 1) }}"
                                                min="0.001"
                                                step="0.001"
                                                required
                                            >
                                        </div>

                                        <div class="col-xl-1 col-lg-2">
                                            <label for="discount_percent" class="form-label mb-1">%Desc.</label>
                                            <input
                                                type="number"
                                                name="discount_percent"
                                                id="discount_percent"
                                                class="form-control @error('discount_percent') is-invalid @enderror"
                                                value="{{ old('discount_percent', 0) }}"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                            >
                                        </div>

                                        <div class="col-xl-6 col-lg-3 d-flex flex-wrap gap-2">
                                            <button type="submit" class="btn budget-primary-btn">
                                                Adicionar artigo
                                            </button>

                                            <button type="submit" class="btn budget-primary-btn">
                                                Adicionar serviço
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif

                        @forelse ($budget->items as $line)
                            @php
                                $collapseId = 'line-notes-' . $line->id;
                                $taxReasonWrapperId = 'tax-reason-wrapper-' . $line->id;
                                $taxRateSelectId = 'tax-rate-id-' . $line->id;

                                $currentTaxRate = $taxRates->firstWhere('id', $line->tax_rate_id);
                                $currentIsExempt = $currentTaxRate
                                    ? (bool) $currentTaxRate->is_exempt
                                    : ((float) $line->tax_percent === 0.0 && !empty($line->tax_exemption_reason));
                            @endphp

                            <tr>
                                @if ($canEditLines)
                                    <td>
                                        <form method="POST" action="{{ route('budgets.items.update', [$budget, $line]) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="fw-semibold">{{ $line->item_code ?: '—' }}</div>
                                            <div class="budget-muted-line">{{ $line->item_type === 'service' ? 'Serviço' : 'Artigo' }}</div>
                                    </td>

                                    <td>
                                            <div class="fw-semibold">{{ $line->item_name }}</div>
                                            @if ($line->description)
                                                <div class="budget-muted-line">{{ $line->description }}</div>
                                            @endif
                                    </td>

                                    <td>
                                            <input
                                                type="number"
                                                name="quantity"
                                                class="form-control form-control-sm"
                                                value="{{ number_format((float) $line->quantity, 3, '.', '') }}"
                                                min="0.001"
                                                step="0.001"
                                                required
                                            >
                                    </td>

                                    <td>
                                            {{ $line->unit_name ?: '—' }}
                                    </td>

                                    <td>
                                            <input
                                                type="number"
                                                name="unit_price"
                                                class="form-control form-control-sm"
                                                value="{{ number_format((float) $line->unit_price, 2, '.', '') }}"
                                                min="0"
                                                step="0.01"
                                                required
                                            >
                                    </td>

                                    <td>
                                            <input
                                                type="number"
                                                name="discount_percent"
                                                class="form-control form-control-sm"
                                                value="{{ number_format((float) $line->discount_percent, 2, '.', '') }}"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                            >
                                    </td>

                                    <td>
                                            <div class="d-flex flex-column gap-2">
                                                <select
                                                    name="tax_rate_id"
                                                    id="{{ $taxRateSelectId }}"
                                                    class="form-select form-select-sm tax-rate-select"
                                                    data-target="#{{ $taxReasonWrapperId }}"
                                                    required
                                                >
                                                    @foreach ($taxRates as $taxRate)
                                                        <option
                                                            value="{{ $taxRate->id }}"
                                                            data-is-exempt="{{ $taxRate->is_exempt ? '1' : '0' }}"
                                                            data-default-reason-id="{{ $taxRate->exemption_reason_id }}"
                                                            {{ (int) $line->tax_rate_id === (int) $taxRate->id ? 'selected' : '' }}
                                                        >
                                                            {{ number_format((float) $taxRate->percent, 2, ',', '.') }}%
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <div
                                                    id="{{ $taxReasonWrapperId }}"
                                                    class="tax-reason-wrapper"
                                                    style="{{ $currentIsExempt ? '' : 'display:none;' }}"
                                                >
                                                    <select
                                                        name="tax_exemption_reason_id"
                                                        class="form-select form-select-sm tax-exemption-reason-select"
                                                    >
                                                        <option value="">Motivo isenção</option>

                                                        @foreach ($taxExemptionReasons as $reason)
                                                            <option
                                                                value="{{ $reason->id }}"
                                                                {{ (int) $line->tax_exemption_reason_id === (int) $reason->id ? 'selected' : '' }}
                                                            >
                                                                {{ $reason->code }} - {{ $reason->description }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                    </td>

                                    <td class="fw-semibold">
                                            {{ number_format((float) $line->total, 2, ',', '.') }} €
                                    </td>

                                    <td>
                                            <div class="d-flex flex-column gap-2">
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                                        Guardar
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#{{ $collapseId }}"
                                                        aria-expanded="false"
                                                        aria-controls="{{ $collapseId }}"
                                                    >
                                                        ▼
                                                    </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('budgets.items.destroy', [$budget, $line]) }}"
                                            onsubmit="return confirm('Remover esta linha do orçamento?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Remover
                                            </button>
                                        </form>
                                                </div>

                                                @if (!empty($line->notes))
                                                    <div class="budget-muted-line">
                                                        Com observações
                                                    </div>
                                                @endif
                                            </div>
                                    </td>
                                @else
                                    <td>
                                        <div class="fw-semibold">{{ $line->item_code ?: '—' }}</div>
                                        <div class="budget-muted-line">{{ $line->item_type === 'service' ? 'Serviço' : 'Artigo' }}</div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $line->item_name }}</div>
                                        @if ($line->description)
                                            <div class="budget-muted-line">{{ $line->description }}</div>
                                        @endif
                                    </td>

                                    <td>{{ number_format((float) $line->quantity, 3, ',', '.') }}</td>
                                    <td>{{ $line->unit_name ?: '—' }}</td>
                                    <td>{{ number_format((float) $line->unit_price, 2, ',', '.') }} €</td>
                                    <td>{{ number_format((float) $line->discount_percent, 2, ',', '.') }}%</td>
                                    <td>
                                        {{ number_format((float) $line->tax_percent, 2, ',', '.') }}%
                                        @if ($line->tax_exemption_reason)
                                            <div class="budget-muted-line">{{ $line->tax_exemption_reason }}</div>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ number_format((float) $line->total, 2, ',', '.') }} €</td>
                                @endif
                            </tr>

                            @if ($canEditLines)
                                <tr class="collapse" id="{{ $collapseId }}">
                                    <td colspan="9" class="bg-light">
                                        <form method="POST" action="{{ route('budgets.items.update', [$budget, $line]) }}">
                                            @csrf
                                            @method('PUT')

                                            <input type="hidden" name="quantity" value="{{ number_format((float) $line->quantity, 3, '.', '') }}">
                                            <input type="hidden" name="unit_price" value="{{ number_format((float) $line->unit_price, 2, '.', '') }}">
                                            <input type="hidden" name="discount_percent" value="{{ number_format((float) $line->discount_percent, 2, '.', '') }}">
                                            <input type="hidden" name="tax_rate_id" value="{{ (int) $line->tax_rate_id }}">
                                            <input type="hidden" name="tax_exemption_reason_id" value="{{ (int) $line->tax_exemption_reason_id }}">

                                            <label for="notes-{{ $line->id }}" class="form-label mb-1">
                                                Observações da linha
                                            </label>

                                            <textarea
                                                name="notes"
                                                id="notes-{{ $line->id }}"
                                                rows="3"
                                                class="form-control"
                                                placeholder="Escreve aqui as observações desta linha"
                                            >{{ $line->notes }}</textarea>

                                            <div class="mt-2">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    Guardar observações
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @elseif (!empty($line->notes))
                                <tr class="table-light">
                                    <td colspan="8">
                                        <strong>Observações:</strong>
                                        <div class="budget-line-note">{{ $line->notes }}</div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $canEditLines ? 9 : 8 }}" class="text-center text-muted py-4">
                                    Este orçamento ainda não tem artigos adicionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="budget-sheet-card">
        <div class="budget-sheet-body pb-0">
            <div class="h5 mb-4">Valores Totais</div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="budget-total-box">
                        <div class="budget-total-label">Valor s/IVA</div>
                        <div class="h4 mb-0">{{ number_format($subtotalValue, 2, ',', '.') }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="budget-total-box">
                        <div class="budget-total-label">Valor IVA</div>
                        <div class="h4 mb-0">{{ number_format($taxValue, 2, ',', '.') }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="budget-total-box">
                        <div class="budget-total-label">Valor Total</div>
                        <div class="h4 mb-0">{{ number_format($totalValue, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="budget-total-strip">
            <span>Valor Total</span>
            <span>{{ number_format($totalValue, 2, ',', '.') }}</span>
        </div>
    </div>
@endsection

@if ($canEditLines)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.tax-rate-select').forEach(function (select) {
                    const targetSelector = select.getAttribute('data-target');
                    const wrapper = document.querySelector(targetSelector);

                    if (!wrapper) {
                        return;
                    }

                    const reasonSelect = wrapper.querySelector('.tax-exemption-reason-select');

                    const toggleReasonField = function () {
                        const selectedOption = select.options[select.selectedIndex];
                        const isExempt = selectedOption?.dataset?.isExempt === '1';
                        const defaultReasonId = selectedOption?.dataset?.defaultReasonId || '';

                        wrapper.style.display = isExempt ? 'block' : 'none';

                        if (!isExempt && reasonSelect) {
                            reasonSelect.value = '';
                        }

                        if (isExempt && reasonSelect && !reasonSelect.value && defaultReasonId) {
                            reasonSelect.value = defaultReasonId;
                        }
                    };

                    select.addEventListener('change', toggleReasonField);
                    toggleReasonField();
                });
            });
        </script>
    @endpush
@endif
