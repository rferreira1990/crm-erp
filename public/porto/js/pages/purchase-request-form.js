(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const table = document.getElementById('rfq-items-table');
        if (!table) {
            return;
        }

        const tableBody = table.querySelector('tbody');
        const addButton = document.getElementById('add-rfq-line');
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

        function getSelectedPayload($select) {
            if (!window.jQuery || typeof jQuery.fn.select2 !== 'function') {
                const selected = $select.find('option:selected');

                return {
                    id: selected.val(),
                    name: selected.data('name') || '',
                    description: selected.data('description') || selected.data('name') || '',
                    unit_code: selected.data('unit') || '',
                };
            }

            const data = $select.select2('data');
            const selected = data && data[0] ? data[0] : null;
            if (!selected || !selected.id) {
                return null;
            }

            return {
                id: selected.id,
                name: selected.name || (selected.element ? selected.element.dataset.name : '') || '',
                description: selected.description || (selected.element ? selected.element.dataset.description : '') || selected.name || '',
                unit_code: selected.unit_code || (selected.element ? selected.element.dataset.unit : '') || '',
            };
        }

        function applyItemDataToRow(row, payload) {
            if (!payload || !payload.id) {
                return;
            }

            const descField = row.querySelector('.rfq-desc');
            const unitField = row.querySelector('.rfq-unit');

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
            if ($select.data('rfqSelect2Ready')) {
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

                    return '<div class="rfq-item-option"><strong>' + code + '</strong> - ' + name + '<small>Unidade: ' + unit + '</small></div>';
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

            $select.on('change', function () {
                const row = this.closest('tr');
                const payload = getSelectedPayload($select);
                applyItemDataToRow(row, payload);
            });

            $select.data('rfqSelect2Ready', true);
        }

        function bindRow(row) {
            const removeButton = row.querySelector('.rfq-remove-line');
            if (removeButton) {
                removeButton.addEventListener('click', function () {
                    if (tableBody.querySelectorAll('tr').length <= 1) {
                        return;
                    }

                    row.remove();
                });
            }

            const itemSelect = row.querySelector('.rfq-item-select');
            if (!itemSelect) {
                return;
            }

            initItemSelect(itemSelect);
        }

        tableBody.querySelectorAll('tr').forEach(function (row) {
            bindRow(row);
        });

        if (addButton) {
            addButton.addEventListener('click', function () {
                const tr = document.createElement('tr');
                tr.innerHTML = '' +
                    '<td>' +
                    '  <div class="rfq-item-select-wrap">' +
                    '      <select name="items[' + rowIndex + '][item_id]" class="form-select rfq-item-select" data-placeholder="Pesquisar artigo por codigo ou nome...">' +
                    '          <option value=""></option>' +
                    '      </select>' +
                    '  </div>' +
                    '</td>' +
                    '<td>' +
                    '  <input type="text" name="items[' + rowIndex + '][description]" class="form-control rfq-desc" maxlength="255" required>' +
                    '</td>' +
                    '<td>' +
                    '  <input type="number" name="items[' + rowIndex + '][qty]" class="form-control" min="0.001" step="0.001" required>' +
                    '</td>' +
                    '<td>' +
                    '  <input type="text" name="items[' + rowIndex + '][unit_snapshot]" class="form-control rfq-unit" maxlength="100">' +
                    '</td>' +
                    '<td>' +
                    '  <input type="text" name="items[' + rowIndex + '][notes]" class="form-control" maxlength="2000">' +
                    '</td>' +
                    '<td class="text-center">' +
                    '  <button type="button" class="btn btn-sm btn-outline-danger rfq-remove-line">X</button>' +
                    '</td>';

                tableBody.appendChild(tr);
                bindRow(tr);
                rowIndex++;
            });
        }
    });
})();
