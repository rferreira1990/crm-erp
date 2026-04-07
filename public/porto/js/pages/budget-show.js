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
        const config = document.getElementById('budget-show-config');
        if (!config) {
            return;
        }

        const enableItemSelect = config.getAttribute('data-enable-item-select') === '1';
        const itemSearchUrl = config.getAttribute('data-item-search-url') || '';
        const openSendEmailModal = config.getAttribute('data-open-send-email-modal') === '1';

        if (enableItemSelect && itemSearchUrl && window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
            const $itemSelect = window.jQuery('#item_id');

            if ($itemSelect.length) {
                $itemSelect.select2({
                    theme: 'bootstrap',
                    width: '100%',
                    dropdownAutoWidth: true,
                    selectOnClose: true,
                    allowClear: true,
                    placeholder: $itemSelect.data('placeholder') || 'Pesquisar artigo por codigo ou nome...',
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

                        return '<div class="budget-item-option"><strong>' + code + '</strong> - ' + name + '<small>' + meta + '</small></div>';
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

                $itemSelect.on('select2:open', function () {
                    const searchField = document.querySelector('.select2-container--open .select2-search__field');
                    if (searchField) {
                        searchField.setAttribute('autocomplete', 'off');
                        searchField.focus();
                    }
                });
            }
        }

        document.querySelectorAll('.tax-rate-select').forEach(function (select) {
            const targetSelector = select.getAttribute('data-target');
            const wrapper = targetSelector ? document.querySelector(targetSelector) : null;

            if (!wrapper) {
                return;
            }

            const reasonSelect = wrapper.querySelector('.tax-exemption-reason-select');

            const toggleReasonField = function () {
                const selectedOption = select.options[select.selectedIndex];
                const isExempt = selectedOption && selectedOption.dataset && selectedOption.dataset.isExempt === '1';
                const defaultReasonId = (selectedOption && selectedOption.dataset && selectedOption.dataset.defaultReasonId) || '';

                wrapper.style.display = isExempt ? 'block' : 'none';

                if (!isExempt && reasonSelect) {
                    reasonSelect.value = '';
                }

                if (isExempt && reasonSelect && !reasonSelect.value && defaultReasonId) {
                    reasonSelect.value = defaultReasonId;
                }
            };

            select.addEventListener('change', toggleReasonField);
            toggleReasonField();
        });

        if (openSendEmailModal && typeof window.bootstrap !== 'undefined') {
            const sendEmailModalElement = document.getElementById('sendBudgetEmailModal');
            if (sendEmailModalElement) {
                const sendEmailModal = new window.bootstrap.Modal(sendEmailModalElement);
                sendEmailModal.show();
            }
        }
    });
})();
