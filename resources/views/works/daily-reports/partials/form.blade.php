@php
    $reportDate = old('report_date', optional($dailyReport->report_date)->format('Y-m-d'));
    $dayStatus = old('day_status', $dailyReport->day_status ?: \App\Models\WorkDailyReport::STATUS_NORMAL);
    $hoursSpent = old('hours_spent', isset($dailyReport->hours_spent) ? number_format((float) $dailyReport->hours_spent, 2, '.', '') : '0.00');

    $rows = old('items');
    if (! is_array($rows)) {
        $rows = $dailyReport->relationLoaded('items')
            ? $dailyReport->items->map(function ($item) {
                return [
                    'item_id' => $item->item_id,
                    'description_snapshot' => $item->description_snapshot,
                    'quantity' => number_format((float) $item->quantity, 3, '.', ''),
                    'unit_snapshot' => $item->unit_snapshot,
                    'item_text' => $item->item
                        ? ($item->item->code . ' - ' . $item->item->name . ($item->item->unit?->code ? ' (' . $item->item->unit->code . ')' : ''))
                        : null,
                ];
            })->all()
            : [];
    }

    if (count($rows) === 0) {
        $rows = [[
            'item_id' => null,
            'description_snapshot' => null,
            'quantity' => null,
            'unit_snapshot' => null,
            'item_text' => null,
        ]];
    }

    $itemSearchUrl = route('api.works.items.search');
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('porto/vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('porto/vendor/select2-bootstrap-theme/select2-bootstrap.min.css') }}">
    <style>
        .daily-report-item-select-wrap .select2-container {
            width: 100% !important;
        }

        .daily-report-item-select-wrap .select2-selection--single {
            min-height: 40px;
            padding-top: 3px;
            padding-bottom: 3px;
        }

        .daily-report-item-select-wrap .select2-selection__rendered {
            line-height: 30px !important;
            padding-left: 10px !important;
            padding-right: 34px !important;
        }

        .daily-report-item-select-wrap .select2-selection__arrow {
            height: 38px !important;
            right: 8px !important;
        }

        .daily-report-item-option {
            line-height: 1.3;
        }

        .daily-report-item-option small {
            color: #6c757d;
            display: block;
        }
    </style>
@endpush

<div class="row">
    <div class="col-xl-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Registo diario</strong>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="report_date" class="form-label">Data <span class="text-danger">*</span></label>
                        <input
                            type="date"
                            id="report_date"
                            name="report_date"
                            value="{{ $reportDate }}"
                            class="form-control @error('report_date') is-invalid @enderror"
                            required
                        >
                        @error('report_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="day_status" class="form-label">Estado do dia <span class="text-danger">*</span></label>
                        <select
                            id="day_status"
                            name="day_status"
                            class="form-select @error('day_status') is-invalid @enderror"
                            required
                        >
                            @foreach ($dayStatuses as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" @selected($dayStatus === $statusValue)>
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('day_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="hours_spent" class="form-label">Horas gastas <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            id="hours_spent"
                            name="hours_spent"
                            min="0"
                            max="99999.99"
                            step="0.01"
                            value="{{ $hoursSpent }}"
                            class="form-control @error('hours_spent') is-invalid @enderror"
                            required
                        >
                        @error('hours_spent')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="work_summary" class="form-label">Resumo dos trabalhos <span class="text-danger">*</span></label>
                        <textarea
                            id="work_summary"
                            name="work_summary"
                            rows="5"
                            class="form-control @error('work_summary') is-invalid @enderror"
                            required
                        >{{ old('work_summary', $dailyReport->work_summary) }}</textarea>
                        @error('work_summary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="incidents" class="form-label">Ocorrencias</label>
                        <textarea
                            id="incidents"
                            name="incidents"
                            rows="3"
                            class="form-control @error('incidents') is-invalid @enderror"
                        >{{ old('incidents', $dailyReport->incidents) }}</textarea>
                        @error('incidents')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="notes" class="form-label">Observacoes</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="form-control @error('notes') is-invalid @enderror"
                        >{{ old('notes', $dailyReport->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Contexto</strong>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <div class="text-muted small">Obra</div>
                    <div class="fw-semibold">{{ $work->code }} - {{ $work->name }}</div>
                </div>
                <div class="mb-2">
                    <div class="text-muted small">Cliente</div>
                    <div>{{ $work->customer?->name ?? '-' }}</div>
                </div>
                <div class="mb-0">
                    <div class="text-muted small">Responsavel tecnico</div>
                    <div>{{ $work->technicalManager?->name ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Materiais aplicados</strong>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-daily-report-item">
            Adicionar linha
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th style="min-width: 300px;">Artigo (opcional)</th>
                        <th style="min-width: 280px;">Descricao <span class="text-danger">*</span></th>
                        <th style="width: 140px;">Quantidade <span class="text-danger">*</span></th>
                        <th style="width: 160px;">Unidade</th>
                        <th style="width: 100px;">Acao</th>
                    </tr>
                </thead>
                <tbody id="daily-report-items-body" data-next-index="{{ count($rows) }}">
                    @foreach ($rows as $index => $row)
                        @php
                            $selectedItemId = isset($row['item_id']) && $row['item_id'] !== '' ? (int) $row['item_id'] : null;
                            $selectedText = trim((string) ($row['item_text'] ?? ''));
                            if ($selectedText === '' && $selectedItemId !== null) {
                                $selectedText = ($row['description_snapshot'] ?: 'Artigo #' . $selectedItemId);
                                if (!empty($row['unit_snapshot'])) {
                                    $selectedText .= ' (' . $row['unit_snapshot'] . ')';
                                }
                            }
                        @endphp
                        <tr>
                            <td class="daily-report-item-select-wrap">
                                <select
                                    name="items[{{ $index }}][item_id]"
                                    class="form-select daily-report-item-select @error("items.$index.item_id") is-invalid @enderror"
                                    data-placeholder="Pesquisar artigo por codigo ou nome..."
                                >
                                    <option value="">Sem artigo</option>
                                    @if ($selectedItemId !== null)
                                        <option
                                            value="{{ $selectedItemId }}"
                                            selected
                                            data-description="{{ $row['description_snapshot'] ?? '' }}"
                                            data-unit="{{ $row['unit_snapshot'] ?? '' }}"
                                        >
                                            {{ $selectedText }}
                                        </option>
                                    @endif
                                </select>
                                @error("items.$index.item_id")
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="items[{{ $index }}][description_snapshot]"
                                    value="{{ $row['description_snapshot'] ?? '' }}"
                                    class="form-control daily-report-description @error("items.$index.description_snapshot") is-invalid @enderror"
                                    maxlength="255"
                                >
                                @error("items.$index.description_snapshot")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="items[{{ $index }}][quantity]"
                                    value="{{ $row['quantity'] ?? '' }}"
                                    min="0"
                                    step="0.001"
                                    class="form-control @error("items.$index.quantity") is-invalid @enderror"
                                >
                                @error("items.$index.quantity")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="items[{{ $index }}][unit_snapshot]"
                                    value="{{ $row['unit_snapshot'] ?? '' }}"
                                    class="form-control daily-report-unit @error("items.$index.unit_snapshot") is-invalid @enderror"
                                    maxlength="100"
                                >
                                @error("items.$index.unit_snapshot")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger daily-report-remove-row">Remover</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @error('items')
            <div class="text-danger small mt-2">{{ $message }}</div>
        @enderror

        <div class="form-text mt-2">
            Pesquisa de artigos com 2+ letras. Nesta fase os materiais sao apenas registados no diario e nao geram movimento de stock.
        </div>
    </div>
</div>

<script type="text/template" id="daily-report-item-row-template">
<tr>
    <td class="daily-report-item-select-wrap">
        <select name="items[__INDEX__][item_id]" class="form-select daily-report-item-select" data-placeholder="Pesquisar artigo por codigo ou nome...">
            <option value="">Sem artigo</option>
        </select>
    </td>
    <td>
        <input type="text" name="items[__INDEX__][description_snapshot]" class="form-control daily-report-description" maxlength="255">
    </td>
    <td>
        <input type="number" name="items[__INDEX__][quantity]" min="0" step="0.001" class="form-control">
    </td>
    <td>
        <input type="text" name="items[__INDEX__][unit_snapshot]" class="form-control daily-report-unit" maxlength="100">
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger daily-report-remove-row">Remover</button>
    </td>
</tr>
</script>

@push('scripts')
    <script src="{{ asset('porto/vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('porto/vendor/select2/js/i18n/pt.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const itemSearchUrl = @json($itemSearchUrl);
            const tableBody = document.getElementById('daily-report-items-body');
            const addButton = document.getElementById('add-daily-report-item');
            const template = document.getElementById('daily-report-item-row-template');

            if (!tableBody || !addButton || !template) {
                return;
            }

            let nextIndex = Number(tableBody.dataset.nextIndex || tableBody.children.length || 0);

            const escapeHtml = function (value) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                };

                return String(value || '').replace(/[&<>"']/g, function (char) {
                    return map[char];
                });
            };

            const initSelect2 = function (selectEl) {
                if (!window.jQuery || typeof jQuery.fn.select2 !== 'function') {
                    return;
                }

                const $select = jQuery(selectEl);
                if ($select.data('select2')) {
                    return;
                }

                $select.select2({
                    theme: 'bootstrap',
                    width: '100%',
                    dropdownAutoWidth: true,
                    selectOnClose: true,
                    allowClear: true,
                    placeholder: selectEl.dataset.placeholder || 'Pesquisar artigo por codigo ou nome...',
                    minimumInputLength: 2,
                    ajax: {
                        url: itemSearchUrl,
                        dataType: 'json',
                        delay: 300,
                        cache: true,
                        data: function (params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: (data.results || []).map(function (item) {
                                    return {
                                        id: item.id,
                                        text: item.text,
                                        code: item.code,
                                        name: item.name,
                                        unit_code: item.unit_code,
                                        unit_name: item.unit_name,
                                        type_label: item.type_label
                                    };
                                }),
                                pagination: data.pagination || { more: false }
                            };
                        }
                    },
                    templateResult: function (item) {
                        if (item.loading) {
                            return item.text;
                        }

                        const code = escapeHtml(item.code || '');
                        const name = escapeHtml(item.name || item.text || '');
                        const unit = escapeHtml(item.unit_code || '-');
                        const type = escapeHtml(item.type_label || '');
                        const meta = type ? ('Unidade: ' + unit + ' | Tipo: ' + type) : ('Unidade: ' + unit);

                        return '<div class="daily-report-item-option"><strong>' + code + '</strong> - ' + name + '<small>' + meta + '</small></div>';
                    },
                    templateSelection: function (item) {
                        if (!item.id) {
                            return item.text || '';
                        }

                        return item.text || ((item.code || '') + ' - ' + (item.name || ''));
                    },
                    escapeMarkup: function (markup) {
                        return markup;
                    },
                    language: {
                        inputTooShort: function () {
                            return 'Escreve pelo menos 2 caracteres';
                        },
                        searching: function () {
                            return 'A pesquisar...';
                        },
                        noResults: function () {
                            return 'Sem resultados';
                        },
                        loadingMore: function () {
                            return 'A carregar mais resultados...';
                        }
                    }
                });

                $select.on('select2:select', function (event) {
                    const row = selectEl.closest('tr');
                    if (!row) {
                        return;
                    }

                    const selected = event.params && event.params.data ? event.params.data : null;
                    if (!selected) {
                        return;
                    }

                    const descriptionInput = row.querySelector('.daily-report-description');
                    const unitInput = row.querySelector('.daily-report-unit');

                    if (descriptionInput && descriptionInput.value.trim() === '') {
                        descriptionInput.value = selected.name || '';
                    }

                    if (unitInput && unitInput.value.trim() === '') {
                        unitInput.value = selected.unit_name || selected.unit_code || '';
                    }
                });
            };

            const bindRowEvents = function (row) {
                const itemSelect = row.querySelector('.daily-report-item-select');
                const descriptionInput = row.querySelector('.daily-report-description');
                const unitInput = row.querySelector('.daily-report-unit');
                const removeButton = row.querySelector('.daily-report-remove-row');

                if (itemSelect) {
                    initSelect2(itemSelect);

                    itemSelect.addEventListener('change', function () {
                        const selected = itemSelect.options[itemSelect.selectedIndex];
                        if (!selected) {
                            return;
                        }

                        if (descriptionInput && descriptionInput.value.trim() === '') {
                            descriptionInput.value = selected.getAttribute('data-description') || '';
                        }

                        if (unitInput && unitInput.value.trim() === '') {
                            unitInput.value = selected.getAttribute('data-unit') || '';
                        }
                    });
                }

                if (removeButton) {
                    removeButton.addEventListener('click', function () {
                        row.remove();
                    });
                }
            };

            Array.from(tableBody.querySelectorAll('tr')).forEach(bindRowEvents);

            addButton.addEventListener('click', function () {
                const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
                nextIndex += 1;

                const wrapper = document.createElement('tbody');
                wrapper.innerHTML = html.trim();
                const row = wrapper.querySelector('tr');

                if (!row) {
                    return;
                }

                tableBody.appendChild(row);
                bindRowEvents(row);
            });
        });
    </script>
@endpush

