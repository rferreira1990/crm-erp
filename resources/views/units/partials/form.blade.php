<div class="row">
    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="code" class="form-label">Código <span class="text-danger">*</span></label>
            <input
                type="text"
                name="code"
                id="code"
                class="form-control @error('code') is-invalid @enderror"
                value="{{ old('code', $unit->code ?? '') }}"
                required
            >
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-5">
        <div class="form-group mb-3">
            <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
            <input
                type="text"
                name="name"
                id="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $unit->name ?? '') }}"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group mb-3">
            <label for="factor" class="form-label">Fator <span class="text-danger">*</span></label>
            <input
                type="number"
                step="0.001"
                min="0.001"
                name="factor"
                id="factor"
                class="form-control @error('factor') is-invalid @enderror"
                value="{{ old('factor', $unit->factor ?? '1.000') }}"
                required
            >
            @error('factor')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
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
                    {{ old('is_active', $unit->is_active ?? true) ? 'checked' : '' }}
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

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        Guardar
    </button>

    <a href="{{ route('units.index') }}" class="btn btn-default">
        Cancelar
    </a>
</div>
