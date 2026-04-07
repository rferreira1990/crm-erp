(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const table = document.getElementById('direct-purchase-items-table');
        if (!table) {
            return;
        }

        const tableBody = table.querySelector('tbody');
        const addButton = document.getElementById('add-purchase-line');
        const itemSearchUrl = table.dataset.itemSearchUrl || '';
        const subtotalLabel = document.getElementById('purchase-subtotal-label');
        const vatLabel = document.getElementById('purchase-vat-label');
        const totalLabel = document.getElementById('purchase-total-label');
        const vatRates = parseJson(table.dataset.vatRates, []);
        let rowIndex = tableBody ? tableBody.querySelectorAll('tr').length : 0;

        if (!tableBody || !itemSearchUrl) {
            return;
        }

        function parseJson(value, fallback) {
            if (!value) {
                return fallback;
            }

            try {
                return JSON.parse(value);
            } catch (error) {
                return fallback;
            }
        }

        function escapeHtml(value) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            };

            return String(value || '').replace(/[&<>"']/g, function (char) {
                return map[char];
            });
        }

        function parseNumber(value) {
            const normalized = Number(String(value || '').replace(',', '.'));
            return Number.isFinite(normalized) ? normalized : 0;
        }

        function formatNumber(value, decimals) {
            return Number(value || 0).toLocaleString('pt-PT', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
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
            const qty = parseNumber(row.querySelector('.purchase-qty') ? row.querySelector('.purchase-qty').value : '');
            const unitPrice = parseNumber(row.querySelector('.purchase-unit-price') ? row.querySelector('.purchase-unit-price').value : '');
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

            tableBody.querySelectorAll('tr').forEach(function (row) {
                const qty = parseNumber(row.querySelector('.purchase-qty') ? row.querySelector('.purchase-qty').value : '');
                const unitPrice = parseNumber(row.querySelector('.purchase-unit-price') ? row.querySelector('.purchase-unit-price').value : '');
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
                            page: params.page || 1,
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
                                    tax_rate_id: item.tax_rate_id,
                                };
                            }),
                            pagination: data.pagination || { more: false },
                        };
                    },
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
                    },
                },
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

        tableBody.querySelectorAll('tr').forEach(function (row) {
            bindRow(row);
        });

        if (addButton) {
            addButton.addEventListener('click', function () {
                const tr = document.createElement('tr');
                tr.innerHTML = '' +
                    '<td>' +
                    '    <div class="purchase-direct-item-select-wrap">' +
                    '        <select name="items[' + rowIndex + '][item_id]" class="form-select purchase-direct-item-select" data-placeholder="Pesquisar artigo por codigo ou nome...">' +
                    '            <option value=""></option>' +
                    '        </select>' +
                    '    </div>' +
                    '</td>' +
                    '<td>' +
                    '    <input type="text" name="items[' + rowIndex + '][description_snapshot]" class="form-control purchase-desc" maxlength="255" required>' +
                    '</td>' +
                    '<td>' +
                    '    <input type="number" name="items[' + rowIndex + '][quantity]" class="form-control text-end purchase-qty" min="0.001" step="0.001" required>' +
                    '</td>' +
                    '<td>' +
                    '    <input type="text" name="items[' + rowIndex + '][unit_snapshot]" class="form-control purchase-unit" maxlength="100">' +
                    '</td>' +
                    '<td>' +
                    '    <input type="number" name="items[' + rowIndex + '][unit_price]" class="form-control text-end purchase-unit-price" min="0" step="0.0001" required>' +
                    '</td>' +
                    '<td>' +
                    '    <select name="items[' + rowIndex + '][vat_rate_id]" class="form-select purchase-vat-rate" required>' +
                    buildVatSelectOptions() +
                    '    </select>' +
                    '</td>' +
                    '<td><input type="text" class="form-control text-end purchase-line-subtotal" readonly></td>' +
                    '<td><input type="text" class="form-control text-end purchase-line-vat" readonly></td>' +
                    '<td><input type="text" class="form-control text-end purchase-line-total" readonly></td>' +
                    '<td><input type="text" name="items[' + rowIndex + '][notes]" class="form-control" maxlength="2000"></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger purchase-remove-line">X</button></td>';

                tableBody.appendChild(tr);
                bindRow(tr);
                rowIndex++;
            });
        }
    });
})();
