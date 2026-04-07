(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-confirm-submit').forEach(function (button) {
            if (button.dataset.confirmBound === '1') {
                return;
            }

            button.dataset.confirmBound = '1';
            button.addEventListener('click', function (event) {
                const message = button.getAttribute('data-confirm-message') || 'Confirmar operacao?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll('.js-trigger-print').forEach(function (button) {
            if (button.dataset.printBound === '1') {
                return;
            }

            button.dataset.printBound = '1';
            button.addEventListener('click', function () {
                window.print();
            });
        });
    });
})();
