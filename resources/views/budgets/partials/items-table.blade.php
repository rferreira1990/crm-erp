<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Linhas do orçamento</strong>
        <span class="text-muted">{{ $budget->items->count() }} linha(s)</span>
    </div>

    <div class="card-body">
        @if ($budget->items->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Artigo</th>
                            <th style="min-width: 110px;">Qtd.</th>
                            <th style="min-width: 130px;">Preço Unit.</th>
                            <th style="min-width: 110px;">Desc. %</th>
                            <th style="min-width: 200px;">Taxa IVA</th>
                            <th style="min-width: 230px;">Motivo Isenção</th>
                            <th>Subtotal</th>
                            <th>IVA</th>
                            <th>Total</th>
                            @can('budgets.update')
                                <th style="min-width: 190px;">Ações</th>
                            @endcan
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($budget->items as $line)
                            @php
                                $collapseId = 'line-notes-' . $line->id;
                                $taxReasonWrapperId = 'tax-reason-wrapper-' . $line->id;
                                $taxRateSelectId = 'tax-rate-id-' . $line->id;

                                $currentTaxRate = $taxRates->firstWhere('id', $line->tax_rate_id);
                                $currentIsExempt = $currentTaxRate ? (bool) $currentTaxRate->is_exempt : ((float) $line->tax_percent === 0.0 && !empty($line->tax_exemption_reason));
                            @endphp

                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $line->item_code ?: '—' }}</td>

                                <td>
                                    <div><strong>{{ $line->item_name }}</strong></div>

                                    @if ($line->description)
                                        <div class="small text-muted">
                                            {{ $line->description }}
                                        </div>
                                    @endif
                                </td>

                                @can('budgets.update')
                                    <td>
                                        <form
                                            method="POST"
                                            action="{{ route('budgets.items.update', [$budget, $line]) }}"
                                            class="budget-line-main-form"
                                        >
                                            @csrf
                                            @method('PUT')

                                            <input
                                                type="number"
                                                name="quantity"
                                                class="form-control form-control-sm"
                                                value="{{ number_format((float) $line->quantity, 3, '.', '') }}"
                                                min="0.001"
                                                step="0.001"
                                                required
                                            >
                                    </td>

                                    <td>
                                            <input
                                                type="number"
                                                name="unit_price"
                                                class="form-control form-control-sm"
                                                value="{{ number_format((float) $line->unit_price, 2, '.', '') }}"
                                                min="0"
                                                step="0.01"
                                                required
                                            >
                                    </td>

                                    <td>
                                            <input
                                                type="number"
                                                name="discount_percent"
                                                class="form-control form-control-sm"
                                                value="{{ number_format((float) $line->discount_percent, 2, '.', '') }}"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                            >
                                    </td>

                                    <td>
                                            <select
                                                name="tax_rate_id"
                                                id="{{ $taxRateSelectId }}"
                                                class="form-select form-select-sm tax-rate-select"
                                                data-target="#{{ $taxReasonWrapperId }}"
                                                required
                                            >
                                                @foreach ($taxRates as $taxRate)
                                                    <option
                                                        value="{{ $taxRate->id }}"
                                                        data-is-exempt="{{ $taxRate->is_exempt ? '1' : '0' }}"
                                                        data-default-reason-id="{{ $taxRate->exemption_reason_id }}"
                                                        {{ (int) $line->tax_rate_id === (int) $taxRate->id ? 'selected' : '' }}
                                                    >
                                                        {{ $taxRate->name }} ({{ number_format((float) $taxRate->percent, 2, ',', '.') }}%)
                                                    </option>
                                                @endforeach
                                            </select>
                                    </td>

                                    <td>
                                            <div
                                                id="{{ $taxReasonWrapperId }}"
                                                class="tax-reason-wrapper"
                                                style="{{ $currentIsExempt ? '' : 'display:none;' }}"
                                            >
                                                <select
                                                    name="tax_exemption_reason_id"
                                                    class="form-select form-select-sm tax-exemption-reason-select"
                                                >
                                                    <option value="">Selecionar motivo</option>

                                                    @foreach ($taxExemptionReasons as $reason)
                                                        <option
                                                            value="{{ $reason->id }}"
                                                            {{ (int) $line->tax_exemption_reason_id === (int) $reason->id ? 'selected' : '' }}
                                                        >
                                                            {{ $reason->code }} - {{ $reason->description }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                    </td>

                                    <td>{{ number_format((float) $line->subtotal, 2, ',', '.') }} €</td>
                                    <td>{{ number_format((float) $line->tax_total, 2, ',', '.') }} €</td>
                                    <td><strong>{{ number_format((float) $line->total, 2, ',', '.') }} €</strong></td>

                                    <td>
                                            <div class="d-flex flex-column gap-2">
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                                        Guardar
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#{{ $collapseId }}"
                                                        aria-expanded="false"
                                                        aria-controls="{{ $collapseId }}"
                                                        title="Observações"
                                                    >
                                                        ▼
                                                    </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('budgets.items.destroy', [$budget, $line]) }}"
                                            onsubmit="return confirm('Remover esta linha do orçamento?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Remover
                                            </button>
                                        </form>
                                                </div>
                                            </div>
                                    </td>
                                @else
                                    <td>{{ number_format((float) $line->quantity, 3, ',', '.') }}</td>
                                    <td>{{ number_format((float) $line->unit_price, 2, ',', '.') }} €</td>
                                    <td>{{ number_format((float) $line->discount_percent, 2, ',', '.') }}%</td>
                                    <td>
                                        {{ $line->tax_rate_name ?: '—' }}
                                        ({{ number_format((float) $line->tax_percent, 2, ',', '.') }}%)
                                    </td>
                                    <td>{{ $line->tax_exemption_reason ?: '—' }}</td>
                                    <td>{{ number_format((float) $line->subtotal, 2, ',', '.') }} €</td>
                                    <td>{{ number_format((float) $line->tax_total, 2, ',', '.') }} €</td>
                                    <td><strong>{{ number_format((float) $line->total, 2, ',', '.') }} €</strong></td>
                                @endcan
                            </tr>

                            @can('budgets.update')
                                <tr class="collapse" id="{{ $collapseId }}">
                                    <td colspan="12" class="bg-light">
                                        <form
                                            method="POST"
                                            action="{{ route('budgets.items.update', [$budget, $line]) }}"
                                        >
                                            @csrf
                                            @method('PUT')

                                            <input type="hidden" name="quantity" value="{{ number_format((float) $line->quantity, 3, '.', '') }}">
                                            <input type="hidden" name="unit_price" value="{{ number_format((float) $line->unit_price, 2, '.', '') }}">
                                            <input type="hidden" name="discount_percent" value="{{ number_format((float) $line->discount_percent, 2, '.', '') }}">
                                            <input type="hidden" name="tax_rate_id" value="{{ (int) $line->tax_rate_id }}">
                                            <input type="hidden" name="tax_exemption_reason_id" value="{{ (int) $line->tax_exemption_reason_id }}">

                                            <label for="notes-{{ $line->id }}" class="form-label mb-1">
                                                Observações da linha
                                            </label>

                                            <textarea
                                                name="notes"
                                                id="notes-{{ $line->id }}"
                                                rows="3"
                                                class="form-control"
                                                placeholder="Escreve aqui as observações desta linha"
                                            >{{ $line->notes }}</textarea>

                                            <div class="mt-2">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    Guardar observações
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @elseif (!empty($line->notes))
                                <tr class="table-light">
                                    <td colspan="11">
                                        <strong>Observações:</strong><br>
                                        {!! nl2br(e($line->notes)) !!}
                                    </td>
                                </tr>
                            @endcan
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="@can('budgets.update') 9 @else 8 @endcan" class="text-end">
                                Subtotal
                            </th>
                            <th colspan="2">
                                {{ number_format((float) $budget->subtotal, 2, ',', '.') }} €
                            </th>
                        </tr>
                        <tr>
                            <th colspan="@can('budgets.update') 9 @else 8 @endcan" class="text-end">
                                Desconto
                            </th>
                            <th colspan="2">
                                {{ number_format((float) $budget->discount_total, 2, ',', '.') }} €
                            </th>
                        </tr>
                        <tr>
                            <th colspan="@can('budgets.update') 9 @else 8 @endcan" class="text-end">
                                IVA
                            </th>
                            <th colspan="2">
                                {{ number_format((float) $budget->tax_total, 2, ',', '.') }} €
                            </th>
                        </tr>
                        <tr>
                            <th colspan="@can('budgets.update') 9 @else 8 @endcan" class="text-end">
                                Total
                            </th>
                            <th colspan="2">
                                {{ number_format((float) $budget->total, 2, ',', '.') }} €
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-muted">
                Este orçamento ainda não tem artigos adicionados.
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tax-rate-select').forEach(function (select) {
        const targetSelector = select.getAttribute('data-target');
        const wrapper = document.querySelector(targetSelector);

        if (!wrapper) {
            return;
        }

        const reasonSelect = wrapper.querySelector('.tax-exemption-reason-select');

        const toggleReasonField = function () {
            const selectedOption = select.options[select.selectedIndex];
            const isExempt = selectedOption?.dataset?.isExempt === '1';
            const defaultReasonId = selectedOption?.dataset?.defaultReasonId || '';

            wrapper.style.display = isExempt ? 'block' : 'none';

            if (!isExempt && reasonSelect) {
                reasonSelect.value = '';
            }

            if (isExempt && reasonSelect && !reasonSelect.value && defaultReasonId) {
                reasonSelect.value = defaultReasonId;
            }
        };

        select.addEventListener('change', toggleReasonField);
        toggleReasonField();
    });
});
</script>
