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
    $openSendEmailModal = (bool) (
        session('open_send_email_modal')
        || $errors->has('recipient_name')
        || $errors->has('recipient_email')
        || $errors->has('cc_email')
        || $errors->has('bcc_email')
        || $errors->has('email_notes')
        || $errors->has('email_attachment')
    );
    $openAwardModal = (bool) (
        session('open_award_modal')
        || $errors->has('mode')
        || $errors->has('forced_supplier_id')
        || $errors->has('justification')
        || $errors->has('allow_partial')
        || $errors->has('replace_existing')
    );
    $openAwardMode = session('open_award_modal', old('mode', ''));
    $openAwardEmailModal = (bool) (
        session('open_award_email_modal')
        || $errors->has('award_supplier_id')
        || $errors->has('award_recipient_name')
        || $errors->has('award_recipient_email')
        || $errors->has('award_cc_email')
        || $errors->has('award_bcc_email')
        || $errors->has('award_email_notes')
        || $errors->has('award_email_attachment')
    );
@endphp
<div
    id="purchase-request-show-config"
    data-supplier-item-reference-map="{{ e(json_encode($supplierItemReferenceMap ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"
    data-open-send-email-modal="{{ $openSendEmailModal ? '1' : '0' }}"
    data-open-award-modal="{{ $openAwardModal ? '1' : '0' }}"
    data-open-award-mode="{{ e((string) $openAwardMode) }}"
    data-open-award-email-modal="{{ $openAwardEmailModal ? '1' : '0' }}"
></div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">{{ $purchaseRequest->code }} - {{ $purchaseRequest->title }}</h2>
        <div class="small text-muted">Pedido de cotacao / compras</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('purchase-requests.comparison', $purchaseRequest) }}" class="btn btn-outline-secondary">Ver comparacao</a>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#exportRfqPdfModal">Gerar PDF</button>
        @can('purchases.update')
            @if ($hasMailConfig)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendRfqEmailModal">{{ $hasEmailLogs ? 'Reenviar por email' : 'Enviar por email' }}</button>
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

@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
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
                            @csrf @method('PATCH')
                            <label for="status" class="form-label">Alterar estado</label>
                            <div class="d-flex gap-2">
                                <select name="status" id="status" class="form-select">@foreach ($nextStatuses as $statusKey => $statusLabel)<option value="{{ $statusKey }}">{{ $statusLabel }}</option>@endforeach</select>
                                <button type="submit" class="btn btn-outline-primary">Atualizar</button>
                            </div>
                        </form>
                    @endif
                @endcan
                @can('purchases.delete')
                    @if (in_array($purchaseRequest->status, ['draft', 'cancelled'], true))
                        <form method="POST" action="{{ route('purchase-requests.destroy', $purchaseRequest) }}">@csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger js-confirm-submit" data-confirm-message="Remover este RFQ?">Remover RFQ</button>
                        </form>
                    @endif
                @endcan
                @if (! $hasMailConfig)<div class="alert alert-warning mt-3 mb-0">SMTP da empresa incompleto. Configure o email da empresa para ativar envios de RFQ.</div>@endif
            </div>
        </section>
    </div>
</div>

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Linhas do pedido de cotacao</h3><span class="badge bg-light text-dark border">{{ $purchaseRequest->items->count() }}</span></header>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0 align-middle">
                <thead><tr><th>#</th><th>Artigo</th><th>Descricao</th><th class="text-end">Qtd</th><th class="text-center">Unidade</th><th>Notas</th></tr></thead>
                <tbody>
                    @forelse ($purchaseRequest->items as $line)
                        <tr>
                            <td>{{ $line->sort_order }}</td>
                            <td>{{ $line->item?->code ?: 'MANUAL' }}</td>
                            <td>{{ $line->description }}</td>
                            <td class="text-end">{{ number_format((float) $line->qty, 3, ',', '.') }}</td>
                            <td class="text-center">{{ $line->item?->unit?->code ?: $line->unit_snapshot ?: '-' }}</td>
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

