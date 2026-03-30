@php
    $preferredContactMethod = old('preferred_contact_method', $supplier->preferred_contact_method ?? '');
@endphp

<div class="row g-3">
    <div class="col-12">
        <h5 class="mb-1">Dados gerais</h5>
        <p class="text-muted mb-0">Identificacao e contacto principal do fornecedor.</p>
    </div>

    @if (isset($supplier) && $supplier)
        <div class="col-md-3">
            <label for="code" class="form-label">Codigo</label>
            <input
                type="text"
                name="code"
                id="code"
                class="form-control @error('code') is-invalid @enderror"
                value="{{ old('code', $supplier->code) }}"
                maxlength="50"
            >
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @else
        <div class="col-md-3">
            <label class="form-label">Codigo</label>
            <input type="text" class="form-control" value="Gerado automaticamente" disabled>
        </div>
    @endif

    <div class="col-md-6">
        <label for="name" class="form-label">Nome</label>
        <input
            type="text"
            name="name"
            id="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $supplier->name ?? '') }}"
            maxlength="255"
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="tax_number" class="form-label">NIF</label>
        <input
            type="text"
            name="tax_number"
            id="tax_number"
            class="form-control @error('tax_number') is-invalid @enderror"
            value="{{ old('tax_number', $supplier->tax_number ?? '') }}"
            maxlength="20"
        >
        @error('tax_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="email" class="form-label">Email</label>
        <input
            type="email"
            name="email"
            id="email"
            class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $supplier->email ?? '') }}"
            maxlength="150"
        >
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="phone" class="form-label">Telefone</label>
        <input
            type="text"
            name="phone"
            id="phone"
            class="form-control @error('phone') is-invalid @enderror"
            value="{{ old('phone', $supplier->phone ?? '') }}"
            maxlength="30"
        >
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="mobile" class="form-label">Telemovel</label>
        <input
            type="text"
            name="mobile"
            id="mobile"
            class="form-control @error('mobile') is-invalid @enderror"
            value="{{ old('mobile', $supplier->mobile ?? '') }}"
            maxlength="30"
        >
        @error('mobile')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="contact_person" class="form-label">Pessoa de contacto</label>
        <input
            type="text"
            name="contact_person"
            id="contact_person"
            class="form-control @error('contact_person') is-invalid @enderror"
            value="{{ old('contact_person', $supplier->contact_person ?? '') }}"
            maxlength="150"
        >
        @error('contact_person')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="website" class="form-label">Website</label>
        <input
            type="text"
            name="website"
            id="website"
            class="form-control @error('website') is-invalid @enderror"
            value="{{ old('website', $supplier->website ?? '') }}"
            maxlength="255"
        >
        @error('website')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="external_reference" class="form-label">Referencia externa</label>
        <input
            type="text"
            name="external_reference"
            id="external_reference"
            class="form-control @error('external_reference') is-invalid @enderror"
            value="{{ old('external_reference', $supplier->external_reference ?? '') }}"
            maxlength="100"
        >
        @error('external_reference')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 mt-2 pt-2 border-top">
        <h5 class="mb-1">Morada</h5>
        <p class="text-muted mb-0">Morada principal para futuras encomendas e documentos de compra.</p>
    </div>

    <div class="col-md-6">
        <label for="address" class="form-label">Morada</label>
        <input
            type="text"
            name="address"
            id="address"
            class="form-control @error('address') is-invalid @enderror"
            value="{{ old('address', $supplier->address ?? '') }}"
            maxlength="255"
        >
        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="postal_code" class="form-label">Codigo postal</label>
        <input
            type="text"
            name="postal_code"
            id="postal_code"
            class="form-control @error('postal_code') is-invalid @enderror"
            value="{{ old('postal_code', $supplier->postal_code ?? '') }}"
            maxlength="20"
        >
        @error('postal_code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="city" class="form-label">Cidade</label>
        <input
            type="text"
            name="city"
            id="city"
            class="form-control @error('city') is-invalid @enderror"
            value="{{ old('city', $supplier->city ?? '') }}"
            maxlength="120"
        >
        @error('city')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="country" class="form-label">Pais</label>
        <input
            type="text"
            name="country"
            id="country"
            class="form-control @error('country') is-invalid @enderror"
            value="{{ old('country', $supplier->country ?? 'Portugal') }}"
            maxlength="120"
        >
        @error('country')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 mt-2 pt-2 border-top">
        <h5 class="mb-1">Condicoes comerciais e financeiras</h5>
        <p class="text-muted mb-0">Defaults para reutilizacao futura no modulo de compras.</p>
    </div>

    <div class="col-md-4">
        <label for="payment_term_id" class="form-label">Condicao de pagamento</label>
        <select name="payment_term_id" id="payment_term_id" class="form-select @error('payment_term_id') is-invalid @enderror">
            <option value="">-</option>
            @foreach ($paymentTerms as $paymentTerm)
                <option value="{{ $paymentTerm->id }}" {{ (int) old('payment_term_id', $supplier->payment_term_id ?? 0) === (int) $paymentTerm->id ? 'selected' : '' }}>
                    {{ $paymentTerm->displayLabel() }}
                </option>
            @endforeach
        </select>
        @error('payment_term_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="default_tax_rate_id" class="form-label">Taxa IVA por defeito</label>
        <select name="default_tax_rate_id" id="default_tax_rate_id" class="form-select @error('default_tax_rate_id') is-invalid @enderror">
            <option value="">-</option>
            @foreach ($taxRates as $taxRate)
                <option value="{{ $taxRate->id }}" {{ (int) old('default_tax_rate_id', $supplier->default_tax_rate_id ?? 0) === (int) $taxRate->id ? 'selected' : '' }}>
                    {{ $taxRate->name }} ({{ number_format((float) $taxRate->percent, 2, ',', '.') }}%)
                </option>
            @endforeach
        </select>
        @error('default_tax_rate_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="default_discount_percent" class="form-label">Desconto por defeito (%)</label>
        <input
            type="number"
            name="default_discount_percent"
            id="default_discount_percent"
            class="form-control @error('default_discount_percent') is-invalid @enderror"
            value="{{ old('default_discount_percent', $supplier->default_discount_percent ?? 0) }}"
            min="0"
            max="100"
            step="0.01"
        >
        @error('default_discount_percent')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="minimum_order_value" class="form-label">Valor minimo encomenda (EUR)</label>
        <input
            type="number"
            name="minimum_order_value"
            id="minimum_order_value"
            class="form-control @error('minimum_order_value') is-invalid @enderror"
            value="{{ old('minimum_order_value', $supplier->minimum_order_value ?? '') }}"
            min="0"
            step="0.01"
        >
        @error('minimum_order_value')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="free_shipping_threshold" class="form-label">Portes gratis a partir de (EUR)</label>
        <input
            type="number"
            name="free_shipping_threshold"
            id="free_shipping_threshold"
            class="form-control @error('free_shipping_threshold') is-invalid @enderror"
            value="{{ old('free_shipping_threshold', $supplier->free_shipping_threshold ?? '') }}"
            min="0"
            step="0.01"
        >
        @error('free_shipping_threshold')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="preferred_payment_method" class="form-label">Metodo de pagamento preferido</label>
        <input
            type="text"
            name="preferred_payment_method"
            id="preferred_payment_method"
            class="form-control @error('preferred_payment_method') is-invalid @enderror"
            value="{{ old('preferred_payment_method', $supplier->preferred_payment_method ?? '') }}"
            maxlength="100"
        >
        @error('preferred_payment_method')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 mt-2 pt-2 border-top">
        <h5 class="mb-1">Dados operacionais</h5>
        <p class="text-muted mb-0">Parametros para agilizar processos de compra futuros.</p>
    </div>

    <div class="col-md-3">
        <label for="lead_time_days" class="form-label">Lead time (dias)</label>
        <input
            type="number"
            name="lead_time_days"
            id="lead_time_days"
            class="form-control @error('lead_time_days') is-invalid @enderror"
            value="{{ old('lead_time_days', $supplier->lead_time_days ?? '') }}"
            min="0"
            max="3650"
        >
        @error('lead_time_days')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-5">
        <label for="habitual_order_email" class="form-label">Email habitual de encomenda</label>
        <input
            type="email"
            name="habitual_order_email"
            id="habitual_order_email"
            class="form-control @error('habitual_order_email') is-invalid @enderror"
            value="{{ old('habitual_order_email', $supplier->habitual_order_email ?? '') }}"
            maxlength="150"
        >
        @error('habitual_order_email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="preferred_contact_method" class="form-label">Metodo de contacto preferido</label>
        <select
            name="preferred_contact_method"
            id="preferred_contact_method"
            class="form-select @error('preferred_contact_method') is-invalid @enderror"
        >
            <option value="">-</option>
            <option value="email" {{ $preferredContactMethod === 'email' ? 'selected' : '' }}>Email</option>
            <option value="phone" {{ $preferredContactMethod === 'phone' ? 'selected' : '' }}>Telefone</option>
            <option value="mobile" {{ $preferredContactMethod === 'mobile' ? 'selected' : '' }}>Telemovel</option>
        </select>
        @error('preferred_contact_method')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="delivery_instructions" class="form-label">Instrucoes de entrega</label>
        <textarea
            name="delivery_instructions"
            id="delivery_instructions"
            rows="3"
            class="form-control @error('delivery_instructions') is-invalid @enderror"
        >{{ old('delivery_instructions', $supplier->delivery_instructions ?? '') }}</textarea>
        @error('delivery_instructions')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="default_notes_for_purchases" class="form-label">Notas por defeito para compras</label>
        <textarea
            name="default_notes_for_purchases"
            id="default_notes_for_purchases"
            rows="3"
            class="form-control @error('default_notes_for_purchases') is-invalid @enderror"
        >{{ old('default_notes_for_purchases', $supplier->default_notes_for_purchases ?? '') }}</textarea>
        @error('default_notes_for_purchases')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 mt-2 pt-2 border-top">
        <h5 class="mb-1">Observacoes</h5>
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notas internas</label>
        <textarea
            name="notes"
            id="notes"
            rows="4"
            class="form-control @error('notes') is-invalid @enderror"
        >{{ old('notes', $supplier->notes ?? '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check mt-2">
            <input
                type="checkbox"
                name="is_active"
                id="is_active"
                value="1"
                class="form-check-input @error('is_active') is-invalid @enderror"
                @checked(old('is_active', $supplier->is_active ?? true))
            >
            <label for="is_active" class="form-check-label">Fornecedor ativo</label>
            @error('is_active')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

