@php
    $currentItems = collect(old('items', []));

    if ($currentItems->isEmpty()) {
        $currentItems = collect([[
            'item_id' => null,
            'description_snapshot' => '',
            'unit_snapshot' => '',
            'quantity' => '',
            'unit_price' => '',
            'vat_rate_id' => '',
            'notes' => '',
        ]]);
    }

    $initialItemOptions = collect($itemInitialOptions ?? [])->keyBy('id');
    $vatRatesForJs = $taxRates->map(function ($taxRate) {
        return [
            'id' => (int) $taxRate->id,
            'label' => $taxRate->name . ' (' . number_format((float) $taxRate->percent, 2, ',', '.') . '%)',
            'percent' => round((float) $taxRate->percent, 3),
        ];
    })->values()->all();
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('porto/vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('porto/vendor/select2-bootstrap-theme/select2-bootstrap.min.css') }}">
    <style>
        .purchase-direct-item-select-wrap .select2-container {
            width: 100% !important;
        }

        .purchase-direct-item-select-wrap .select2-selection--single {
            min-height: 38px;
        }

        .purchase-direct-item-option {
            line-height: 1.3;
        }

        .purchase-direct-item-option small {
            color: #6c757d;
            display: block;
        }
    </style>
@endpush

<div class="row g-3">
    <div class="col-md-4">
        <label for="supplier_id" class="form-label">Fornecedor</label>
        <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
            <option value="">Selecionar...</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $directPurchase->supplier_id ?? 0) === (int) $supplier->id)>
                    {{ $supplier->code ? $supplier->code . ' - ' . $supplier->name : $supplier->name }}
                </option>
            @endforeach
        </select>
        @error('supplier_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="purchase_date" class="form-label">Data</label>
        <input
            type="date"
            name="purchase_date"
            id="purchase_date"
            class="form-control @error('purchase_date') is-invalid @enderror"
            value="{{ old('purchase_date', optional($directPurchase->purchase_date)->toDateString() ?: now()->toDateString()) }}"
            required
        >
        @error('purchase_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="due_date" class="form-label">Vencimento</label>
        <input
            type="date"
            name="due_date"
            id="due_date"
            class="form-control @error('due_date') is-invalid @enderror"
            value="{{ old('due_date', optional($directPurchase->due_date)->toDateString() ?: now()->toDateString()) }}"
            required
        >
        @error('due_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="payment_method" class="form-label">Metodo pagamento</label>
        <select
            name="payment_method"
            id="payment_method"
            class="form-select @error('payment_method') is-invalid @enderror"
            required
        >
            <option value="">Selecionar...</option>
            @foreach ($paymentMethods as $paymentMethodKey => $paymentMethodLabel)
                <option value="{{ $paymentMethodKey }}" @selected(old('payment_method', $directPurchase->payment_method ?? '') === $paymentMethodKey)>
                    {{ $paymentMethodLabel }}
                </option>
            @endforeach
        </select>
        @error('payment_method')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="external_reference" class="form-label">Doc. externo</label>
        <input
            type="text"
            name="external_reference"
            id="external_reference"
            class="form-control @error('external_reference') is-invalid @enderror"
            value="{{ old('external_reference', $directPurchase->external_reference ?? '') }}"
            maxlength="120"
            placeholder="Fatura/guia/referencia"
        >
        @error('external_reference')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-1">
        <label for="currency" class="form-label">Moeda</label>
        <input
            type="text"
            name="currency"
            id="currency"
            class="form-control text-uppercase @error('currency') is-invalid @enderror"
            maxlength="3"
            value="{{ old('currency', $directPurchase->currency ?: 'EUR') }}"
            required
        >
        @error('currency')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label class="form-label">Resumo</label>
        <div class="border rounded px-2 py-2 bg-light">
            <div class="d-flex justify-content-between small">
                <span>Subtotal:</span>
                <span id="purchase-subtotal-label">0,00</span>
            </div>
            <div class="d-flex justify-content-between small">
                <span>IVA:</span>
                <span id="purchase-vat-label">0,00</span>
            </div>
            <div class="d-flex justify-content-between fw-semibold">
                <span>Total:</span>
                <span id="purchase-total-label">0,00</span>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <label for="invoice_pdf" class="form-label">PDF da fatura fornecedor</label>
        <input
            type="file"
            name="invoice_pdf"
            id="invoice_pdf"
            class="form-control @error('invoice_pdf') is-invalid @enderror"
            accept="application/pdf,.pdf"
        >
        <div class="form-text">Opcional. Apenas PDF ate 10MB.</div>
        @error('invoice_pdf')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notas</label>
        <textarea
            name="notes"
            id="notes"
            rows="2"
            class="form-control @error('notes') is-invalid @enderror"
        >{{ old('notes', $directPurchase->notes ?? '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Linhas da compra</h5>
            <div class="d-flex gap-2">
                @can('items.create')
                    <a href="{{ route('items.create') }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
                        Criar artigo
                    </a>
                @endcan
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-purchase-line">Adicionar linha</button>
            </div>
        </div>

        @error('items')
            <div class="alert alert-danger py-2">{{ $message }}</div>
        @enderror

        <div class="table-responsive">
            <table
                class="table table-bordered align-middle"
                id="direct-purchase-items-table"
                data-item-search-url="{{ route('api.items.search') }}"
                data-vat-rates="{{ e(json_encode($vatRatesForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"
            >
                <thead>
                    <tr>
                        <th style="width: 20%">Artigo</th>
                        <th style="width: 20%">Descricao</th>
                        <th style="width: 7%">Qtd</th>
                        <th style="width: 7%">Un.</th>
                        <th style="width: 8%">Preco un.</th>
                        <th style="width: 9%">IVA</th>
                        <th style="width: 8%">Subtotal</th>
                        <th style="width: 8%">IVA valor</th>
                        <th style="width: 8%">Total</th>
                        <th>Notas</th>
                        <th style="width: 5%" class="text-center">Acao</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($currentItems as $index => $line)
                        @php
                            $selectedItemId = (int) ($line['item_id'] ?? 0);
                            $selectedItem = $selectedItemId > 0 ? $initialItemOptions->get($selectedItemId) : null;
                            $selectedTaxRateId = (int) ($line['vat_rate_id'] ?? 0);
                        @endphp
                        <tr>
                            <td>
                                <div class="purchase-direct-item-select-wrap">
                                    <select
                                        name="items[{{ $index }}][item_id]"
                                        class="form-select purchase-direct-item-select @error('items.' . $index . '.item_id') is-invalid @enderror"
                                        data-placeholder="Pesquisar artigo por codigo ou nome..."
                                    >
                                        <option value=""></option>
                                        @if ($selectedItemId > 0)
                                            <option
                                                value="{{ $selectedItemId }}"
                                                data-name="{{ $selectedItem['name'] ?? '' }}"
                                                data-description="{{ $selectedItem['description'] ?? ($selectedItem['name'] ?? '') }}"
                                                data-unit="{{ $selectedItem['unit'] ?? '' }}"
                                                data-tax-rate-id="{{ $selectedItem['tax_rate_id'] ?? '' }}"
                                                selected
                                            >
                                                {{ $selectedItem['label'] ?? ('Artigo #' . $selectedItemId) }}
                                            </option>
                                        @endif
                                    </select>
                                </div>
                                @error('items.' . $index . '.item_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="items[{{ $index }}][description_snapshot]"
                                    class="form-control purchase-desc @error('items.' . $index . '.description_snapshot') is-invalid @enderror"
                                    value="{{ $line['description_snapshot'] ?? '' }}"
                                    maxlength="255"
                                    required
                                >
                                @error('items.' . $index . '.description_snapshot')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="items[{{ $index }}][quantity]"
                                    class="form-control text-end purchase-qty @error('items.' . $index . '.quantity') is-invalid @enderror"
                                    value="{{ $line['quantity'] ?? '' }}"
                                    min="0.001"
                                    step="0.001"
                                    required
                                >
                                @error('items.' . $index . '.quantity')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="items[{{ $index }}][unit_snapshot]"
                                    class="form-control purchase-unit @error('items.' . $index . '.unit_snapshot') is-invalid @enderror"
                                    value="{{ $line['unit_snapshot'] ?? '' }}"
                                    maxlength="100"
                                >
                                @error('items.' . $index . '.unit_snapshot')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="items[{{ $index }}][unit_price]"
                                    class="form-control text-end purchase-unit-price @error('items.' . $index . '.unit_price') is-invalid @enderror"
                                    value="{{ $line['unit_price'] ?? '' }}"
                                    min="0"
                                    step="0.0001"
                                    required
                                >
                                @error('items.' . $index . '.unit_price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <select
                                    name="items[{{ $index }}][vat_rate_id]"
                                    class="form-select purchase-vat-rate @error('items.' . $index . '.vat_rate_id') is-invalid @enderror"
                                    required
                                >
                                    <option value="">Selecionar...</option>
                                    @foreach ($taxRates as $taxRate)
                                        <option
                                            value="{{ $taxRate->id }}"
                                            data-percent="{{ number_format((float) $taxRate->percent, 3, '.', '') }}"
                                            @selected($selectedTaxRateId === (int) $taxRate->id)
                                        >
                                            {{ $taxRate->name }} ({{ number_format((float) $taxRate->percent, 2, ',', '.') }}%)
                                        </option>
                                    @endforeach
                                </select>
                                @error('items.' . $index . '.vat_rate_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td><input type="text" class="form-control text-end purchase-line-subtotal" readonly></td>
                            <td><input type="text" class="form-control text-end purchase-line-vat" readonly></td>
                            <td><input type="text" class="form-control text-end purchase-line-total" readonly></td>
                            <td>
                                <input
                                    type="text"
                                    name="items[{{ $index }}][notes]"
                                    class="form-control @error('items.' . $index . '.notes') is-invalid @enderror"
                                    value="{{ $line['notes'] ?? '' }}"
                                    maxlength="2000"
                                >
                                @error('items.' . $index . '.notes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger purchase-remove-line">X</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('porto/vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('porto/vendor/select2/js/i18n/pt.js') }}"></script>
    <script src="{{ asset('porto/js/pages/purchase-direct-form.js') }}"></script>
@endpush
