(function () {
    'use strict';

    function normalize(value) {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const config = document.getElementById('budget-customer-search-config');
        const hiddenInput = document.getElementById('customer_id');
        const searchInput = document.getElementById('customer_search');
        const dropdown = document.getElementById('customer_dropdown');

        if (!config || !hiddenInput || !searchInput || !dropdown) {
            return;
        }

        const customersJson = config.getAttribute('data-customer-options') || '[]';
        let customers = [];

        try {
            customers = JSON.parse(customersJson);
            if (!Array.isArray(customers)) {
                customers = [];
            }
        } catch (error) {
            customers = [];
        }

        const selectedCustomerId = hiddenInput.value ? String(hiddenInput.value) : '';
        const selectedCustomer = customers.find(function (customer) {
            return String(customer.id) === selectedCustomerId;
        });

        if (selectedCustomer) {
            searchInput.value = selectedCustomer.label || '';
        }

        const closeDropdown = function () {
            dropdown.classList.add('d-none');
            dropdown.innerHTML = '';
        };

        const selectCustomer = function (customer) {
            hiddenInput.value = customer.id;
            searchInput.value = customer.label || '';
            closeDropdown();
        };

        const renderDropdown = function (items) {
            dropdown.innerHTML = '';

            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'list-group-item text-muted';
                empty.textContent = 'Nenhum cliente encontrado.';
                dropdown.appendChild(empty);
                dropdown.classList.remove('d-none');
                return;
            }

            items.forEach(function (customer) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action';
                button.textContent = customer.label || '';

                button.addEventListener('click', function () {
                    selectCustomer(customer);
                });

                dropdown.appendChild(button);
            });

            dropdown.classList.remove('d-none');
        };

        const filterCustomers = function () {
            const term = normalize(searchInput.value);

            if (term === '') {
                renderDropdown(customers.slice(0, 20));
                return;
            }

            const filtered = customers
                .filter(function (customer) {
                    return normalize(customer.search).includes(term);
                })
                .slice(0, 20);

            renderDropdown(filtered);
        };

        searchInput.addEventListener('focus', filterCustomers);
        searchInput.addEventListener('input', function () {
            hiddenInput.value = '';
            filterCustomers();
        });
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeDropdown();
            }
        });

        document.addEventListener('click', function (event) {
            const clickedInside = searchInput.contains(event.target) || dropdown.contains(event.target);
            if (!clickedInside) {
                closeDropdown();
            }
        });
    });
})();
