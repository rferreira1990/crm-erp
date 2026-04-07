(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const typeField = document.getElementById('type');
        const stockFields = document.getElementById('stock-fields');
        const tracksStock = document.getElementById('tracks_stock');
        const stockAlert = document.getElementById('stock_alert');

        if (!typeField || !stockFields || !tracksStock || !stockAlert) {
            return;
        }

        const toggleStockFields = function () {
            const isService = typeField.value === 'service';

            stockFields.style.display = isService ? 'none' : 'flex';

            if (isService) {
                tracksStock.checked = false;
                stockAlert.checked = false;
            }
        };

        typeField.addEventListener('change', toggleStockFields);
        toggleStockFields();
    });
})();