@include('purchases.requests.partials.award-panel', [
    'purchaseRequest' => $purchaseRequest,
    'awardPreview' => $awardPreview,
])

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Resumo global das propostas</h3>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('purchase-requests.comparison', $purchaseRequest) }}" class="btn btn-sm btn-outline-primary">Abrir comparacao completa</a>
            <span class="badge bg-light text-dark border">{{ $comparisonQuotes->count() }}</span>
        </div>
    </header>
    <div class="card-body">
        @if ($comparisonQuotes->isEmpty())
            <div class="text-muted">Ainda sem propostas registadas para este RFQ.</div>
        @else
            @if ($bestVsSecondTotalPercent !== null && $comparisonQuotes->count() > 1)
                <div class="alert alert-info py-2">
                    Melhor proposta global atualmente {{ number_format((float) $bestVsSecondTotalPercent, 2, ',', '.') }}% mais barata que a segunda melhor.
                </div>
            @endif
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fornecedor</th><th>Ref. proposta</th><th>Cond. pagamento</th><th class="text-end">Total s/ IVA</th><th class="text-center">Lead time</th><th class="text-center">Cotadas</th><th class="text-center">Em falta</th><th class="text-center">Estado</th><th>Indicadores</th><th class="text-center">PDF fornecedor</th>@can('purchases.update')<th class="text-end">Acoes</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparisonQuotes as $quote)
                            @php
                                $isBestPrice = (int) $bestPriceQuoteId === (int) $quote->id;
                                $isBestLead = (int) $bestLeadQuoteId === (int) $quote->id;
                                $isSelected = (int) $selectedQuoteId === (int) $quote->id;
                                $comparisonTotal = (float) ($quote->comparison_total_amount ?? $quote->total_amount);
                                $summary = $summaryByQuoteId[(int) $quote->id] ?? ['quoted_lines_count' => 0, 'missing_lines_count' => 0];
                                $totalComparison = $totalComparisonByQuoteId[(int) $quote->id] ?? ['delta_percent_vs_best' => null, 'best_cheaper_percent' => null];
                                $quoteStatusClass = $quote->status === 'selected' ? 'bg-success' : ($quote->status === 'rejected' ? 'bg-secondary' : 'bg-primary');
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $quote->supplier_name_snapshot }}</strong>
                                    @if ($quote->supplier?->code)<div class="small text-muted">{{ $quote->supplier->code }}</div>@endif
                                    @if ($quote->supplier?->catalogFiles?->isNotEmpty())<div class="small mt-1"><span class="badge bg-info text-dark">Catalogos: {{ $quote->supplier->catalogFiles->count() }}</span></div>@endif
                                </td>
                                <td>{{ $quote->supplier_quote_reference ?: '-' }}</td>
                                <td>{{ $quote->payment_term_snapshot ?: '-' }}</td>
                                <td class="text-end">{{ number_format($comparisonTotal, 2, ',', '.') }} {{ $quote->currency }}</td>
                                <td class="text-center">{{ $quote->lead_time_days !== null ? $quote->lead_time_days . ' dias' : '-' }}</td>
                                <td class="text-center">{{ $summary['quoted_lines_count'] }}</td>
                                <td class="text-center">{{ $summary['missing_lines_count'] }}</td>
                                <td class="text-center"><span class="badge {{ $quoteStatusClass }}">{{ $quoteStatuses[$quote->status] ?? $quote->status }}</span></td>
                                <td>
                                    @if ($isBestPrice)
                                        <span class="badge bg-info text-dark">Melhor total</span>
                                        @if ($bestVsSecondTotalPercent !== null && $comparisonQuotes->count() > 1)
                                            <div class="small mt-1">{{ number_format((float) $bestVsSecondTotalPercent, 2, ',', '.') }}% abaixo da 2a melhor</div>
                                        @endif
                                    @elseif ($totalComparison['delta_percent_vs_best'] !== null)
                                        @if ((float) $totalComparison['delta_percent_vs_best'] <= 0.0)
                                            <span class="badge bg-secondary">Mesmo preco</span>
                                        @else
                                            <span class="badge bg-light text-dark border">{{ number_format((float) $totalComparison['delta_percent_vs_best'], 2, ',', '.') }}% acima do melhor</span>
                                        @endif
                                    @endif
                                    @if ($isBestLead)<span class="badge bg-warning text-dark">Lead mais curto</span>@endif
                                    @if ($isSelected)<span class="badge bg-success">Selecionada</span>@endif
                                </td>
                                <td class="text-center">
                                    @if ($quote->quote_pdf_path)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchase-requests.quotes.pdf', [$purchaseRequest, $quote]) }}" target="_blank">Ver PDF</a>
                                        @can('purchases.update')
                                            @if ($purchaseRequest->isEditable())
                                                <form method="POST" action="{{ route('purchase-requests.quotes.remove-pdf', [$purchaseRequest, $quote]) }}" class="mt-1">@csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger js-confirm-submit" data-confirm-message="Remover PDF da proposta?">Remover</button>
                                                </form>
                                            @endif
                                        @endcan
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                @can('purchases.update')
                                    <td class="text-end">
                                        @if ($purchaseRequest->isEditable())
                                            <a href="{{ route('purchase-requests.quotes.edit', [$purchaseRequest, $quote]) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                        @endif
                                        @if ($purchaseRequest->isEditable() && ! $isSelected)
                                            <form method="POST" action="{{ route('purchase-requests.quotes.select', [$purchaseRequest, $quote]) }}" class="d-inline">@csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-success">Selecionar</button>
                                            </form>
                                        @endif
                                        @if ($purchaseRequest->isEditable())
                                            <form method="POST" action="{{ route('purchase-requests.quotes.destroy', [$purchaseRequest, $quote]) }}" class="d-inline">@csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger js-confirm-submit" data-confirm-message="Remover proposta deste fornecedor?">Remover</button>
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
    <header class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Comparacao artigo a artigo</h3><span class="badge bg-light text-dark border">{{ $purchaseRequest->items->count() }} linhas</span></header>
    <div class="card-body">
        @if ($comparisonQuotes->isEmpty())
            <div class="text-muted">Registe propostas para ver comparacao detalhada por linha.</div>
        @else
            <div class="alert alert-light border mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    A comparacao detalhada por artigo foi movida para uma pagina dedicada para facilitar leitura, analise de diferencas e exportacao PDF.
                </div>
                <a href="{{ route('purchase-requests.comparison', $purchaseRequest) }}" class="btn btn-primary">Ver comparacao completa</a>
            </div>
        @endif
    </div>
