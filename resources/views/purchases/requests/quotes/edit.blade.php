@extends('layouts.admin')

@section('title', 'Editar Cotacao Recebida')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Editar cotacao recebida</h2>
        <div class="small text-muted">
            RFQ {{ $purchaseRequest->code }} | {{ $quote->supplier_name_snapshot }}
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('purchase-requests.comparison', $purchaseRequest) }}" class="btn btn-light border">Voltar a comparacao</a>
        <a href="{{ route('purchase-requests.show', $purchaseRequest) }}" class="btn btn-light border">Voltar ao RFQ</a>
    </div>
</div>

@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<section class="card mb-3">
    <header class="card-header">
        <h3 class="card-title mb-0">Dados da proposta</h3>
    </header>
    <div class="card-body">
        <form method="POST" action="{{ route('purchase-requests.quotes.update', [$purchaseRequest, $quote]) }}" class="quote-form-wrapper" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="return_to" value="comparison">

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Fornecedor</label>
                    <select name="supplier_id" class="form-select supplier-selector" required>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $quote->supplier_id) === (int) $supplier->id)>
                                {{ $supplier->code }} - {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ref. proposta fornecedor</label>
                    <input type="text" name="supplier_quote_reference" class="form-control" maxlength="120" value="{{ old('supplier_quote_reference', $quote->supplier_quote_reference) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Condicao pagamento</label>
                    <select name="payment_term_id" class="form-select">
                        <option value="">Selecionar...</option>
                        @foreach ($paymentTerms as $paymentTerm)
                            <option value="{{ $paymentTerm->id }}" @selected((int) old('payment_term_id', $quote->payment_term_id) === (int) $paymentTerm->id)>
                                {{ $paymentTerm->displayLabel() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Moeda</label>
                    <input type="text" name="currency" class="form-control text-uppercase" maxlength="3" value="{{ old('currency', $quote->currency) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Lead time (dias)</label>
                    <input type="number" name="lead_time_days" class="form-control" min="0" value="{{ old('lead_time_days', $quote->lead_time_days) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select" required>
                        @foreach ($quoteStatuses as $quoteStatusKey => $quoteStatusLabel)
                            <option value="{{ $quoteStatusKey }}" @selected(old('status', $quote->status) === $quoteStatusKey)>
                                {{ $quoteStatusLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Notas gerais</label>
                    <input type="text" name="notes" class="form-control" maxlength="5000" value="{{ old('notes', $quote->notes) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">PDF da proposta</label>
                    <input type="file" name="quote_pdf" class="form-control" accept="application/pdf,.pdf">
                    @if ($quote->quote_pdf_path)
                        <div class="small mt-1">
                            PDF atual:
                            <a href="{{ route('purchase-requests.quotes.pdf', [$purchaseRequest, $quote]) }}" target="_blank">ver ficheiro</a>
                        </div>
                    @endif
                </div>
            </div>

            @include('purchases.requests.partials.quote-lines-form', [
                'purchaseRequest' => $purchaseRequest,
                'quoteItemsByRequestItemId' => $quote->items->keyBy('purchase_request_item_id'),
                'useOldValues' => true,
                'formPrefix' => 'edit-quote-' . $quote->id,
            ])

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('purchase-requests.comparison', $purchaseRequest) }}" class="btn btn-light border">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar alteracoes</button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const supplierItemReferenceMap = @json($supplierItemReferenceMap ?? []);
    const form = document.querySelector('.quote-form-wrapper');

    if (!form) {
        return;
    }

    const parseNumber = (value) => {
        const normalized = Number(String(value || '').replace(',', '.'));

        return Number.isFinite(normalized) ? normalized : 0;
    };

    const calcLine = (row) => {
        const qty = row.querySelector('.quoted-qty-input');
        const unitPrice = row.querySelector('.unit-price-input');
        const discount = row.querySelector('.discount-percent-input');
        const total = row.querySelector('.line-total-display');

        if (!qty || !unitPrice || !discount || !total) {
            return;
        }

        if ((unitPrice.value || '').trim() === '') {
            total.value = '';
            return;
        }

        const lineTotal = parseNumber(qty.value)
            * parseNumber(unitPrice.value)
            * (1 - (Math.min(Math.max(parseNumber(discount.value), 0), 100) / 100));

        total.value = lineTotal.toFixed(2);
    };

    const applySupplierRefs = () => {
        const supplierSelect = form.querySelector('.supplier-selector');

        if (!supplierSelect || !supplierSelect.value) {
            return;
        }

        form.querySelectorAll('.quote-line-row').forEach((row) => {
            const itemId = row.getAttribute('data-item-id');
            const input = row.querySelector('.supplier-item-reference-input');

            if (!itemId || !input) {
                return;
            }

            const key = supplierSelect.value + ':' + itemId;
            if (supplierItemReferenceMap[key] && input.value.trim() === '') {
                input.value = supplierItemReferenceMap[key];
            }
        });
    };

    form.querySelectorAll('.quote-line-row').forEach((row) => {
        ['.quoted-qty-input', '.unit-price-input', '.discount-percent-input'].forEach((selector) => {
            const input = row.querySelector(selector);
            if (input) {
                input.addEventListener('input', () => calcLine(row));
            }
        });

        calcLine(row);
    });

    const supplierSelect = form.querySelector('.supplier-selector');
    if (supplierSelect) {
        supplierSelect.addEventListener('change', applySupplierRefs);
    }

    applySupplierRefs();
});
</script>
@endpush
