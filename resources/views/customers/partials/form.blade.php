<div class="row g-3">
    @if(isset($customer) && $customer)
        <div class="col-md-3">
            <label class="form-label">Código</label>
            <input type="text" class="form-control" value="{{ $customer->code }}" disabled>
        </div>
    @else
        <div class="col-md-3">
            <label class="form-label">Código</label>
            <input type="text" class="form-control" value="Gerado automaticamente" disabled>
        </div>
    @endif

    <div class="col-md-5">
        <label class="form-label">Nome</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name ?? '') }}" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select name="type" class="form-select" required>
            <option value="private" @selected(old('type', $customer->type ?? 'private') === 'private')>Particular</option>
            <option value="company" @selected(old('type', $customer->type ?? '') === 'company')>Empresa</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">NIF (opcional)</label>
        <input type="text" name="nif" class="form-control" value="{{ old('nif', $customer->nif ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Telemóvel</label>
        <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $customer->mobile ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Telefone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Pessoa de contacto</label>
        <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $customer->contact_person ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            <option value="active" @selected(old('status', $customer->status ?? 'active') === 'active')>Ativo</option>
            <option value="inactive" @selected(old('status', $customer->status ?? '') === 'inactive')>Inativo</option>
            <option value="prospect" @selected(old('status', $customer->status ?? '') === 'prospect')>Prospect</option>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Morada</label>
        <input type="text" name="address_line_1" class="form-control" value="{{ old('address_line_1', $customer->address_line_1 ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">Morada complementar</label>
        <input type="text" name="address_line_2" class="form-control" value="{{ old('address_line_2', $customer->address_line_2 ?? '') }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Código Postal</label>
        <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $customer->postal_code ?? '') }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Cidade</label>
        <input type="text" name="city" class="form-control" value="{{ old('city', $customer->city ?? '') }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">País</label>
        <input type="text" name="country" class="form-control" value="{{ old('country', $customer->country ?? 'Portugal') }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Último contacto</label>
        <input
            type="datetime-local"
            name="last_contact_at"
            class="form-control"
            value="{{ old('last_contact_at', isset($customer) && $customer?->last_contact_at ? $customer->last_contact_at->format('Y-m-d\TH:i') : '') }}"
        >
    </div>

    <div class="col-md-3">
        <label class="form-label">Desconto padrão (%)</label>
        <input type="number" step="0.01" name="default_discount" class="form-control" value="{{ old('default_discount', $customer->default_discount ?? 0) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Prazo pagamento (dias)</label>
        <input type="number" name="payment_terms_days" class="form-control" value="{{ old('payment_terms_days', $customer->payment_terms_days ?? 0) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Origem</label>
        <input type="text" name="source" class="form-control" value="{{ old('source', $customer->source ?? '') }}">
    </div>

    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                class="form-check-input"
                id="is_active"
                @checked(old('is_active', $customer->is_active ?? true))
            >
            <label class="form-check-label" for="is_active">Ativo</label>
        </div>
    </div>

    <div class="col-12">
        <label class="form-label">Notas</label>
        <textarea name="notes" rows="4" class="form-control">{{ old('notes', $customer->notes ?? '') }}</textarea>
    </div>
</div>
