<div class="row">
    <div class="col-md-2">
        <div class="form-group mb-3">
            <label class="form-label">Código</label>
            <input type="text" class="form-control" value="Automático" disabled>
            <small class="text-muted">Gerado ao guardar.</small>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group mb-3">
            <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
            <input
                type="text"
                name="name"
                id="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name') }}"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="short_name" class="form-label">Nome curto</label>
            <input
                type="text"
                name="short_name"
                id="short_name"
                class="form-control @error('short_name') is-invalid @enderror"
                value="{{ old('short_name') }}"
            >
            @error('short_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group mb-3">
            <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
            <select
                name="type"
                id="type"
                class="form-control @error('type') is-invalid @enderror"
                required
            >
                <option value="product" {{ old('type', 'product') === 'product' ? 'selected' : '' }}>Produto</option>
                <option value="service" {{ old('type') === 'service' ? 'selected' : '' }}>Serviço</option>
            </select>
            @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-1">
        <div class="form-group mb-3">
            <label class="form-label d-block">Ativo</label>
            <div class="form-check form-switch mt-2">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="is_active"
                    name="is_active"
                    value="1"
                    {{ old('is_active', true) ? 'checked' : '' }}
                >
            </div>
            @error('is_active')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group mb-3">
    <label for="description" class="form-label">Descrição</label>
    <textarea
        name="description"
        id="description"
        rows="3"
        class="form-control @error('description') is-invalid @enderror"
    >{{ old('description') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="family_id" class="form-label">Família</label>
            <select name="family_id" id="family_id" class="form-control @error('family_id') is-invalid @enderror">
                <option value="">Selecionar...</option>
                @foreach($families as $family)
                    <option value="{{ $family->id }}" {{ old('family_id') == $family->id ? 'selected' : '' }}>
                        {{ $family->name }}
                    </option>
                @endforeach
            </select>
            @error('family_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="brand_id" class="form-label">Marca</label>
            <select name="brand_id" id="brand_id" class="form-control @error('brand_id') is-invalid @enderror">
                <option value="">Selecionar...</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                        {{ $brand->name }}
                    </option>
                @endforeach
            </select>
            @error('brand_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="unit_id" class="form-label">Unidade <span class="text-danger">*</span></label>
            <select
                name="unit_id"
                id="unit_id"
                class="form-control @error('unit_id') is-invalid @enderror"
                required
            >
                <option value="">Selecionar...</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                        {{ $unit->code }} - {{ $unit->name }}
                    </option>
                @endforeach
            </select>
            @error('unit_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="tax_rate_id" class="form-label">IVA</label>
            <select name="tax_rate_id" id="tax_rate_id" class="form-control @error('tax_rate_id') is-invalid @enderror">
                <option value="">Selecionar...</option>
                @foreach($taxRates as $taxRate)
                    <option value="{{ $taxRate->id }}" {{ old('tax_rate_id') == $taxRate->id ? 'selected' : '' }}>
                        {{ $taxRate->name }} ({{ number_format((float) $taxRate->percent, 2, ',', '.') }}%)
                    </option>
                @endforeach
            </select>
            @error('tax_rate_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="barcode" class="form-label">Código de barras</label>
            <input
                type="text"
                name="barcode"
                id="barcode"
                class="form-control @error('barcode') is-invalid @enderror"
                value="{{ old('barcode') }}"
            >
            @error('barcode')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="supplier_reference" class="form-label">Ref. fornecedor</label>
            <input
                type="text"
                name="supplier_reference"
                id="supplier_reference"
                class="form-control @error('supplier_reference') is-invalid @enderror"
                value="{{ old('supplier_reference') }}"
            >
            @error('supplier_reference')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group mb-3">
            <label for="cost_price" class="form-label">Preço custo</label>
            <input
                type="number"
                step="0.01"
                min="0"
                name="cost_price"
                id="cost_price"
                class="form-control @error('cost_price') is-invalid @enderror"
                value="{{ old('cost_price', '0.00') }}"
            >
            @error('cost_price')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group mb-3">
            <label for="sale_price" class="form-label">Preço venda <span class="text-danger">*</span></label>
            <input
                type="number"
                step="0.01"
                min="0"
                name="sale_price"
                id="sale_price"
                class="form-control @error('sale_price') is-invalid @enderror"
                value="{{ old('sale_price', '0.00') }}"
                required
            >
            @error('sale_price')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group mb-3">
            <label for="max_discount_percent" class="form-label">Desc. máx. %</label>
            <input
                type="number"
                step="0.01"
                min="0"
                max="100"
                name="max_discount_percent"
                id="max_discount_percent"
                class="form-control @error('max_discount_percent') is-invalid @enderror"
                value="{{ old('max_discount_percent') }}"
            >
            @error('max_discount_percent')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row" id="stock-fields">
    <div class="col-md-3">
        <div class="form-group mb-3">
            <label class="form-label d-block">Controla stock</label>
            <div class="form-check form-switch mt-2">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="tracks_stock"
                    name="tracks_stock"
                    value="1"
                    {{ old('tracks_stock', true) ? 'checked' : '' }}
                >
                <label class="form-check-label" for="tracks_stock">Sim</label>
            </div>
            @error('tracks_stock')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="min_stock" class="form-label">Stock mínimo</label>
            <input
                type="number"
                step="0.001"
                min="0"
                name="min_stock"
                id="min_stock"
                class="form-control @error('min_stock') is-invalid @enderror"
                value="{{ old('min_stock', '0.000') }}"
            >
            @error('min_stock')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="max_stock" class="form-label">Stock máximo</label>
            <input
                type="number"
                step="0.001"
                min="0"
                name="max_stock"
                id="max_stock"
                class="form-control @error('max_stock') is-invalid @enderror"
                value="{{ old('max_stock') }}"
            >
            @error('max_stock')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label class="form-label d-block">Alerta stock</label>
            <div class="form-check form-switch mt-2">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="stock_alert"
                    name="stock_alert"
                    value="1"
                    {{ old('stock_alert') ? 'checked' : '' }}
                >
                <label class="form-check-label" for="stock_alert">Ativo</label>
            </div>
            @error('stock_alert')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('items.index') }}" class="btn btn-default">Cancelar</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeField = document.getElementById('type');
    const stockFields = document.getElementById('stock-fields');
    const tracksStock = document.getElementById('tracks_stock');
    const minStock = document.getElementById('min_stock');
    const maxStock = document.getElementById('max_stock');
    const stockAlert = document.getElementById('stock_alert');

    function toggleStockFields() {
        const isService = typeField.value === 'service';

        stockFields.style.display = isService ? 'none' : 'flex';

        if (isService) {
            tracksStock.checked = false;
            minStock.value = '0.000';
            maxStock.value = '';
            stockAlert.checked = false;
        }
    }

    typeField.addEventListener('change', toggleStockFields);
    toggleStockFields();
});
</script>
@endpush
