<div class="modal fade" id="exportRfqPdfModal" tabindex="-1" aria-labelledby="exportRfqPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportRfqPdfModalLabel">Gerar PDF do RFQ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <form method="GET" action="{{ route('purchase-requests.pdf', $purchaseRequest) }}" target="_blank">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="pdf_supplier_id" class="form-label">Fornecedor (opcional)</label>
                        <select name="supplier_id" id="pdf_supplier_id" class="form-select">
                            <option value="">Sem fornecedor definido</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->code }} - {{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-info mb-0">
                        O PDF segue o estilo tecnico dos orcamentos, adaptado ao RFQ e sem qualquer coluna de preco.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Gerar PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
