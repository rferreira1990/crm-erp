(function () {
    'use strict';

    function toggleExpenseFields(form) {
        const typeInput = form.querySelector('.expense-type');
        const kmWrappers = form.querySelectorAll('.expense-km-wrapper');
        const qtyWrappers = form.querySelectorAll('.expense-qty-wrapper');
        const travelWrappers = form.querySelectorAll('.expense-travel-wrapper');
        const kmInput = form.querySelector('.expense-km');
        const qtyInput = form.querySelector('.expense-qty');
        const unitCostInput = form.querySelector('.expense-unit-cost');
        const totalCostInput = form.querySelector('.expense-total-cost');

        if (!typeInput) {
            return;
        }

        const isTravelKm = typeInput.value === 'travel_km';

        kmWrappers.forEach(function (wrapper) {
            wrapper.classList.toggle('d-none', !isTravelKm);
        });

        travelWrappers.forEach(function (wrapper) {
            wrapper.classList.toggle('d-none', !isTravelKm);
        });

        qtyWrappers.forEach(function (wrapper) {
            wrapper.classList.toggle('d-none', isTravelKm);
        });

        if (isTravelKm && qtyInput) {
            qtyInput.value = '';
        }

        if (isTravelKm && totalCostInput) {
            const kmValue = Number(kmInput ? kmInput.value : 0);
            const unitValue = Number(unitCostInput ? unitCostInput.value : 0);

            if (!Number.isNaN(kmValue) && !Number.isNaN(unitValue)) {
                totalCostInput.value = (kmValue * unitValue).toFixed(2);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.work-expense-form').forEach(function (form) {
            const typeInput = form.querySelector('.expense-type');
            const kmInput = form.querySelector('.expense-km');
            const unitCostInput = form.querySelector('.expense-unit-cost');

            if (typeInput) {
                typeInput.addEventListener('change', function () {
                    toggleExpenseFields(form);
                });
            }

            [kmInput, unitCostInput].forEach(function (input) {
                if (input) {
                    input.addEventListener('input', function () {
                        toggleExpenseFields(form);
                    });
                }
            });

            toggleExpenseFields(form);
        });
    });
})();
