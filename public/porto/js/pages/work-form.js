(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const customerSelect = document.getElementById('customer_id');
        const budgetSelect = document.getElementById('budget_id');

        if (!customerSelect || !budgetSelect) {
            return;
        }

        const filterBudgetsByCustomer = function () {
            const customerId = customerSelect.value;
            const selectedBudget = budgetSelect.value;

            Array.from(budgetSelect.options).forEach(function (option, index) {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                const optionCustomerId = option.getAttribute('data-customer-id') || '';
                const shouldShow = !customerId || optionCustomerId === customerId;

                option.hidden = !shouldShow;

                if (!shouldShow && option.value === selectedBudget) {
                    budgetSelect.value = '';
                }
            });
        };

        customerSelect.addEventListener('change', filterBudgetsByCustomer);
        filterBudgetsByCustomer();
    });
})();
