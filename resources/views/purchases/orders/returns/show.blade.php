@extends('layouts.admin')

@section('title', 'Detalhe da Devolucao')

@section('content')
@php
    $canUpdatePurchase = auth()->user()?->can('purchases.update');
    $isDirect = $order->isDirect() || ! $purchaseRequest;

    $operationalStatusClass = match ((string) $purchaseReturn->status) {
        \App\Models\PurchaseSupplierOrderReturn::STATUS_CLOSED => 'bg-success',
        default => 'bg-warning text-dark',
    };

    $confirmationStatusClass = match ((string) ($purchaseReturn->supplier_confirmation_status ?: \App\Models\PurchaseSupplierOrderReturn::CONFIRMATION_PENDING)) {
        \App\Models\PurchaseSupplierOrderReturn::CONFIRMATION_ACCEPTED => 'bg-success',
        \App\Models\PurchaseSupplierOrderReturn::CONFIRMATION_REJECTED => 'bg-danger',
        default => 'bg-secondary',
    };
    $backRoute = $isDirect
        ? route('purchase-orders.returns.create', $order)
        : route('purchase-requests.supplier-orders.returns.create', [$purchaseRequest, $order]);
    $pdfRoute = $isDirect
        ? route('purchase-orders.returns.pdf', [$order, $purchaseReturn])
        : route('purchase-requests.supplier-orders.returns.pdf', [$purchaseRequest, $order, $purchaseReturn]);
    $closeRoute = $isDirect
        ? route('purchase-orders.returns.close', [$order, $purchaseReturn])
        : route('purchase-requests.supplier-orders.returns.close', [$purchaseRequest, $order, $purchaseReturn]);
    $sendEmailRoute = $isDirect
        ? route('purchase-orders.returns.send-email', [$order, $purchaseReturn])
        : route('purchase-requests.supplier-orders.returns.send-email', [$purchaseRequest, $order, $purchaseReturn]);
    $updateConfirmationRoute = $isDirect
        ? route('purchase-orders.returns.confirmation', [$order, $purchaseReturn])
        : route('purchase-requests.supplier-orders.returns.confirmation', [$purchaseRequest, $order, $purchaseReturn]);
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">{{ $purchaseReturn->return_number }}</h2>
        <div class="text-muted">
            @if ($isDirect)
                Encomenda direta #{{ $order->id }} | {{ $order->supplier?->name ?: '-' }}
            @else
                RFQ {{ $purchaseRequest->code }} | Encomenda #{{ $order->id }} | {{ $order->supplier?->name ?: '-' }}
            @endif
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ $backRoute }}" class="btn btn-outline-secondary">Voltar a devolucoes</a>
        <a href="{{ $pdfRoute }}" target="_blank" class="btn btn-outline-primary">Ver PDF</a>
        @if ($canUpdatePurchase && ! $purchaseReturn->isClosed())
            <form method="POST" action="{{ $closeRoute }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-success">Fechar devolucao</button>
            </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
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
    <div class="col-xl-8">
        <section class="card h-100">
            <header class="card-header">
                <h3 class="card-title mb-0">Resumo da devolucao</h3>
            </header>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4"><strong>Data:</strong> {{ $purchaseReturn->return_date?->format('d/m/Y') ?: '-' }}</div>
                    <div class="col-md-4"><strong>Rececao ref.:</strong> {{ $purchaseReturn->linkedReceipt?->receipt_number ?: '-' }}</div>
                    <div class="col-md-4"><strong>Registado por:</strong> {{ $purchaseReturn->user?->name ?: '-' }}</div>
                    <div class="col-md-4">
                        <strong>Estado operacional:</strong>
                        <span class="badge {{ $operationalStatusClass }}">{{ $purchaseReturn->statusLabel() }}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Confirmacao fornecedor:</strong>
                        <span class="badge {{ $confirmationStatusClass }}">{{ $purchaseReturn->confirmationStatusLabel() }}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Qtd devolvida:</strong>
                        {{ number_format((float) $purchaseReturn->totalReturnedQty(), 3, ',', '.') }}
                    </div>
                    <div class="col-md-6">
                        <strong>Fecho:</strong>
                        @if ($purchaseReturn->isClosed())
                            {{ $purchaseReturn->closed_at?->format('d/m/Y H:i') ?: '-' }} ({{ $purchaseReturn->closedBy?->name ?: '-' }})
                        @else
                            <span class="text-muted">Aberta</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Confirmado internamente:</strong>
                        @if ($purchaseReturn->confirmation_at)
                            {{ $purchaseReturn->confirmation_at->format('d/m/Y H:i') }} ({{ $purchaseReturn->confirmedBy?->name ?: '-' }})
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                    <div class="col-12"><strong>Observacoes:</strong> {{ $purchaseReturn->notes ?: '-' }}</div>
                    <div class="col-12"><strong>Notas de confirmacao:</strong> {{ $purchaseReturn->confirmation_notes ?: '-' }}</div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-xl-4">
        <section class="card h-100">
            <header class="card-header">
                <h3 class="card-title mb-0">Fornecedor</h3>
            </header>
            <div class="card-body">
                <div><strong>{{ $order->supplier?->code ? $order->supplier->code . ' - ' . $order->supplier->name : ($order->supplier?->name ?: '-') }}</strong></div>
                <div><strong>Email:</strong> {{ $order->supplier?->habitual_order_email ?: $order->supplier?->email ?: '-' }}</div>
                <div><strong>Contacto:</strong> {{ $order->supplier?->contact_person ?: '-' }}</div>
                <div><strong>NIF:</strong> {{ $order->supplier?->tax_number ?: '-' }}</div>
            </div>
        </section>
    </div>
