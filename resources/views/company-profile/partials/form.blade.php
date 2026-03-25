<section class="card mb-4">
    <header class="card-header">
        <h2 class="card-title mb-0">Dados Gerais da Empresa</h2>
    </header>

    <div class="card-body">
        <div class="row">
            <div class="col-md-12 form-group">
                <label for="company_name">Designação</label>
                <input
                    type="text"
                    name="company_name"
                    id="company_name"
                    class="form-control @error('company_name') is-invalid @enderror"
                    value="{{ old('company_name', $companyProfile->company_name ?? '') }}"
                >
                @error('company_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12 form-group">
                <label for="address_line_1">Morada</label>
                <input
                    type="text"
                    name="address_line_1"
                    id="address_line_1"
                    class="form-control @error('address_line_1') is-invalid @enderror"
                    value="{{ old('address_line_1', $companyProfile->address_line_1 ?? '') }}"
                >
                @error('address_line_1')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="city">Localidade</label>
                <input
                    type="text"
                    name="city"
                    id="city"
                    class="form-control @error('city') is-invalid @enderror"
                    value="{{ old('city', $companyProfile->city ?? '') }}"
                >
                @error('city')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-2 form-group">
                <label for="postal_code">Código Postal</label>
                <input
                    type="text"
                    name="postal_code"
                    id="postal_code"
                    class="form-control @error('postal_code') is-invalid @enderror"
                    value="{{ old('postal_code', $companyProfile->postal_code ?? '') }}"
                    maxlength="4"
                >
                @error('postal_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-1 form-group">
                <label for="postal_code_suffix">&nbsp;</label>
                <input
                    type="text"
                    name="postal_code_suffix"
                    id="postal_code_suffix"
                    class="form-control @error('postal_code_suffix') is-invalid @enderror"
                    value="{{ old('postal_code_suffix', $companyProfile->postal_code_suffix ?? '') }}"
                    maxlength="3"
                >
                @error('postal_code_suffix')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-3 form-group">
                <label for="postal_designation">Designação Postal (opcional)</label>
                <input
                    type="text"
                    name="postal_designation"
                    id="postal_designation"
                    class="form-control @error('postal_designation') is-invalid @enderror"
                    value="{{ old('postal_designation', $companyProfile->postal_designation ?? '') }}"
                >
                @error('postal_designation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12 form-group">
                <label for="district">Distrito</label>
                <input
                    type="text"
                    name="district"
                    id="district"
                    class="form-control @error('district') is-invalid @enderror"
                    value="{{ old('district', $companyProfile->district ?? '') }}"
                >
                @error('district')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="phone">Telefone</label>
                <input
                    type="text"
                    name="phone"
                    id="phone"
                    class="form-control @error('phone') is-invalid @enderror"
                    value="{{ old('phone', $companyProfile->phone ?? '') }}"
                >
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="fax">Fax</label>
                <input
                    type="text"
                    name="fax"
                    id="fax"
                    class="form-control @error('fax') is-invalid @enderror"
                    value="{{ old('fax', $companyProfile->fax ?? '') }}"
                >
                @error('fax')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="contact_person">Contacto</label>
                <input
                    type="text"
                    name="contact_person"
                    id="contact_person"
                    class="form-control @error('contact_person') is-invalid @enderror"
                    value="{{ old('contact_person', $companyProfile->contact_person ?? '') }}"
                >
                @error('contact_person')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email', $companyProfile->email ?? '') }}"
                >
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-2 form-group">
                <label for="country_code">Código País</label>
                <input
                    type="text"
                    name="country_code"
                    id="country_code"
                    class="form-control @error('country_code') is-invalid @enderror"
                    value="{{ old('country_code', $companyProfile->country_code ?? 'PT') }}"
                    maxlength="5"
                >
                @error('country_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4 form-group">
                <label for="tax_number">Nº Contribuinte</label>
                <input
                    type="text"
                    name="tax_number"
                    id="tax_number"
                    class="form-control @error('tax_number') is-invalid @enderror"
                    value="{{ old('tax_number', $companyProfile->tax_number ?? '') }}"
                >
                @error('tax_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="website">Site</label>
                <input
                    type="url"
                    name="website"
                    id="website"
                    class="form-control @error('website') is-invalid @enderror"
                    value="{{ old('website', $companyProfile->website ?? '') }}"
                >
                @error('website')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="share_capital">Capital Social</label>
                <input
                    type="number"
                    step="0.01"
                    name="share_capital"
                    id="share_capital"
                    class="form-control @error('share_capital') is-invalid @enderror"
                    value="{{ old('share_capital', $companyProfile->share_capital ?? '') }}"
                >
                @error('share_capital')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="registry_office">Conservatória</label>
                <input
                    type="text"
                    name="registry_office"
                    id="registry_office"
                    class="form-control @error('registry_office') is-invalid @enderror"
                    value="{{ old('registry_office', $companyProfile->registry_office ?? '') }}"
                >
                @error('registry_office')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12 form-group">
                <label for="logo">Imagem do Logótipo</label>
                <input
                    type="file"
                    name="logo"
                    id="logo"
                    class="form-control @error('logo') is-invalid @enderror"
                    accept=".jpg,.jpeg,.png,.webp"
                >
                @error('logo')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror

                @if(!empty($companyProfile->logo_path))
                    <div class="mt-3">
                        <div class="border rounded p-3 bg-light mb-2">
                            <img
                                src="{{ asset('storage/' . $companyProfile->logo_path) }}"
                                alt="Logótipo atual"
                                style="max-height: 100px; width: auto;"
                            >
                        </div>

                        <div class="checkbox-custom checkbox-default">
                            <input
                                type="checkbox"
                                name="remove_logo"
                                id="remove_logo"
                                value="1"
                                {{ old('remove_logo') ? 'checked' : '' }}
                            >
                            <label for="remove_logo">Remover logótipo atual</label>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Dados Bancários (Fatura e Nota de Débito)</h2>
    </header>

    <div class="card-body">
        <div class="row">
            <div class="col-md-12 form-group">
                <label for="bank_name">Banco</label>
                <input
                    type="text"
                    name="bank_name"
                    id="bank_name"
                    class="form-control @error('bank_name') is-invalid @enderror"
                    value="{{ old('bank_name', $companyProfile->bank_name ?? '') }}"
                >
                @error('bank_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12 form-group">
                <label for="bank_iban">IBAN</label>
                <input
                    type="text"
                    name="bank_iban"
                    id="bank_iban"
                    class="form-control @error('bank_iban') is-invalid @enderror"
                    value="{{ old('bank_iban', $companyProfile->bank_iban ?? '') }}"
                >
                @error('bank_iban')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12 form-group">
                <label for="bank_bic_swift">BIC/SWIFT</label>
                <input
                    type="text"
                    name="bank_bic_swift"
                    id="bank_bic_swift"
                    class="form-control @error('bank_bic_swift') is-invalid @enderror"
                    value="{{ old('bank_bic_swift', $companyProfile->bank_bic_swift ?? '') }}"
                >
                @error('bank_bic_swift')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</section>
