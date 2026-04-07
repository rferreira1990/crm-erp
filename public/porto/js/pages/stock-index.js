(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('manual_movement_type');
        const directionSelect = document.getElementById('manual_direction');
        const itemSelect = document.getElementById('manual_item_id');
        const hint = document.getElementById('manual-stock-hint');

        if (!typeSelect || !directionSelect) {
            return;
        }

        const canAdjust = typeSelect.getAttribute('data-can-manual-adjustment') === '1';

        const syncDirection = function () {
            if (typeSelect.value === 'manual_entry') {
                directionSelect.value = 'in';
            } else if (typeSelect.value === 'manual_exit') {
                directionSelect.value = 'out';
            } else {
                directionSelect.value = canAdjust ? 'adjustment' : 'in';
            }
        };

        const syncStockHint = function () {
            if (!itemSelect || !hint) {
                return;
            }

            const selected = itemSelect.options[itemSelect.selectedIndex];
            if (!selected || !selected.value) {
                hint.textContent = '';
                return;
            }

            const stockValue = Number(selected.getAttribute('data-stock') || 0).toFixed(3).replace('.', ',');
            hint.textContent = 'Stock atual do artigo: ' + stockValue;
        };

        typeSelect.addEventListener('change', syncDirection);
        syncDirection();

        if (itemSelect) {
            itemSelect.addEventListener('change', syncStockHint);
            syncStockHint();
        }
    });
})();
