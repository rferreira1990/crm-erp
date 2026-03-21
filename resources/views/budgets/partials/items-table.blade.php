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
                            <th>Qtd.</th>
                            <th>Preço Unit.</th>
                            <th>Desc. %</th>
                            <th>IVA %</th>
                            <th>Subtotal</th>
                            <th>IVA</th>
                            <th>Total</th>
                            @can('budgets.update')
                                <th>Ações</th>
                            @endcan
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($budget->items as $line)
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

                                <td style="min-width: 120px;">
                                    @can('budgets.update')
                                        <form
                                            method="POST"
                                            action="{{ route('budgets.items.update', [$budget, $line]) }}"
                                            class="d-flex flex-column gap-2"
                                        >
                                            @csrf
                                            @method('PUT')

                                            <input
                                                type="number"
                                                name="quantity"
                                                class="form-control form-control-sm @error('quantity') is-invalid @enderror"
                                                value="{{ old('quantity', number_format((float) $line->quantity, 3, '.', '')) }}"
                                                min="0.001"
                                                step="0.001"
                                                required
                                            >
                                </td>

                                <td>{{ number_format((float) $line->unit_price, 2, ',', '.') }} €</td>

                                <td style="min-width: 120px;">
                                            <input
                                                type="number"
                                                name="discount_percent"
                                                class="form-control form-control-sm @error('discount_percent') is-invalid @enderror"
                                                value="{{ old('discount_percent', number_format((float) $line->discount_percent, 2, '.', '')) }}"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                            >
                                </td>

                                <td>{{ number_format((float) $line->tax_percent, 2, ',', '.') }}%</td>
                                <td>{{ number_format((float) $line->subtotal, 2, ',', '.') }} €</td>
                                <td>{{ number_format((float) $line->tax_total, 2, ',', '.') }} €</td>
                                <td><strong>{{ number_format((float) $line->total, 2, ',', '.') }} €</strong></td>

                                <td style="min-width: 150px;">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    Guardar
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
                                </td>
                                    @else
                                        <td>{{ number_format((float) $line->quantity, 3, ',', '.') }}</td>
                                        <td>{{ number_format((float) $line->unit_price, 2, ',', '.') }} €</td>
                                        <td>{{ number_format((float) $line->discount_percent, 2, ',', '.') }}%</td>
                                        <td>{{ number_format((float) $line->tax_percent, 2, ',', '.') }}%</td>
                                        <td>{{ number_format((float) $line->subtotal, 2, ',', '.') }} €</td>
                                        <td>{{ number_format((float) $line->tax_total, 2, ',', '.') }} €</td>
                                        <td><strong>{{ number_format((float) $line->total, 2, ',', '.') }} €</strong></td>
                                    @endcan
                            </tr>
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
