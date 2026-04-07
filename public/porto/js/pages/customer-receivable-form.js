(function () {
    'use strict';

    function formatDate(date) {
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        return yyyy + '-' + mm + '-' + dd;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const customerSelect = document.getElementById('customer_id');
        const issueDateInput = document.getElementById('issue_date');
        const dueDateInput = document.getElementById('due_date');

        if (!customerSelect || !issueDateInput || !dueDateInput) {
            return;
        }

        const dueDateTouched = function () {
            return dueDateInput.getAttribute('data-touched') === '1';
        };

        dueDateInput.addEventListener('change', function () {
            dueDateInput.setAttribute('data-touched', '1');
        });

        customerSelect.addEventListener('change', function () {
            if (dueDateTouched()) {
                return;
            }

            const selectedOption = customerSelect.options[customerSelect.selectedIndex];
            if (!selectedOption) {
                return;
            }

            const days = Number(selectedOption.getAttribute('data-payment-terms-days') || 0);
            if (Number.isNaN(days)) {
                return;
            }

            const baseDate = issueDateInput.value
                ? new Date(issueDateInput.value + 'T00:00:00')
                : new Date();

            if (Number.isNaN(baseDate.getTime())) {
                return;
            }

            baseDate.setDate(baseDate.getDate() + Math.max(0, days));
            dueDateInput.value = formatDate(baseDate);
        });
    });
})();
