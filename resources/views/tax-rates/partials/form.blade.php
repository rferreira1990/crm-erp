<div class="row">
    <div class="col-md-5">
        <div class="form-group mb-3">
            <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
            <input
                type="text"
                name="name"
                id="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $tax_rate->name ?? '') }}"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group mb-3">
            <label for="percent" class="form-label">Percentagem <span class="text-danger">*</span></label>
            <input
                type="number"
                step="0.01"
                min="0"
                max="100"
                name="percent"
                id="percent"
                class="form-control @error('percent') is-invalid @enderror"
                value="{{ old('percent', isset($tax_rate) ? number_format((float) $tax_rate->percent, 2, '.', '') : '23.00') }}"
                required
            >
            @error('percent')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group mb-3">
            <label for="saft_code" class="form-label">Código SAF-T <span class="text-danger">*</span></label>
            <input
                type="text"
                name="saft_code"
                id="saft_code"
                class="form-control @error('saft_code') is-invalid @enderror"
                value="{{ old('saft_code', $tax_rate->saft_code ?? '') }}"
                maxlength="10"
                required
            >
            @error('saft_code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="country_code" class="form-label">País <span class="text-danger">*</span></label>
            <input
                type="text"
                name="country_code"
                id="country_code"
                class="form-control @error('country_code') is-invalid @enderror"
                value="{{ old('country_code', $tax_rate->country_code ?? 'PT') }}"
                maxlength="2"
                required
            >
            @error('country_code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="sort_order" class="form-label">Ordem</label>
            <input
                type="number"
                min="0"
                name="sort_order"
                id="sort_order"
                class="form-control @error('sort_order') is-invalid @enderror"
                value="{{ old('sort_order', $tax_rate->sort_order ?? 0) }}"
            >
            @error('sort_order')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label class="form-label d-block">Estado</label>
            <div class="form-check form-switch mt-2">
                <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="is_active"
                    name="is_active"
                    value="1"
                    {{ old('is_active', $tax_rate->is_active ?? true) ? 'checked' : '' }}
                >
                <label class="form-check-label" for="is_active">
                    Ativa
                </label>
            </div>
            @error('is_active')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

@if(isset($tax_rate))
    <div class="row mt-2">
        <div class="col-md-12">
            <div class="alert alert-info mb-3">
                <strong>Info:</strong>
                O campo <strong>isenta</strong> é calculado automaticamente com base na percentagem.
                Se a percentagem for 0, a taxa será considerada isenta.
            </div>
        </div>
    </div>
@endif

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        Guardar
    </button>

    <a href="{{ route('tax-rates.index') }}" class="btn btn-default">
        Cancelar
    </a>
</div>
