(function () {
    'use strict';

    function escapeHtml(value) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };

        return String(value || '').replace(/[&<>"']/g, function (char) {
            return map[char];
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const tableBody = document.getElementById('daily-report-items-body');
        const addButton = document.getElementById('add-daily-report-item');
        const template = document.getElementById('daily-report-item-row-template');

        if (!tableBody || !addButton || !template) {
            return;
        }

        const itemSearchUrl = tableBody.getAttribute('data-item-search-url') || '';
        let nextIndex = Number(tableBody.getAttribute('data-next-index') || tableBody.children.length || 0);

        const initSelect2 = function (selectEl) {
            if (!itemSearchUrl) {
                return;
            }

            if (!window.jQuery || typeof window.jQuery.fn.select2 !== 'function') {
                return;
            }

            const $select = window.jQuery(selectEl);
            if ($select.data('select2')) {
                return;
            }

            $select.select2({
                theme: 'bootstrap',
                width: '100%',
                dropdownAutoWidth: true,
                selectOnClose: true,
                allowClear: true,
                placeholder: selectEl.getAttribute('data-placeholder') || 'Pesquisar artigo por codigo ou nome...',
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
                                    unit_code: item.unit_code,
                                    unit_name: item.unit_name,
                                    type_label: item.type_label
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
                    const type = escapeHtml(item.type_label || '');
                    const meta = type ? ('Unidade: ' + unit + ' | Tipo: ' + type) : ('Unidade: ' + unit);

                    return '<div class="daily-report-item-option"><strong>' + code + '</strong> - ' + name + '<small>' + meta + '</small></div>';
                },
                templateSelection: function (item) {
                    if (!item.id) {
                        return item.text || '';
                    }

                    return item.text || ((item.code || '') + ' - ' + (item.name || ''));
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
                const row = selectEl.closest('tr');
                if (!row) {
                    return;
                }

                const selected = event.params && event.params.data ? event.params.data : null;
                if (!selected) {
                    return;
                }

                const descriptionInput = row.querySelector('.daily-report-description');
                const unitInput = row.querySelector('.daily-report-unit');

                if (descriptionInput && descriptionInput.value.trim() === '') {
                    descriptionInput.value = selected.name || '';
                }

                if (unitInput && unitInput.value.trim() === '') {
                    unitInput.value = selected.unit_name || selected.unit_code || '';
                }
            });
        };

        const bindRowEvents = function (row) {
            const itemSelect = row.querySelector('.daily-report-item-select');
            const descriptionInput = row.querySelector('.daily-report-description');
            const unitInput = row.querySelector('.daily-report-unit');
            const removeButton = row.querySelector('.daily-report-remove-row');

            if (itemSelect) {
                initSelect2(itemSelect);

                itemSelect.addEventListener('change', function () {
                    const selected = itemSelect.options[itemSelect.selectedIndex];
                    if (!selected) {
                        return;
                    }

                    if (descriptionInput && descriptionInput.value.trim() === '') {
                        descriptionInput.value = selected.getAttribute('data-description') || '';
                    }

                    if (unitInput && unitInput.value.trim() === '') {
                        unitInput.value = selected.getAttribute('data-unit') || '';
                    }
                });
            }

            if (removeButton) {
                removeButton.addEventListener('click', function () {
                    row.remove();
                });
            }
        };

        Array.from(tableBody.querySelectorAll('tr')).forEach(bindRowEvents);

        addButton.addEventListener('click', function () {
            const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
            nextIndex += 1;

            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = html.trim();
            const row = wrapper.querySelector('tr');

            if (!row) {
                return;
            }

            tableBody.appendChild(row);
            bindRowEvents(row);
        });
    });
})();
