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
            <table class="table table-bordered align-middle" id="direct-purchase-items-table">
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableBody = document.querySelector('#direct-purchase-items-table tbody');
            const addButton = document.getElementById('add-purchase-line');
            let rowIndex = tableBody ? tableBody.querySelectorAll('tr').length : 0;
            const itemSearchUrl = @json(route('api.items.search'));
            const vatRates = @json($taxRates->map(fn ($taxRate) => [
                'id' => (int) $taxRate->id,
                'label' => $taxRate->name . ' (' . number_format((float) $taxRate->percent, 2, ',', '.') . '%)',
                'percent' => round((float) $taxRate->percent, 3),
            ])->values()->all());

            const subtotalLabel = document.getElementById('purchase-subtotal-label');
            const vatLabel = document.getElementById('purchase-vat-label');
            const totalLabel = document.getElementById('purchase-total-label');

            function escapeHtml(value) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                };

                return String(value || '').replace(/[&<>"']/g, function (s) {
                    return map[s];
                });
            }

            function parseNumber(value) {
                const n = Number(String(value || '').replace(',', '.'));
                return Number.isFinite(n) ? n : 0;
            }

            function formatNumber(value, decimals) {
                return Number(value || 0).toLocaleString('pt-PT', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            }

            function vatPercentFromRow(row) {
                const vatSelect = row.querySelector('.purchase-vat-rate');
                if (!vatSelect) {
                    return 0;
                }

                const selectedOption = vatSelect.options[vatSelect.selectedIndex];
                if (!selectedOption) {
                    return 0;
                }

                return parseNumber(selectedOption.getAttribute('data-percent'));
            }

            function recalculateLine(row) {
                const qty = parseNumber(row.querySelector('.purchase-qty')?.value);
                const unitPrice = parseNumber(row.querySelector('.purchase-unit-price')?.value);
                const vatPercent = vatPercentFromRow(row);

                const subtotal = qty * unitPrice;
                const vatAmount = subtotal * (vatPercent / 100);
                const total = subtotal + vatAmount;

                const subtotalField = row.querySelector('.purchase-line-subtotal');
                const vatField = row.querySelector('.purchase-line-vat');
                const totalField = row.querySelector('.purchase-line-total');

                if (subtotalField) {
                    subtotalField.value = formatNumber(subtotal, 2);
                }

                if (vatField) {
                    vatField.value = formatNumber(vatAmount, 2);
                }

                if (totalField) {
                    totalField.value = formatNumber(total, 2);
                }
            }

            function recalculateTotals() {
                let subtotal = 0;
                let vatAmount = 0;
                let total = 0;

                if (!tableBody) {
                    return;
                }

                tableBody.querySelectorAll('tr').forEach(function (row) {
                    const qty = parseNumber(row.querySelector('.purchase-qty')?.value);
                    const unitPrice = parseNumber(row.querySelector('.purchase-unit-price')?.value);
                    const vatPercent = vatPercentFromRow(row);

                    const rowSubtotal = qty * unitPrice;
                    const rowVat = rowSubtotal * (vatPercent / 100);
                    const rowTotal = rowSubtotal + rowVat;

                    subtotal += rowSubtotal;
                    vatAmount += rowVat;
                    total += rowTotal;
                });

                if (subtotalLabel) {
                    subtotalLabel.textContent = formatNumber(subtotal, 2);
                }

                if (vatLabel) {
                    vatLabel.textContent = formatNumber(vatAmount, 2);
                }

                if (totalLabel) {
                    totalLabel.textContent = formatNumber(total, 2);
                }
            }

            function applyItemDataToRow(row, payload) {
                if (!payload || !payload.id) {
                    return;
                }

                const descField = row.querySelector('.purchase-desc');
                const unitField = row.querySelector('.purchase-unit');
                const vatSelect = row.querySelector('.purchase-vat-rate');

                if (descField && !descField.value.trim()) {
                    descField.value = payload.description || payload.name || '';
                }

                if (unitField && !unitField.value.trim()) {
                    unitField.value = payload.unit_code || '';
                }

                if (vatSelect && payload.tax_rate_id) {
                    const value = String(payload.tax_rate_id);
                    const hasOption = Array.from(vatSelect.options).some(function (option) {
                        return option.value === value;
                    });

                    if (hasOption) {
                        vatSelect.value = value;
                    }
                }
            }

            function initItemSelect(selectElement) {
                if (!window.jQuery || typeof jQuery.fn.select2 !== 'function') {
                    return;
                }

                const $select = jQuery(selectElement);
                if ($select.data('purchaseDirectSelect2Ready')) {
                    return;
                }

                $select.select2({
                    theme: 'bootstrap',
                    width: '100%',
                    allowClear: true,
                    placeholder: $select.data('placeholder') || 'Pesquisar artigo por codigo ou nome...',
                    minimumInputLength: 2,
                    ajax: {
                        url: itemSearchUrl,
                        dataType: 'json',
                        delay: 300,
                        cache: true,
                        data: function (params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: (data.results || []).map(function (item) {
                                    return {
                                        id: item.id,
                                        text: item.text,
                                        code: item.code,
                                        name: item.name,
                                        description: item.description,
                                        unit_code: item.unit_code,
                                        tax_rate_id: item.tax_rate_id
                                    };
                                }),
                                pagination: data.pagination || { more: false }
                            };
                        }
                    },
                    templateResult: function (item) {
                        if (item.loading) {
                            return item.text;
                        }

                        const code = escapeHtml(item.code || '');
                        const name = escapeHtml(item.name || item.text || '');
                        const unit = escapeHtml(item.unit_code || '-');

                        return '<div class="purchase-direct-item-option"><strong>' + code + '</strong> - ' + name + '<small>Unidade: ' + unit + '</small></div>';
                    },
                    templateSelection: function (item) {
                        if (!item.id) {
                            return item.text || '';
                        }

                        const code = item.code || (item.text ? item.text.split(' - ')[0] : '');
                        const name = item.name || item.text || '';

                        if (!code) {
                            return name;
                        }

                        return code + ' - ' + name.replace(code + ' - ', '');
                    },
                    escapeMarkup: function (markup) {
                        return markup;
                    },
                    language: {
                        inputTooShort: function () {
                            return 'Escreve pelo menos 2 caracteres';
                        },
                        searching: function () {
                            return 'A pesquisar...';
                        },
                        noResults: function () {
                            return 'Sem resultados';
                        },
                        loadingMore: function () {
                            return 'A carregar mais resultados...';
                        }
                    }
                });

                $select.on('select2:select', function (event) {
                    const row = event.target.closest('tr');
                    applyItemDataToRow(row, event.params ? event.params.data : null);
                    recalculateLine(row);
                    recalculateTotals();
                });

                $select.data('purchaseDirectSelect2Ready', true);
            }

            function buildVatSelectOptions() {
                let html = '<option value="">Selecionar...</option>';
                vatRates.forEach(function (rate) {
                    html += '<option value="' + rate.id + '" data-percent="' + Number(rate.percent || 0).toFixed(3) + '">'
                        + escapeHtml(rate.label) + '</option>';
                });
                return html;
            }

            function bindRow(row) {
                const removeButton = row.querySelector('.purchase-remove-line');
                if (removeButton) {
                    removeButton.addEventListener('click', function () {
                        if (tableBody.querySelectorAll('tr').length <= 1) {
                            return;
                        }

                        row.remove();
                        recalculateTotals();
                    });
                }

                row.querySelectorAll('.purchase-qty, .purchase-unit-price, .purchase-vat-rate').forEach(function (input) {
                    input.addEventListener('input', function () {
                        recalculateLine(row);
                        recalculateTotals();
                    });

                    input.addEventListener('change', function () {
                        recalculateLine(row);
                        recalculateTotals();
                    });
                });

                recalculateLine(row);
                recalculateTotals();

                const itemSelect = row.querySelector('.purchase-direct-item-select');
                if (itemSelect) {
                    initItemSelect(itemSelect);
                }
            }

            if (tableBody) {
                tableBody.querySelectorAll('tr').forEach(function (row) {
                    bindRow(row);
                });
            }

            if (addButton && tableBody) {
                addButton.addEventListener('click', function () {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <div class="purchase-direct-item-select-wrap">
                                <select name="items[${rowIndex}][item_id]" class="form-select purchase-direct-item-select" data-placeholder="Pesquisar artigo por codigo ou nome...">
                                    <option value=""></option>
                                </select>
                            </div>
                        </td>
                        <td>
                            <input type="text" name="items[${rowIndex}][description_snapshot]" class="form-control purchase-desc" maxlength="255" required>
                        </td>
                        <td>
                            <input type="number" name="items[${rowIndex}][quantity]" class="form-control text-end purchase-qty" min="0.001" step="0.001" required>
                        </td>
                        <td>
                            <input type="text" name="items[${rowIndex}][unit_snapshot]" class="form-control purchase-unit" maxlength="100">
                        </td>
                        <td>
                            <input type="number" name="items[${rowIndex}][unit_price]" class="form-control text-end purchase-unit-price" min="0" step="0.0001" required>
                        </td>
                        <td>
                            <select name="items[${rowIndex}][vat_rate_id]" class="form-select purchase-vat-rate" required>
                                ${buildVatSelectOptions()}
                            </select>
                        </td>
                        <td><input type="text" class="form-control text-end purchase-line-subtotal" readonly></td>
                        <td><input type="text" class="form-control text-end purchase-line-vat" readonly></td>
                        <td><input type="text" class="form-control text-end purchase-line-total" readonly></td>
                        <td>
                            <input type="text" name="items[${rowIndex}][notes]" class="form-control" maxlength="2000">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger purchase-remove-line">X</button>
                        </td>
                    `;

                    tableBody.appendChild(tr);
                    bindRow(tr);
                    rowIndex++;
                });
            }
        });
    </script>
@endpush