</div>

<section class="card mb-3">
    <header class="card-header">
        <h3 class="card-title mb-0">Linhas devolvidas</h3>
    </header>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Artigo</th>
                        <th>Descricao</th>
                        <th class="text-center">Un.</th>
                        <th class="text-end">Qtd devolvida</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseReturn->items as $returnItem)
                        @php
                            $orderItem = $returnItem->orderItem;
                        @endphp
                        <tr>
                            <td>{{ $orderItem?->sort_order ?: '-' }}</td>
                            <td>{{ $orderItem?->item?->code ?: 'MANUAL' }}</td>
                            <td>{{ $orderItem?->description ?: '-' }}</td>
                            <td class="text-center">{{ $orderItem?->item?->unit?->code ?: $orderItem?->unit_snapshot ?: '-' }}</td>
                            <td class="text-end">{{ number_format((float) $returnItem->quantity_returned, 3, ',', '.') }}</td>
                            <td>{{ $returnItem->reason ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted">Sem linhas de devolucao.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<div class="row g-3 mb-3">
    <div class="col-xl-7">
        <section class="card h-100">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Envio por email</h3>
                @if ($purchaseReturn->emailLogs->isNotEmpty())
                    <span class="badge bg-light text-dark border">Ja enviado {{ $purchaseReturn->emailLogs->count() }}x</span>
                @endif
            </header>
            <div class="card-body">
                @if (! $hasMailConfig)
                    <div class="alert alert-warning mb-0">SMTP da empresa incompleto. Configura os dados de email da empresa para ativar o envio.</div>
                @elseif (! $canUpdatePurchase)
                    <div class="text-muted">Sem permissao para enviar emails.</div>
                @else
                    <form method="POST" action="{{ $sendEmailRoute }}">
                        @csrf
                        <input type="hidden" name="is_resend" value="{{ $purchaseReturn->emailLogs->isNotEmpty() ? 1 : 0 }}">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="recipient_name" class="form-label">Nome destinatario</label>
                                <input type="text" id="recipient_name" name="recipient_name" class="form-control @error('recipient_name') is-invalid @enderror" value="{{ $defaultRecipientName }}" maxlength="150">
                                @error('recipient_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="recipient_email" class="form-label">Email destinatario</label>
                                <input type="email" id="recipient_email" name="recipient_email" class="form-control @error('recipient_email') is-invalid @enderror" value="{{ $defaultRecipientEmail }}" maxlength="150" required>
                                @error('recipient_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="cc_email" class="form-label">CC</label>
                                <input type="email" id="cc_email" name="cc_email" class="form-control @error('cc_email') is-invalid @enderror" value="{{ $defaultCcEmail }}" maxlength="150">
                                @error('cc_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="bcc_email" class="form-label">BCC</label>
                                <input type="email" id="bcc_email" name="bcc_email" class="form-control @error('bcc_email') is-invalid @enderror" value="{{ $defaultBccEmail }}" maxlength="150">
                                @error('bcc_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Assunto</label>
                                <input type="text" id="subject" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ $defaultSubject }}" maxlength="190" required>
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="email_notes" class="form-label">Mensagem (opcional)</label>
                                <textarea id="email_notes" name="email_notes" rows="4" class="form-control @error('email_notes') is-invalid @enderror">{{ $defaultEmailNotes }}</textarea>
                                @error('email_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info mb-0">O PDF da devolucao sera anexado automaticamente.</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">{{ $purchaseReturn->emailLogs->isNotEmpty() ? 'Reenviar devolucao' : 'Enviar devolucao' }}</button>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="card h-100">
            <header class="card-header">
                <h3 class="card-title mb-0">Confirmacao do fornecedor</h3>
            </header>
            <div class="card-body">
                @if ($canUpdatePurchase)
                    <form method="POST" action="{{ $updateConfirmationRoute }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label for="supplier_confirmation_status" class="form-label">Estado</label>
                            <select id="supplier_confirmation_status" name="supplier_confirmation_status" class="form-select @error('supplier_confirmation_status') is-invalid @enderror" required>
                                @foreach (\App\Models\PurchaseSupplierOrderReturn::confirmationStatuses() as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}" @selected(old('supplier_confirmation_status', $purchaseReturn->supplier_confirmation_status ?: \App\Models\PurchaseSupplierOrderReturn::CONFIRMATION_PENDING) === $statusKey)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                            @error('supplier_confirmation_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="confirmation_notes" class="form-label">Notas</label>
                            <textarea id="confirmation_notes" name="confirmation_notes" rows="5" class="form-control @error('confirmation_notes') is-invalid @enderror">{{ old('confirmation_notes', $purchaseReturn->confirmation_notes) }}</textarea>
                            @error('confirmation_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-outline-primary">Atualizar confirmacao</button>
                        </div>
                    </form>
                @else
                    <div class="text-muted">Sem permissao para atualizar confirmacao.</div>
                @endif
            </div>
        </section>
    </div>
</div>

<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Historico de envios</h3>
        <span class="badge bg-light text-dark border">{{ $purchaseReturn->emailLogs->count() }}</span>
    </header>
    <div class="card-body">
        @if ($purchaseReturn->emailLogs->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Utilizador</th>
                            <th>Destinatario</th>
                            <th>Email</th>
                            <th>Assunto</th>
                            <th>Tipo</th>
                            <th>Mensagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseReturn->emailLogs as $log)
                            <tr>
                                <td>{{ $log->sent_at?->format('d/m/Y H:i:s') ?: '-' }}</td>
                                <td>{{ $log->sender?->name ?: '-' }}</td>
                                <td>{{ $log->recipient_name ?: '-' }}</td>
                                <td>
                                    {{ $log->recipient_email }}
                                    @if ($log->cc_email)
                                        <div class="small text-muted">CC: {{ $log->cc_email }}</div>
                                    @endif
                                    @if ($log->bcc_email)
                                        <div class="small text-muted">BCC: {{ $log->bcc_email }}</div>
                                    @endif
                                </td>
                                <td>{{ $log->subject }}</td>
                                <td>
                                    @if ($log->is_resend)
                                        <span class="badge bg-secondary">Reenvio</span>
                                    @else
                                        <span class="badge bg-primary">Envio inicial</span>
                                    @endif
                                </td>
                                <td>{!! nl2br(e($log->body_snapshot ?: '-')) !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-muted">Ainda sem envios por email para esta devolucao.</div>
        @endif
    </div>
</section>
@endsection
