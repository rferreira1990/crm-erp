(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('.quote-form-wrapper');
        if (!form) {
            return;
        }

        const supplierItemReferenceMap = parseJson(
            form.dataset.supplierItemReferenceMap,
            {}
        );

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
            const normalized = Number(String(value || '').replace(',', '.'));
            return Number.isFinite(normalized) ? normalized : 0;
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

        function applySupplierRefs() {
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
            supplierSelect.addEventListener('change', applySupplierRefs);
        }

        applySupplierRefs();
    });
})();
