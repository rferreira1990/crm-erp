(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const table = document.getElementById('order-items-table');
        if (!table) {
            return;
        }

        const tableBody = table.querySelector('tbody');
        const addButton = document.getElementById('add-order-line');
        const itemSearchUrl = table.dataset.itemSearchUrl || '';
        let rowIndex = tableBody ? tableBody.querySelectorAll('tr').length : 0;

        if (!tableBody || !itemSearchUrl) {
            return;
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
                                    unit_name: item.unit_name,
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
                    },
                },
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

        tableBody.querySelectorAll('tr').forEach(function (row) {
            bindRow(row);
        });

        if (addButton) {
            addButton.addEventListener('click', function () {
                const tr = document.createElement('tr');
                tr.innerHTML = '' +
                    '<td>' +
                    '  <div class="purchase-order-item-select-wrap">' +
                    '      <select name="items[' + rowIndex + '][item_id]" class="form-select purchase-order-item-select" data-placeholder="Pesquisar artigo por codigo ou nome...">' +
                    '          <option value=""></option>' +
                    '      </select>' +
                    '  </div>' +
                    '</td>' +
                    '<td><input type="text" name="items[' + rowIndex + '][description]" class="form-control order-desc" maxlength="255" required></td>' +
                    '<td><input type="number" name="items[' + rowIndex + '][qty]" class="form-control text-end order-qty" min="0.001" step="0.001" required></td>' +
                    '<td><input type="text" name="items[' + rowIndex + '][unit_snapshot]" class="form-control order-unit" maxlength="100"></td>' +
                    '<td><input type="number" name="items[' + rowIndex + '][unit_price]" class="form-control text-end order-unit-price" min="0" step="0.0001" required></td>' +
                    '<td><input type="number" name="items[' + rowIndex + '][discount_percent]" class="form-control text-end order-discount" min="0" max="100" step="0.001"></td>' +
                    '<td><input type="text" class="form-control text-end order-line-total" readonly></td>' +
                    '<td><input type="text" name="items[' + rowIndex + '][notes]" class="form-control" maxlength="2000"></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger order-remove-line">X</button></td>';

                tableBody.appendChild(tr);
                bindRow(tr);
                rowIndex++;
            });
        }
    });
})();
