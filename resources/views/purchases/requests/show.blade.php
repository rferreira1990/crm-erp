@extends('layouts.admin')

@section('title', 'Detalhe do RFQ')

@section('content')
@php
    $statusBadgeClass = match ($purchaseRequest->status) {
        'closed' => 'bg-success',
        'cancelled' => 'bg-secondary',
        'sent' => 'bg-primary',
        default => 'bg-warning text-dark',
    };

    $nextStatuses = collect($statuses)->filter(fn ($label, $statusKey) => $purchaseRequest->canChangeTo($statusKey));
    $hasEmailLogs = $purchaseRequest->relationLoaded('emailLogs') && $purchaseRequest->emailLogs->isNotEmpty();
    $defaultSupplierId = (int) old('supplier_id', 0);
    $selectedSupplierForEmail = collect($suppliers)->firstWhere('id', $defaultSupplierId);
    $defaultRecipientName = old('recipient_name', $selectedSupplierForEmail?->contact_person ?: $selectedSupplierForEmail?->name ?: '');
    $defaultRecipientEmail = old('recipient_email', $selectedSupplierForEmail?->habitual_order_email ?: $selectedSupplierForEmail?->email ?: '');
    $defaultCcEmail = old('cc_email', $companyProfile?->mail_default_cc ?: '');
    $defaultBccEmail = old('bcc_email', $companyProfile?->mail_default_bcc ?: '');
    $defaultEmailNotes = old('email_notes', '');
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">{{ $purchaseRequest->code }} - {{ $purchaseRequest->title }}</h2>
        <div class="small text-muted">Pedido de cotacao / compras</div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#exportRfqPdfModal">Gerar PDF</button>

        @can('purchases.update')
            @if ($hasMailConfig)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendRfqEmailModal">
                    {{ $hasEmailLogs ? 'Reenviar por email' : 'Enviar por email' }}
                </button>
            @endif
        @endcan

        <a href="{{ route('purchase-requests.index') }}" class="btn btn-light border">Voltar</a>

        @can('purchases.update')
            @if ($purchaseRequest->isEditable())
                <a href="{{ route('purchase-requests.edit', $purchaseRequest) }}" class="btn btn-primary">Editar RFQ</a>
            @endif
        @endcan
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <section class="card h-100">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Dados do RFQ</h3>
                <span class="badge {{ $statusBadgeClass }}">{{ $statuses[$purchaseRequest->status] ?? $purchaseRequest->status }}</span>
            </header>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-6"><strong>Codigo:</strong> {{ $purchaseRequest->code }}</div>
                    <div class="col-md-6"><strong>Obra:</strong> {{ $purchaseRequest->work?->code ? $purchaseRequest->work->code . ' - ' . $purchaseRequest->work->name : '-' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6"><strong>Criado em:</strong> {{ $purchaseRequest->created_at?->format('d/m/Y H:i') ?: '-' }}</div>
                    <div class="col-md-6"><strong>Prazo propostas:</strong> {{ $purchaseRequest->deadline_at?->format('d/m/Y') ?: '-' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6"><strong>Enviado em:</strong> {{ $purchaseRequest->sent_at?->format('d/m/Y H:i') ?: '-' }}</div>
                    <div class="col-md-6"><strong>Criado por:</strong> {{ $purchaseRequest->creator?->name ?: '-' }}</div>
                </div>
                <div><strong>Notas:</strong><div class="text-muted mt-1">{{ $purchaseRequest->notes ?: '-' }}</div></div>
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="card h-100">
            <header class="card-header"><h3 class="card-title mb-0">Acoes</h3></header>
            <div class="card-body">
                @can('purchases.update')
                    @if ($nextStatuses->isNotEmpty())
                        <form method="POST" action="{{ route('purchase-requests.change-status', $purchaseRequest) }}" class="mb-3">
                            @csrf
                            @method('PATCH')
                            <label for="status" class="form-label">Alterar estado</label>
                            <div class="d-flex gap-2">
                                <select name="status" id="status" class="form-select">
                                    @foreach ($nextStatuses as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-primary">Atualizar</button>
                            </div>
                        </form>
                    @endif
                @endcan

                @can('purchases.delete')
                    @if (in_array($purchaseRequest->status, ['draft', 'cancelled'], true))
                        <form method="POST" action="{{ route('purchase-requests.destroy', $purchaseRequest) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Remover este RFQ?');">Remover RFQ</button>
                        </form>
                    @endif
                @endcan

                @if (! $hasMailConfig)
                    <div class="alert alert-warning mt-3 mb-0">SMTP da empresa incompleto. Configure o email da empresa para ativar envios de RFQ.</div>
                @endif
            </div>
        </section>
    </div>
</div>

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Linhas do pedido de cotacao</h3>
        <span class="badge bg-light text-dark border">{{ $purchaseRequest->items->count() }}</span>
    </header>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th><th>Artigo</th><th>Descricao</th><th class="text-end">Qtd</th><th>Unidade</th><th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseRequest->items as $line)
                        <tr>
                            <td>{{ $line->sort_order }}</td>
                            <td>@if($line->item) {{ $line->item->code }} <div class="small text-muted">{{ $line->item->name }}</div> @else - @endif</td>
                            <td>{{ $line->description }}</td>
                            <td class="text-end">{{ number_format((float) $line->qty, 3, ',', '.') }}</td>
                            <td>{{ $line->unit_snapshot ?: '-' }}</td>
                            <td>{{ $line->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Sem linhas no pedido.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Resumo global das propostas</h3>
        <span class="badge bg-light text-dark border">{{ $comparisonQuotes->count() }}</span>
    </header>
    <div class="card-body">
        @if ($comparisonQuotes->isEmpty())
            <div class="text-muted">Ainda sem propostas registadas para este RFQ.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fornecedor</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Lead time</th>
                            <th class="text-center">Cotadas</th>
                            <th class="text-center">Em falta</th>
                            <th class="text-center">Estado</th>
                            <th>Indicadores</th>
                            @can('purchases.update')
                                <th class="text-end">Acoes</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparisonQuotes as $quote)
                            @php
                                $isBestPrice = (int) $bestPriceQuoteId === (int) $quote->id;
                                $isBestLead = (int) $bestLeadQuoteId === (int) $quote->id;
                                $isSelected = (int) $selectedQuoteId === (int) $quote->id;
                                $summary = $summaryByQuoteId[(int) $quote->id] ?? ['quoted_lines_count' => 0, 'missing_lines_count' => 0];
                                $quoteStatusClass = $quote->status === 'selected'
                                    ? 'bg-success'
                                    : ($quote->status === 'rejected' ? 'bg-secondary' : 'bg-primary');
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $quote->supplier_name_snapshot }}</strong>
                                    @if ($quote->supplier?->code)
                                        <div class="small text-muted">{{ $quote->supplier->code }}</div>
                                    @endif
                                    @if ($quote->supplier?->catalogFiles?->isNotEmpty())
                                        <div class="small mt-1">
                                            <span class="badge bg-info text-dark">Catalogos: {{ $quote->supplier->catalogFiles->count() }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((float) $quote->total_amount, 2, ',', '.') }} {{ $quote->currency }}</td>
                                <td class="text-center">{{ $quote->lead_time_days !== null ? $quote->lead_time_days . ' dias' : '-' }}</td>
                                <td class="text-center">{{ $summary['quoted_lines_count'] }}</td>
                                <td class="text-center">{{ $summary['missing_lines_count'] }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $quoteStatusClass }}">{{ $quoteStatuses[$quote->status] ?? $quote->status }}</span>
                                </td>
                                <td>
                                    @if ($isBestPrice) <span class="badge bg-info text-dark">Melhor total</span> @endif
                                    @if ($isBestLead) <span class="badge bg-warning text-dark">Mais rapido</span> @endif
                                    @if ($isSelected) <span class="badge bg-success">Selecionada</span> @endif
                                </td>
                                @can('purchases.update')
                                    <td class="text-end">
                                        @if ($purchaseRequest->isEditable() && ! $isSelected)
                                            <form method="POST" action="{{ route('purchase-requests.quotes.select', [$purchaseRequest, $quote]) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-success">Selecionar</button>
                                            </form>
                                        @endif

                                        @if ($purchaseRequest->isEditable())
                                            <form method="POST" action="{{ route('purchase-requests.quotes.destroy', [$purchaseRequest, $quote]) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remover proposta deste fornecedor?');">
                                                    Remover
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Comparacao artigo a artigo</h3>
        <span class="badge bg-light text-dark border">{{ $purchaseRequest->items->count() }} linhas</span>
    </header>
    <div class="card-body">
        @if ($comparisonQuotes->isEmpty())
            <div class="text-muted">Registe propostas para ver comparacao detalhada por linha.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Artigo / Descricao</th><th class="text-end">Qtd pedida</th><th>Unidade</th>
                            @foreach ($comparisonQuotes as $quote)
                                <th>{{ $quote->supplier_name_snapshot }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparisonRows as $row)
                            @php($requestItem = $row['request_item'])
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $requestItem->item ? $requestItem->item->code . ' - ' . $requestItem->item->name : 'Linha manual' }}</div>
                                    <div>{{ $requestItem->description }}</div>
                                    @if ($requestItem->notes)<div class="small text-muted mt-1">{{ $requestItem->notes }}</div>@endif
                                </td>
                                <td class="text-end">{{ number_format((float) $requestItem->qty, 3, ',', '.') }}</td>
                                <td>{{ $requestItem->unit_snapshot ?: '-' }}</td>

                                @foreach ($row['cells'] as $cell)
                                    @php($quoteItem = $cell['quote_item'])
                                    <td class="{{ $cell['is_missing'] ? 'table-danger' : '' }} {{ $cell['qty_divergent'] ? 'table-warning' : '' }}">
                                        @if ($cell['is_missing'])
                                            <span class="badge bg-danger">Nao cotado</span>
                                        @else
                                            <div class="small"><strong>Preco unit.</strong> {{ $quoteItem->unit_price !== null ? number_format((float) $quoteItem->unit_price, 4, ',', '.') : '-' }}</div>
                                            <div class="small"><strong>Total linha</strong> {{ $quoteItem->line_total !== null ? number_format((float) $quoteItem->line_total, 2, ',', '.') : '-' }}</div>
                                            <div class="small"><strong>Prazo</strong> {{ $quoteItem->lead_time_days !== null ? $quoteItem->lead_time_days . ' dias' : '-' }}</div>
                                            @if ($quoteItem->quoted_qty !== null)
                                                <div class="small"><strong>Qtd cotada</strong> {{ number_format((float) $quoteItem->quoted_qty, 3, ',', '.') }}</div>
                                            @endif
                                            <div class="mt-1">
                                                @if ($cell['is_best_price']) <span class="badge bg-success">Melhor preco</span> @endif
                                                @if ($cell['is_fastest_lead']) <span class="badge bg-warning text-dark">Prazo mais curto</span> @endif
                                                @if ($cell['qty_divergent']) <span class="badge bg-dark">Qtd divergente</span> @endif
                                            </div>
                                            @if ($quoteItem->notes)
                                                <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($quoteItem->notes, 90) }}</div>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@can('purchases.update')
    @if ($purchaseRequest->isEditable() && $comparisonQuotes->isNotEmpty())
        <section class="card mb-3">
            <header class="card-header"><h3 class="card-title mb-0">Editar propostas existentes (linha a linha)</h3></header>
            <div class="card-body">
                @foreach ($comparisonQuotes as $quote)
                    <details class="mb-3">
                        <summary><strong>{{ $quote->supplier_name_snapshot }}</strong></summary>

                        <form method="POST" action="{{ route('purchase-requests.quotes.update', [$purchaseRequest, $quote]) }}" class="mt-3">
                            @csrf
                            @method('PUT')

                            <div class="row g-2 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Fornecedor</label>
                                    <select name="supplier_id" class="form-select" required>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected((int) $quote->supplier_id === (int) $supplier->id)>{{ $supplier->code }} - {{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Total global</label>
                                    <input type="number" name="total_amount" class="form-control" step="0.01" min="0" value="{{ number_format((float) $quote->total_amount, 2, '.', '') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Moeda</label>
                                    <input type="text" name="currency" class="form-control" maxlength="3" value="{{ $quote->currency }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Lead time</label>
                                    <input type="number" name="lead_time_days" class="form-control" min="0" value="{{ $quote->lead_time_days }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Valida ate</label>
                                    <input type="date" name="valid_until" class="form-control" value="{{ $quote->valid_until?->toDateString() }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Condicao pagamento</label>
                                    <input type="text" name="payment_term_snapshot" class="form-control" maxlength="120" value="{{ $quote->payment_term_snapshot }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Estado proposta</label>
                                    <select name="status" class="form-select" required>
                                        @foreach ($quoteStatuses as $quoteStatusKey => $quoteStatusLabel)
                                            <option value="{{ $quoteStatusKey }}" @selected($quote->status === $quoteStatusKey)>{{ $quoteStatusLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Notas gerais</label>
                                    <input type="text" name="notes" class="form-control" maxlength="5000" value="{{ $quote->notes }}">
                                </div>
                            </div>

                            @include('purchases.requests.partials.quote-lines-form', [
                                'purchaseRequest' => $purchaseRequest,
                                'quoteItemsByRequestItemId' => $quote->items->keyBy('purchase_request_item_id'),
                                'useOldValues' => false,
                            ])

                            <div class="d-flex justify-content-end mt-2"><button type="submit" class="btn btn-sm btn-outline-primary">Guardar alteracoes</button></div>
                        </form>
                    </details>
                @endforeach
            </div>
        </section>
    @endif
@endcan

@can('purchases.update')
    @if ($purchaseRequest->isEditable())
        <section class="card mb-3">
            <header class="card-header"><h3 class="card-title mb-0">Registar proposta de fornecedor</h3></header>
            <div class="card-body">
                <form method="POST" action="{{ route('purchase-requests.quotes.store', $purchaseRequest) }}">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="supplier_id" class="form-label">Fornecedor</label>
                            <select name="supplier_id" id="supplier_id" class="form-select" required>
                                <option value="">Selecionar...</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected((int) old('supplier_id') === (int) $supplier->id)>{{ $supplier->code }} - {{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><label for="total_amount" class="form-label">Total global</label><input type="number" name="total_amount" id="total_amount" class="form-control" step="0.01" min="0" value="{{ old('total_amount') }}"></div>
                        <div class="col-md-2"><label for="currency" class="form-label">Moeda</label><input type="text" name="currency" id="currency" class="form-control" maxlength="3" value="{{ old('currency', 'EUR') }}" required></div>
                        <div class="col-md-2"><label for="lead_time_days" class="form-label">Lead time</label><input type="number" name="lead_time_days" id="lead_time_days" class="form-control" min="0" value="{{ old('lead_time_days') }}"></div>
                        <div class="col-md-2"><label for="valid_until" class="form-label">Valida ate</label><input type="date" name="valid_until" id="valid_until" class="form-control" value="{{ old('valid_until') }}"></div>
                        <div class="col-md-4"><label for="payment_term_snapshot" class="form-label">Condicao pagamento</label><input type="text" name="payment_term_snapshot" id="payment_term_snapshot" class="form-control" maxlength="120" value="{{ old('payment_term_snapshot') }}"></div>
                        <div class="col-md-2">
                            <label for="quote_status" class="form-label">Estado</label>
                            <select name="status" id="quote_status" class="form-select" required>
                                @foreach ($quoteStatuses as $quoteStatusKey => $quoteStatusLabel)
                                    <option value="{{ $quoteStatusKey }}" @selected(old('status', 'received') === $quoteStatusKey)>{{ $quoteStatusLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label for="notes" class="form-label">Notas gerais</label><input type="text" name="notes" id="notes" class="form-control" maxlength="5000" value="{{ old('notes') }}"></div>
                    </div>

                    @include('purchases.requests.partials.quote-lines-form', [
                        'purchaseRequest' => $purchaseRequest,
                        'quoteItemsByRequestItemId' => collect(),
                        'useOldValues' => true,
                    ])

                    <div class="d-flex justify-content-end mt-3"><button type="submit" class="btn btn-primary">Guardar proposta</button></div>
                </form>
            </div>
        </section>
    @endif
@endcan

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Historico de emails</h3>
        <span class="badge bg-light text-dark border">{{ $purchaseRequest->emailLogs->count() }}</span>
    </header>
    <div class="card-body">
        @if ($purchaseRequest->emailLogs->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead><tr><th>Data/Hora</th><th>Enviado por</th><th>Destinatario</th><th>Email</th><th>Assunto</th><th>Mensagem</th></tr></thead>
                    <tbody>
                        @foreach ($purchaseRequest->emailLogs as $log)
                            <tr>
                                <td>{{ $log->sent_at?->format('d/m/Y H:i:s') ?: '-' }}</td>
                                <td>{{ $log->sender?->name ?: '-' }}</td>
                                <td>{{ $log->recipient_name ?: '-' }}</td>
                                <td>{{ $log->recipient_email }}</td>
                                <td>{{ $log->subject ?: '-' }}</td>
                                <td>{!! nl2br(e($log->message ?: '-')) !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-muted">Sem historico de envios para este RFQ.</div>
        @endif
    </div>
</section>

@include('purchases.requests.partials.export-pdf-modal', ['purchaseRequest' => $purchaseRequest, 'suppliers' => $suppliers])

@can('purchases.update')
    @if ($hasMailConfig)
        @include('purchases.requests.partials.send-email-modal', [
            'purchaseRequest' => $purchaseRequest,
            'suppliers' => $suppliers,
            'hasEmailLogs' => $hasEmailLogs,
            'defaultSupplierId' => $defaultSupplierId,
            'defaultRecipientName' => $defaultRecipientName,
            'defaultRecipientEmail' => $defaultRecipientEmail,
            'defaultCcEmail' => $defaultCcEmail,
            'defaultBccEmail' => $defaultBccEmail,
            'defaultEmailNotes' => $defaultEmailNotes,
            'emailAttachmentMaxMb' => $emailAttachmentMaxMb,
        ])
    @endif
@endcan
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const supplierSelect = document.getElementById('email_supplier_id');
        const recipientNameInput = document.getElementById('recipient_name');
        const recipientEmailInput = document.getElementById('recipient_email');

        if (supplierSelect && recipientNameInput && recipientEmailInput) {
            supplierSelect.addEventListener('change', function () {
                const selected = supplierSelect.options[supplierSelect.selectedIndex];
                if (!selected || !selected.value) {
                    return;
                }
                if (!recipientNameInput.value.trim()) {
                    recipientNameInput.value = selected.getAttribute('data-name') || '';
                }
                if (!recipientEmailInput.value.trim()) {
                    recipientEmailInput.value = selected.getAttribute('data-email') || '';
                }
            });
        }

        @if (session('open_send_email_modal') || $errors->has('supplier_id') || $errors->has('recipient_name') || $errors->has('recipient_email') || $errors->has('cc_email') || $errors->has('bcc_email') || $errors->has('email_notes') || $errors->has('email_attachment'))
            const sendEmailModalElement = document.getElementById('sendRfqEmailModal');
            if (sendEmailModalElement && typeof bootstrap !== 'undefined') {
                const sendEmailModal = new bootstrap.Modal(sendEmailModalElement);
                sendEmailModal.show();
            }
        @endif
    });
</script>
@endpush
