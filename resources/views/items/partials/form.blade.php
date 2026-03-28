<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Código</label>
        <input type="text" class="form-control" value="{{ $item->code ?: 'Gerado automaticamente' }}" disabled>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Nome <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $item->name) }}">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Nome curto</label>
        <input type="text" name="short_name" class="form-control @error('short_name') is-invalid @enderror"
               value="{{ old('short_name', $item->short_name) }}">
        @error('short_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Tipo <span class="text-danger">*</span></label>
        <select name="type" id="type" class="form-control @error('type') is-invalid @enderror">
            <option value="product" {{ old('type', $item->type) === 'product' ? 'selected' : '' }}>Produto</option>
            <option value="service" {{ old('type', $item->type) === 'service' ? 'selected' : '' }}>Serviço</option>
        </select>
        @error('type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Família</label>
        <select name="family_id" class="form-control @error('family_id') is-invalid @enderror">
            <option value="">-- Selecionar --</option>
            @foreach ($families as $family)
                <option value="{{ $family->id }}" {{ (string) old('family_id', $item->family_id) === (string) $family->id ? 'selected' : '' }}>
                    {{ $family->name }}
                </option>
            @endforeach
        </select>
        @error('family_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Marca</label>
        <select name="brand_id" class="form-control @error('brand_id') is-invalid @enderror">
            <option value="">-- Selecionar --</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" {{ (string) old('brand_id', $item->brand_id) === (string) $brand->id ? 'selected' : '' }}>
                    {{ $brand->name }}
                </option>
            @endforeach
        </select>
        @error('brand_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Unidade <span class="text-danger">*</span></label>
        <select name="unit_id" class="form-control @error('unit_id') is-invalid @enderror">
            <option value="">-- Selecionar --</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" {{ (string) old('unit_id', $item->unit_id) === (string) $unit->id ? 'selected' : '' }}>
                    {{ $unit->name }}
                </option>
            @endforeach
        </select>
        @error('unit_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">IVA <span class="text-danger">*</span></label>
        <select name="tax_rate_id" class="form-control @error('tax_rate_id') is-invalid @enderror">
            <option value="">-- Selecionar --</option>
            @foreach ($taxRates as $taxRate)
                <option value="{{ $taxRate->id }}" {{ (string) old('tax_rate_id', $item->tax_rate_id) === (string) $taxRate->id ? 'selected' : '' }}>
                    {{ $taxRate->name }}
                </option>
            @endforeach
        </select>
        @error('tax_rate_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Código de barras</label>
        <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror"
               value="{{ old('barcode', $item->barcode) }}">
        @error('barcode')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Ref. fornecedor</label>
        <input type="text" name="supplier_reference" class="form-control @error('supplier_reference') is-invalid @enderror"
               value="{{ old('supplier_reference', $item->supplier_reference) }}">
        @error('supplier_reference')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3 d-flex align-items-end">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   id="is_active" {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">
                Ativo
            </label>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Preço de custo</label>
        <input type="number" step="0.01" min="0" name="cost_price"
               class="form-control @error('cost_price') is-invalid @enderror"
               value="{{ old('cost_price', $item->cost_price) }}">
        @error('cost_price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Preço de venda</label>
        <input type="number" step="0.01" min="0" name="sale_price"
               class="form-control @error('sale_price') is-invalid @enderror"
               value="{{ old('sale_price', $item->sale_price) }}">
        @error('sale_price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Desconto máx. (%)</label>
        <input type="number" step="0.01" min="0" max="100" name="max_discount_percent"
               class="form-control @error('max_discount_percent') is-invalid @enderror"
               value="{{ old('max_discount_percent', $item->max_discount_percent) }}">
        @error('max_discount_percent')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row" id="stock-fields">
    <div class="col-md-3 mb-3 d-flex align-items-end">
        <div class="form-check">
            <input type="hidden" name="tracks_stock" value="0">
            <input class="form-check-input" type="checkbox" name="tracks_stock" value="1"
                   id="tracks_stock" {{ old('tracks_stock', $item->tracks_stock) ? 'checked' : '' }}>
            <label class="form-check-label" for="tracks_stock">
                Controla stock
            </label>
        </div>
    </div>

    <div class="col-md-2 mb-3">
        <label class="form-label">Stock mínimo</label>
        <input type="number" step="0.01" min="0" name="min_stock"
               class="form-control @error('min_stock') is-invalid @enderror"
               value="{{ old('min_stock', $item->min_stock) }}">
        @error('min_stock')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2 mb-3">
        <label class="form-label">Stock máximo</label>
        <input type="number" step="0.01" min="0" name="max_stock"
               class="form-control @error('max_stock') is-invalid @enderror"
               value="{{ old('max_stock', $item->max_stock) }}">
        @error('max_stock')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2 mb-3">
        <label class="form-label">Stock atual</label>
        <input type="number" step="0.001" min="0" name="current_stock"
               class="form-control @error('current_stock') is-invalid @enderror"
               value="{{ old('current_stock', $item->current_stock) }}">
        @error('current_stock')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3 d-flex align-items-end">
        <div class="form-check">
            <input type="hidden" name="stock_alert" value="0">
            <input class="form-check-input" type="checkbox" name="stock_alert" value="1"
                   id="stock_alert" {{ old('stock_alert', $item->stock_alert) ? 'checked' : '' }}>
            <label class="form-check-label" for="stock_alert">
                Alerta stock baixo
            </label>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <label class="form-label">Descrição</label>
        <textarea name="description" rows="4"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $item->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeField = document.getElementById('type');
        const stockFields = document.getElementById('stock-fields');
        const tracksStock = document.getElementById('tracks_stock');
        const stockAlert = document.getElementById('stock_alert');

        function toggleStockFields() {
            const isService = typeField.value === 'service';

            stockFields.style.display = isService ? 'none' : 'flex';

            if (isService) {
                tracksStock.checked = false;
                stockAlert.checked = false;
            }
        }

        typeField.addEventListener('change', toggleStockFields);
        toggleStockFields();
    });
</script>
@endpush
