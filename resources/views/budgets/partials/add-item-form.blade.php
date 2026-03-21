<div class="card shadow-sm mb-4">
    <div class="card-header">
        <strong>Adicionar artigo ao orçamento</strong>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('budgets.items.store', $budget) }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="item_id" class="form-label">Artigo *</label>
                    <select
                        name="item_id"
                        id="item_id"
                        class="form-select @error('item_id') is-invalid @enderror"
                        required
                    >
                        <option value="">Selecionar artigo</option>
                        @foreach ($availableItems as $item)
                            <option
                                value="{{ $item->id }}"
                                {{ old('item_id') == $item->id ? 'selected' : '' }}
                            >
                                {{ $item->code }} - {{ $item->name }}
                                @if ($item->sale_price !== null)
                                    | {{ number_format((float) $item->sale_price, 2, ',', '.') }} €
                                @endif
                                @if ($item->taxRate)
                                    | IVA {{ number_format((float) $item->taxRate->percent, 2, ',', '.') }}%
                                @endif
                            </option>
                        @endforeach
                    </select>

                    @error('item_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="quantity" class="form-label">Quantidade *</label>
                    <input
                        type="number"
                        name="quantity"
                        id="quantity"
                        class="form-control @error('quantity') is-invalid @enderror"
                        value="{{ old('quantity', 1) }}"
                        min="0.001"
                        step="0.001"
                        required
                    >

                    @error('quantity')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="discount_percent" class="form-label">Desconto (%)</label>
                    <input
                        type="number"
                        name="discount_percent"
                        id="discount_percent"
                        class="form-control @error('discount_percent') is-invalid @enderror"
                        value="{{ old('discount_percent', 0) }}"
                        min="0"
                        max="100"
                        step="0.01"
                    >

                    @error('discount_percent')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    Adicionar artigo
                </button>
            </div>
        </form>
    </div>
</div>