</section>

@can('purchases.update')
    @if ($purchaseRequest->isEditable())
        <section class="card mb-3" id="registar-proposta">
            <header class="card-header"><h3 class="card-title mb-0">Registar proposta de fornecedor</h3></header>
            <div class="card-body">
                <form method="POST" action="{{ route('purchase-requests.quotes.store', $purchaseRequest) }}" class="quote-form-wrapper" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label for="supplier_id" class="form-label">Fornecedor</label><select name="supplier_id" id="supplier_id" class="form-select supplier-selector" required><option value="">Selecionar...</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int) old('supplier_id') === (int) $supplier->id)>{{ $supplier->code }} - {{ $supplier->name }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label for="supplier_quote_reference" class="form-label">Ref. proposta fornecedor</label><input type="text" name="supplier_quote_reference" id="supplier_quote_reference" class="form-control" maxlength="120" value="{{ old('supplier_quote_reference') }}"></div>
                        <div class="col-md-3"><label for="payment_term_id" class="form-label">Condicao pagamento</label><select name="payment_term_id" id="payment_term_id" class="form-select"><option value="">Selecionar...</option>@foreach ($paymentTerms as $paymentTerm)<option value="{{ $paymentTerm->id }}" @selected((int) old('payment_term_id', 0) === (int) $paymentTerm->id)>{{ $paymentTerm->displayLabel() }}</option>@endforeach</select></div>
                        <div class="col-md-1"><label for="currency" class="form-label">Moeda</label><input type="text" name="currency" id="currency" class="form-control text-uppercase" maxlength="3" value="{{ old('currency', 'EUR') }}" required></div>
                        <div class="col-md-1"><label for="lead_time_days" class="form-label">Lead</label><input type="number" name="lead_time_days" id="lead_time_days" class="form-control" min="0" value="{{ old('lead_time_days') }}"></div>
                        <div class="col-md-2"><label for="quote_status" class="form-label">Estado</label><select name="status" id="quote_status" class="form-select" required>@foreach ($quoteStatuses as $quoteStatusKey => $quoteStatusLabel)<option value="{{ $quoteStatusKey }}" @selected(old('status', 'received') === $quoteStatusKey)>{{ $quoteStatusLabel }}</option>@endforeach</select></div>
                        <div class="col-md-7"><label for="notes" class="form-label">Notas gerais</label><input type="text" name="notes" id="notes" class="form-control" maxlength="5000" value="{{ old('notes') }}"></div>
                        <div class="col-md-3"><label for="quote_pdf" class="form-label">PDF da proposta fornecedor</label><input type="file" name="quote_pdf" id="quote_pdf" class="form-control" accept="application/pdf,.pdf"></div>
                    </div>
                    @include('purchases.requests.partials.quote-lines-form', ['purchaseRequest' => $purchaseRequest, 'quoteItemsByRequestItemId' => collect(), 'useOldValues' => true, 'formPrefix' => 'create-quote'])
                    <div class="d-flex justify-content-end mt-3"><button type="submit" class="btn btn-primary">Guardar proposta</button></div>
                </form>
            </div>
        </section>
    @endif
@endcan

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Historico de emails</h3><span class="badge bg-light text-dark border">{{ $purchaseRequest->emailLogs->count() }}</span></header>
    <div class="card-body">
        @if ($purchaseRequest->emailLogs->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead><tr><th>Data/Hora</th><th>Enviado por</th><th>Destinatario</th><th>Email</th><th>Assunto</th><th>Mensagem</th></tr></thead>
                    <tbody>@foreach ($purchaseRequest->emailLogs as $log)<tr><td>{{ $log->sent_at?->format('d/m/Y H:i:s') ?: '-' }}</td><td>{{ $log->sender?->name ?: '-' }}</td><td>{{ $log->recipient_name ?: '-' }}</td><td>{{ $log->recipient_email }}</td><td>{{ $log->subject ?: '-' }}</td><td>{!! nl2br(e($log->message ?: '-')) !!}</td></tr>@endforeach</tbody>
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
        @include('purchases.requests.partials.send-email-modal', ['purchaseRequest' => $purchaseRequest, 'suppliers' => $suppliers, 'hasEmailLogs' => $hasEmailLogs, 'defaultSupplierId' => $defaultSupplierId, 'defaultRecipientName' => $defaultRecipientName, 'defaultRecipientEmail' => $defaultRecipientEmail, 'defaultCcEmail' => $defaultCcEmail, 'defaultBccEmail' => $defaultBccEmail, 'defaultEmailNotes' => $defaultEmailNotes, 'emailAttachmentMaxMb' => $emailAttachmentMaxMb])
    @endif
@endcan
@endsection

@push('scripts')
<script src="{{ asset('porto/js/pages/purchase-request-show.js') }}"></script>
@endpush
