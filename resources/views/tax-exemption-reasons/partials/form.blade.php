<div class="row">
    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="code" class="form-label">Código <span class="text-danger">*</span></label>
            <input
                type="text"
                name="code"
                id="code"
                class="form-control @error('code') is-invalid @enderror"
                value="{{ old('code', $tax_exemption_reason->code ?? '') }}"
                maxlength="10"
                required
            >
            @error('code')
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
                    {{ old('is_active', $tax_exemption_reason->is_active ?? true) ? 'checked' : '' }}
                >
                <label class="form-check-label" for="is_active">
                    Ativo
                </label>
            </div>
            @error('is_active')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group mb-3">
    <label for="description" class="form-label">Descrição <span class="text-danger">*</span></label>
    <textarea
        name="description"
        id="description"
        rows="3"
        class="form-control @error('description') is-invalid @enderror"
        required
    >{{ old('description', $tax_exemption_reason->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="invoice_note" class="form-label">Nota para fatura</label>
    <textarea
        name="invoice_note"
        id="invoice_note"
        rows="3"
        class="form-control @error('invoice_note') is-invalid @enderror"
    >{{ old('invoice_note', $tax_exemption_reason->invoice_note ?? '') }}</textarea>
    @error('invoice_note')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="legal_reference" class="form-label">Referência legal</label>
    <textarea
        name="legal_reference"
        id="legal_reference"
        rows="3"
        class="form-control @error('legal_reference') is-invalid @enderror"
    >{{ old('legal_reference', $tax_exemption_reason->legal_reference ?? '') }}</textarea>
    @error('legal_reference')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        Guardar
    </button>

    <a href="{{ route('tax-exemption-reasons.index') }}" class="btn btn-default">
        Cancelar
    </a>
</div>
