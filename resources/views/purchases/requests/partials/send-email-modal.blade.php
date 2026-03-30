<div class="modal fade" id="sendRfqEmailModal" tabindex="-1" aria-labelledby="sendRfqEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendRfqEmailModalLabel">
                    {{ $hasEmailLogs ? 'Reenviar RFQ por email' : 'Enviar RFQ por email' }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <form method="POST" action="{{ route('purchase-requests.send-email', $purchaseRequest) }}" enctype="multipart/form-data">
                @csrf

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="email_supplier_id" class="form-label">Fornecedor (opcional)</label>
                            <select name="supplier_id" id="email_supplier_id" class="form-select">
                                <option value="">Selecionar...</option>
                                @foreach ($suppliers as $supplier)
                                    <option
                                        value="{{ $supplier->id }}"
                                        data-name="{{ $supplier->contact_person ?: $supplier->name }}"
                                        data-email="{{ $supplier->habitual_order_email ?: $supplier->email }}"
                                        @selected($defaultSupplierId === (int) $supplier->id)
                                    >
                                        {{ $supplier->code }} - {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="recipient_name" class="form-label">Nome do destinatario</label>
                            <input type="text" name="recipient_name" id="recipient_name" class="form-control @error('recipient_name') is-invalid @enderror" value="{{ $defaultRecipientName }}" maxlength="150">
                            @error('recipient_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="recipient_email" class="form-label">Email do destinatario</label>
                            <input type="email" name="recipient_email" id="recipient_email" class="form-control @error('recipient_email') is-invalid @enderror" value="{{ $defaultRecipientEmail }}" maxlength="150" required>
                            @error('recipient_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="cc_email" class="form-label">CC</label>
                            <input type="email" name="cc_email" id="cc_email" class="form-control @error('cc_email') is-invalid @enderror" value="{{ $defaultCcEmail }}" maxlength="150">
                            @error('cc_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="bcc_email" class="form-label">BCC</label>
                            <input type="email" name="bcc_email" id="bcc_email" class="form-control @error('bcc_email') is-invalid @enderror" value="{{ $defaultBccEmail }}" maxlength="150">
                            @error('bcc_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="email_notes" class="form-label">Observacoes no email</label>
                            <textarea name="email_notes" id="email_notes" rows="5" class="form-control @error('email_notes') is-invalid @enderror" placeholder="Mensagem opcional para o fornecedor">{{ $defaultEmailNotes }}</textarea>
                            @error('email_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="email_attachment" class="form-label">Anexo adicional (opcional)</label>
                            <input type="file" name="email_attachment" id="email_attachment" class="form-control @error('email_attachment') is-invalid @enderror">
                            @error('email_attachment')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Tamanho maximo: {{ $emailAttachmentMaxMb }} MB.</small>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                O RFQ sera anexado automaticamente em PDF tecnico (sem precos).
                                @if ($hasEmailLogs)
                                    Este envio ficara registado como novo reenvio.
                                @else
                                    Apos o primeiro envio, o estado do RFQ passa para <strong>Enviado</strong>.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">{{ $hasEmailLogs ? 'Confirmar reenvio' : 'Confirmar envio' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
