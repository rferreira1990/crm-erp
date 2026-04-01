<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
            <input
                type="text"
                name="name"
                id="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $item_family->name ?? '') }}"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="parent_id" class="form-label">Familia pai</label>
            <select
                name="parent_id"
                id="parent_id"
                class="form-control @error('parent_id') is-invalid @enderror"
            >
                <option value="">-- Familia raiz --</option>
                @foreach(($parentOptions ?? collect()) as $parentOption)
                    <option
                        value="{{ $parentOption->id }}"
                        {{ (string) old('parent_id', $item_family->parent_id ?? '') === (string) $parentOption->id ? 'selected' : '' }}
                    >
                        {{ $parentOption->path_label ?? $parentOption->name }}
                    </option>
                @endforeach
            </select>
            @error('parent_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted d-block mt-1">
                Deixa vazio para criar uma familia principal.
            </small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-group mb-3">
            <label for="description" class="form-label">Descricao</label>
            <textarea
                name="description"
                id="description"
                rows="4"
                class="form-control @error('description') is-invalid @enderror"
            >{{ old('description', $item_family->description ?? '') }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
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
                    {{ old('is_active', $item_family->is_active ?? true) ? 'checked' : '' }}
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

    <a href="{{ route('item-families.index') }}" class="btn btn-default">
        Cancelar
    </a>
</div>
