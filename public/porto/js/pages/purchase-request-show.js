(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const configElement = document.getElementById('purchase-request-show-config');
        if (!configElement) {
            return;
        }

        const supplierItemReferenceMap = parseJson(
            configElement.dataset.supplierItemReferenceMap,
            {}
        );

        const supplierSelectEmail = document.getElementById('email_supplier_id');
        const recipientNameInput = document.getElementById('recipient_name');
        const recipientEmailInput = document.getElementById('recipient_email');
        const forcedSupplierSelect = document.getElementById('forced_supplier_id');
        const forcedSupplierSummary = document.getElementById('forced_supplier_summary');
        const awardSupplierSelect = document.getElementById('award_supplier_id');
        const awardRecipientNameInput = document.getElementById('award_recipient_name');
        const awardRecipientEmailInput = document.getElementById('award_recipient_email');

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

        function parseNumber(value) {
            const parsed = Number(String(value || '').replace(',', '.'));
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function calcLine(row) {
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
        }

        function applySupplierRefs(form) {
            const supplierSelect = form.querySelector('.supplier-selector');
            if (!supplierSelect || !supplierSelect.value) {
                return;
            }

            form.querySelectorAll('.quote-line-row').forEach(function (row) {
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
        }

        document.querySelectorAll('.quote-form-wrapper').forEach(function (form) {
            form.querySelectorAll('.quote-line-row').forEach(function (row) {
                ['.quoted-qty-input', '.unit-price-input', '.discount-percent-input'].forEach(function (selector) {
                    const input = row.querySelector(selector);
                    if (input) {
                        input.addEventListener('input', function () {
                            calcLine(row);
                        });
                    }
                });

                calcLine(row);
            });

            const supplierSelect = form.querySelector('.supplier-selector');
            if (supplierSelect) {
                supplierSelect.addEventListener('change', function () {
                    applySupplierRefs(form);
                });

                applySupplierRefs(form);
            }
        });

        if (supplierSelectEmail && recipientNameInput && recipientEmailInput) {
            supplierSelectEmail.addEventListener('change', function () {
                const option = supplierSelectEmail.options[supplierSelectEmail.selectedIndex];
                if (!option || !option.value) {
                    return;
                }

                if (!recipientNameInput.value.trim()) {
                    recipientNameInput.value = option.getAttribute('data-name') || '';
                }

                if (!recipientEmailInput.value.trim()) {
                    recipientEmailInput.value = option.getAttribute('data-email') || '';
                }
            });
        }

        if (awardSupplierSelect && awardRecipientNameInput && awardRecipientEmailInput) {
            const applyAwardRecipientFromSupplier = function () {
                const option = awardSupplierSelect.options[awardSupplierSelect.selectedIndex];
                if (!option || !option.value) {
                    return;
                }

                if (!awardRecipientNameInput.value.trim()) {
                    awardRecipientNameInput.value = option.getAttribute('data-name') || '';
                }

                if (!awardRecipientEmailInput.value.trim()) {
                    awardRecipientEmailInput.value = option.getAttribute('data-email') || '';
                }
            };

            awardSupplierSelect.addEventListener('change', applyAwardRecipientFromSupplier);
            applyAwardRecipientFromSupplier();
        }

        if (forcedSupplierSelect && forcedSupplierSummary) {
            const renderForcedSummary = function () {
                const option = forcedSupplierSelect.options[forcedSupplierSelect.selectedIndex];
                if (!option || !option.value) {
                    forcedSupplierSummary.textContent = 'Seleciona fornecedor para ver resumo.';
                    return;
                }

                const total = Number(option.getAttribute('data-total') || '0').toFixed(2).replace('.', ',');
                const lines = option.getAttribute('data-lines') || '0';
                const currency = option.getAttribute('data-currency') || 'EUR';
                forcedSupplierSummary.textContent = 'Resumo da proposta: '
                    + lines + ' linha(s) cotada(s), total s/ IVA ' + total + ' ' + currency + '.';
            };

            forcedSupplierSelect.addEventListener('change', renderForcedSummary);
            renderForcedSummary();
        }

        if (configElement.dataset.openSendEmailModal === '1') {
            const modalElement = document.getElementById('sendRfqEmailModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                new bootstrap.Modal(modalElement).show();
            }
        }

        if (configElement.dataset.openAwardModal === '1') {
            const mode = configElement.dataset.openAwardMode || '';
            let modalId = 'awardLowestTotalModal';
            if (mode === 'lowest_per_line') {
                modalId = 'awardLowestPerLineModal';
            } else if (mode === 'forced_supplier') {
                modalId = 'awardForcedSupplierModal';
            }

            const awardModalElement = document.getElementById(modalId);
            if (awardModalElement && typeof bootstrap !== 'undefined') {
                new bootstrap.Modal(awardModalElement).show();
            }
        }

        if (configElement.dataset.openAwardEmailModal === '1') {
            const awardEmailModalElement = document.getElementById('sendAwardEmailModal');
            if (awardEmailModalElement && typeof bootstrap !== 'undefined') {
                new bootstrap.Modal(awardEmailModalElement).show();
            }
        }
    });
})();
