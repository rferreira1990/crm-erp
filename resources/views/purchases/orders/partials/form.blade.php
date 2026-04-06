@php
    $currentItems = collect(old('items', isset($order) && $order->relationLoaded('items')
        ? $order->items->map(fn ($line) => [
            'item_id' => $line->item_id,
            'description' => $line->description,
            'qty' => $line->qty,
            'unit_snapshot' => $line->unit_snapshot,
            'unit_price' => $line->unit_price,
            'discount_percent' => $line->discount_percent,
            'notes' => $line->notes,
        ])->toArray()
        : []));

    if ($currentItems->isEmpty()) {
        $currentItems = collect([[
            'item_id' => null,
            'description' => '',
            'qty' => '',
            'unit_snapshot' => '',
            'unit_price' => '',
            'discount_percent' => '',
            'notes' => '',
        ]]);
    }

    $initialItemOptions = collect($orderItemInitialOptions ?? [])->keyBy('id');
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('porto/vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('porto/vendor/select2-bootstrap-theme/select2-bootstrap.min.css') }}">
    <style>
        .purchase-order-item-select-wrap .select2-container {
            width: 100% !important;
        }

        .purchase-order-item-select-wrap .select2-selection--single {
            min-height: 38px;
        }

        .purchase-order-item-option {
            line-height: 1.3;
        }

        .purchase-order-item-option small {
            color: #6c757d;
            display: block;
        }
    </style>
@endpush

<div class="row g-3">
    <div class="col-md-5">
        <label for="supplier_id" class="form-label">Fornecedor</label>
        <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
            <option value="">Selecionar...</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $order->supplier_id ?? 0) === (int) $supplier->id)>
                    {{ $supplier->code ? $supplier->code . ' - ' . $supplier->name : $supplier->name }}
                </option>
            @endforeach
        </select>
        @error('supplier_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="prepared_at" class="form-label">Data</label>
        <input
            type="date"
            name="prepared_at"
            id="prepared_at"
            class="form-control @error('prepared_at') is-invalid @enderror"
            value="{{ old('prepared_at', optional($order->prepared_at)->toDateString() ?: now()->toDateString()) }}"
            required
        >
        @error('prepared_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="payment_term_id" class="form-label">Cond. pagamento</label>
        <select name="payment_term_id" id="payment_term_id" class="form-select @error('payment_term_id') is-invalid @enderror">
            <option value="">-</option>
            @foreach ($paymentTerms as $paymentTerm)
                <option value="{{ $paymentTerm->id }}" @selected((int) old('payment_term_id', $order->payment_term_id ?? 0) === (int) $paymentTerm->id)>
                    {{ $paymentTerm->displayLabel() }}
                </option>
            @endforeach
        </select>
        @error('payment_term_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="currency" class="form-label">Moeda</label>
        <input
            type="text"
            name="currency"
            id="currency"
            class="form-control text-uppercase @error('currency') is-invalid @enderror"
            maxlength="3"
            value="{{ old('currency', $order->currency ?: 'EUR') }}"
            required
        >
        @error('currency')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notas</label>
        <textarea
            name="notes"
            id="notes"
            rows="3"
            class="form-control @error('notes') is-invalid @enderror"
        >{{ old('notes', $order->notes ?? '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Linhas da encomenda</h5>
            <div class="d-flex gap-2">
                @can('items.create')
                    <a href="{{ route('items.create') }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
                        Criar artigo
                    </a>
                @endcan
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-order-line">Adicionar linha</button>
            </div>
        </div>

        @error('items')
            <div class="alert alert-danger py-2">{{ $message }}</div>
        @enderror

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="order-items-table">
                <thead>
                    <tr>
                        <th style="width: 24%">Artigo</th>
                        <th style="width: 22%">Descricao</th>
                        <th style="width: 8%">Qtd</th>
                        <th style="width: 8%">Un.</th>
                        <th style="width: 10%">Preco un.</th>
                        <th style="width: 8%">Desc %</th>
                        <th style="width: 10%">Total</th>
                        <th>Notas</th>
                        <th style="width: 6%" class="text-center">Acao</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($currentItems as $index => $line)
                        @php
                            $selectedItemId = (int) ($line['item_id'] ?? 0);
                            $selectedItem = $selectedItemId > 0 ? $initialItemOptions->get($selectedItemId) : null;
                        @endphp
                        <tr>
                            <td>
                                <div class="purchase-order-item-select-wrap">
                                    <select
                                        name="items[{{ $index }}][item_id]"
                                        class="form-select purchase-order-item-select @error('items.' . $index . '.item_id') is-invalid @enderror"
                                        data-placeholder="Pesquisar artigo por codigo ou nome..."
                                    >
                                        <option value=""></option>
                                        @if ($selectedItemId > 0)
                                            <option
                                                value="{{ $selectedItemId }}"
                                                data-name="{{ $selectedItem['name'] ?? '' }}"
                                                data-description="{{ $selectedItem['description'] ?? ($selectedItem['name'] ?? '') }}"
                                                data-unit="{{ $selectedItem['unit'] ?? '' }}"
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
                                    name="items[{{ $index }}][description]"
                                    class="form-control order-desc @error('items.' . $index . '.description') is-invalid @enderror"
                                    value="{{ $line['description'] ?? '' }}"
                                    maxlength="255"
                                    required
                                >
                                @error('items.' . $index . '.description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="items[{{ $index }}][qty]"
                                    class="form-control text-end order-qty @error('items.' . $index . '.qty') is-invalid @enderror"
                                    value="{{ $line['qty'] ?? '' }}"
                                    min="0.001"
                                    step="0.001"
                                    required
                                >
                                @error('items.' . $index . '.qty')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="items[{{ $index }}][unit_snapshot]"
                                    class="form-control order-unit @error('items.' . $index . '.unit_snapshot') is-invalid @enderror"
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
                                    class="form-control text-end order-unit-price @error('items.' . $index . '.unit_price') is-invalid @enderror"
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
                                <input
                                    type="number"
                                    name="items[{{ $index }}][discount_percent]"
                                    class="form-control text-end order-discount @error('items.' . $index . '.discount_percent') is-invalid @enderror"
                                    value="{{ $line['discount_percent'] ?? '' }}"
                                    min="0"
                                    max="100"
                                    step="0.001"
                                >
                                @error('items.' . $index . '.discount_percent')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input type="text" class="form-control text-end order-line-total" readonly>
                            </td>
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
                                <button type="button" class="btn btn-sm btn-outline-danger order-remove-line">X</button>
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
            const tableBody = document.querySelector('#order-items-table tbody');
            const addButton = document.getElementById('add-order-line');
            let rowIndex = tableBody ? tableBody.querySelectorAll('tr').length : 0;
            const itemSearchUrl = @json(route('api.items.search'));

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

            function recalculateLine(row) {
                const qtyField = row.querySelector('.order-qty');
                const unitPriceField = row.querySelector('.order-unit-price');
                const discountField = row.querySelector('.order-discount');
                const totalField = row.querySelector('.order-line-total');

                if (!qtyField || !unitPriceField || !discountField || !totalField) {
                    return;
                }

                const qty = parseNumber(qtyField.value);
                const unitPrice = parseNumber(unitPriceField.value);
                const discount = Math.min(100, Math.max(0, parseNumber(discountField.value)));
                const total = qty * unitPrice * (1 - (discount / 100));

                totalField.value = formatNumber(total, 2);
            }

            function applyItemDataToRow(row, payload) {
                if (!payload || !payload.id) {
                    return;
                }

                const descField = row.querySelector('.order-desc');
                const unitField = row.querySelector('.order-unit');

                if (descField && !descField.value.trim()) {
                    descField.value = payload.description || payload.name || '';
                }

                if (unitField && !unitField.value.trim()) {
                    unitField.value = payload.unit_code || '';
                }
            }

            function initItemSelect(selectElement) {
                if (!window.jQuery || typeof jQuery.fn.select2 !== 'function') {
                    return;
                }

                const $select = jQuery(selectElement);
                if ($select.data('purchaseOrderSelect2Ready')) {
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
                                        unit_name: item.unit_name
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

                        return '<div class="purchase-order-item-option"><strong>' + code + '</strong> - ' + name + '<small>Unidade: ' + unit + '</small></div>';
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
                });

                $select.data('purchaseOrderSelect2Ready', true);
            }

            function bindRow(row) {
                const removeButton = row.querySelector('.order-remove-line');
                if (removeButton) {
                    removeButton.addEventListener('click', function () {
                        if (tableBody.querySelectorAll('tr').length <= 1) {
                            return;
                        }

                        row.remove();
                    });
                }

                row.querySelectorAll('.order-qty, .order-unit-price, .order-discount').forEach(function (input) {
                    input.addEventListener('input', function () {
                        recalculateLine(row);
                    });
                });

                recalculateLine(row);

                const itemSelect = row.querySelector('.purchase-order-item-select');
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
                            <div class="purchase-order-item-select-wrap">
                                <select name="items[${rowIndex}][item_id]" class="form-select purchase-order-item-select" data-placeholder="Pesquisar artigo por codigo ou nome...">
                                    <option value=""></option>
                                </select>
                            </div>
                        </td>
                        <td>
                            <input type="text" name="items[${rowIndex}][description]" class="form-control order-desc" maxlength="255" required>
                        </td>
                        <td>
                            <input type="number" name="items[${rowIndex}][qty]" class="form-control text-end order-qty" min="0.001" step="0.001" required>
                        </td>
                        <td>
                            <input type="text" name="items[${rowIndex}][unit_snapshot]" class="form-control order-unit" maxlength="100">
                        </td>
                        <td>
                            <input type="number" name="items[${rowIndex}][unit_price]" class="form-control text-end order-unit-price" min="0" step="0.0001" required>
                        </td>
                        <td>
                            <input type="number" name="items[${rowIndex}][discount_percent]" class="form-control text-end order-discount" min="0" max="100" step="0.001">
                        </td>
                        <td>
                            <input type="text" class="form-control text-end order-line-total" readonly>
                        </td>
                        <td>
                            <input type="text" name="items[${rowIndex}][notes]" class="form-control" maxlength="2000">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger order-remove-line">X</button>
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
