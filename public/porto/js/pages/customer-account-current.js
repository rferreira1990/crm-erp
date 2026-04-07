(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const typeField = document.getElementById('customer_account_entry_type');
        const amountField = document.getElementById('customer_account_entry_amount');

        if (!typeField) {
            return;
        }

        document.querySelectorAll('.js-customer-account-type').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetType = button.getAttribute('data-type');
                if (targetType) {
                    typeField.value = targetType;
                }

                if (amountField) {
                    amountField.focus();
                }
            });
        });
    });
})();
