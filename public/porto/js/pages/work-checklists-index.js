(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

        document.querySelectorAll('.js-checklist-toggle').forEach(function (toggleInput) {
            toggleInput.addEventListener('change', function () {
                const url = toggleInput.getAttribute('data-url');
                const checklistId = toggleInput.getAttribute('data-checklist-id');
                const itemId = toggleInput.getAttribute('data-item-id');
                const nextValue = toggleInput.checked;

                if (!url || !checklistId || !itemId) {
                    return;
                }

                toggleInput.disabled = true;

                window.fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        is_completed: nextValue ? 1 : 0
                    })
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Erro ao atualizar item.');
                        }

                        return response.json();
                    })
                    .then(function (payload) {
                        if (!payload || payload.ok !== true) {
                            throw new Error('Resposta invalida do servidor.');
                        }

                        const descriptionElement = document.querySelector('[data-checklist-item-description="' + itemId + '"]');
                        const metaElement = document.querySelector('[data-checklist-item-meta="' + itemId + '"]');
                        const progressElement = document.querySelector('[data-checklist-progress="' + checklistId + '"]');
                        const requiredElement = document.querySelector('[data-checklist-required="' + checklistId + '"]');

                        if (descriptionElement) {
                            descriptionElement.classList.toggle('text-decoration-line-through', payload.item.is_completed);
                            descriptionElement.classList.toggle('text-muted', payload.item.is_completed);
                        }

                        if (metaElement) {
                            if (payload.item.is_completed) {
                                const byName = payload.item.completed_by_name || '-';
                                const at = payload.item.completed_at ? (' · ' + payload.item.completed_at) : '';
                                metaElement.textContent = byName + at;
                            } else {
                                metaElement.textContent = '-';
                            }
                        }

                        if (progressElement) {
                            progressElement.textContent = payload.checklist.completed_items + '/' + payload.checklist.total_items;
                        }

                        if (requiredElement) {
                            const pendingRequired = Number(payload.checklist.pending_required_items || 0);
                            if (pendingRequired > 0) {
                                requiredElement.classList.remove('bg-success');
                                requiredElement.classList.add('bg-danger');
                                requiredElement.textContent = pendingRequired + ' obrigatorio(s) pendente(s)';
                            } else {
                                requiredElement.classList.remove('bg-danger');
                                requiredElement.classList.add('bg-success');
                                requiredElement.textContent = 'Obrigatorios ok';
                            }
                        }
                    })
                    .catch(function () {
                        toggleInput.checked = !nextValue;
                        window.alert('Nao foi possivel atualizar o estado do item da checklist.');
                    })
                    .finally(function () {
                        toggleInput.disabled = false;
                    });
            });
        });
    });
})();
