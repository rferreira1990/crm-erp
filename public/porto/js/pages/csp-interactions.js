(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-confirm-form').forEach(function (form) {
            if (form.dataset.confirmBound === '1') {
                return;
            }

            form.dataset.confirmBound = '1';
            form.addEventListener('submit', function (event) {
                const message = form.getAttribute('data-confirm-message') || 'Confirmar operacao?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });

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

        document.querySelectorAll('.js-submit-parent-form').forEach(function (element) {
            if (element.dataset.submitParentBound === '1') {
                return;
            }

            element.dataset.submitParentBound = '1';
            element.addEventListener('click', function (event) {
                event.preventDefault();

                const form = element.closest('form');
                if (form) {
                    form.submit();
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
