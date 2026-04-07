(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const tableBody = document.querySelector('#template-items-table tbody');
        const addButton = document.getElementById('add-template-item');

        if (!tableBody || !addButton) {
            return;
        }

        const reindexRows = function () {
            const rows = tableBody.querySelectorAll('tr');
            rows.forEach(function (row, rowIndex) {
                row.querySelectorAll('input').forEach(function (input) {
                    const currentName = input.getAttribute('name');
                    if (!currentName) {
                        return;
                    }

                    const nextName = currentName.replace(/items\[\d+\]/, 'items[' + rowIndex + ']');
                    input.setAttribute('name', nextName);
                });
            });
        };

        const addRow = function () {
            const rowCount = tableBody.querySelectorAll('tr').length;
            const row = document.createElement('tr');
            row.innerHTML = [
                '<td>',
                '    <input type="text" name="items[' + rowCount + '][description]" class="form-control" maxlength="500" required>',
                '</td>',
                '<td class="text-center">',
                '    <input type="hidden" name="items[' + rowCount + '][is_required]" value="0">',
                '    <input type="checkbox" name="items[' + rowCount + '][is_required]" value="1" class="form-check-input">',
                '</td>',
                '<td>',
                '    <input type="number" name="items[' + rowCount + '][sort_order]" class="form-control" value="' + ((rowCount + 1) * 10) + '" min="0" max="9999">',
                '</td>',
                '<td class="text-center">',
                '    <button type="button" class="btn btn-sm btn-outline-danger remove-template-item">Remover</button>',
                '</td>'
            ].join('');

            tableBody.appendChild(row);
            reindexRows();
        };

        addButton.addEventListener('click', addRow);

        tableBody.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.remove-template-item');
            if (!removeButton) {
                return;
            }

            const rows = tableBody.querySelectorAll('tr');
            if (rows.length <= 1) {
                window.alert('O template deve ter pelo menos 1 item.');
                return;
            }

            const row = removeButton.closest('tr');
            if (row) {
                row.remove();
                reindexRows();
            }
        });
    });
})();
