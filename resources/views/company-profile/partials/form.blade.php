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

<section class="card mb-4">
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

<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Configuração de Email</h2>
    </header>

    <div class="card-body">
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="mail_host">Servidor SMTP</label>
                <input
                    type="text"
                    name="mail_host"
                    id="mail_host"
                    class="form-control @error('mail_host') is-invalid @enderror"
                    value="{{ old('mail_host', $companyProfile->mail_host ?? 'mail.fortiscasa.pt') }}"
                >
                @error('mail_host')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-3 form-group">
                <label for="mail_port">Porta</label>
                <input
                    type="number"
                    name="mail_port"
                    id="mail_port"
                    class="form-control @error('mail_port') is-invalid @enderror"
                    value="{{ old('mail_port', $companyProfile->mail_port ?? 465) }}"
                    min="1"
                    max="65535"
                >
                @error('mail_port')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-3 form-group">
                <label for="mail_encryption">Encriptação</label>
                <select
                    name="mail_encryption"
                    id="mail_encryption"
                    class="form-control @error('mail_encryption') is-invalid @enderror"
                >
                    <option value="">Selecionar</option>
                    <option value="ssl" {{ old('mail_encryption', $companyProfile->mail_encryption ?? 'ssl') === 'ssl' ? 'selected' : '' }}>SSL</option>
                    <option value="tls" {{ old('mail_encryption', $companyProfile->mail_encryption ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                </select>
                @error('mail_encryption')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="mail_username">Utilizador SMTP</label>
                <input
                    type="email"
                    name="mail_username"
                    id="mail_username"
                    class="form-control @error('mail_username') is-invalid @enderror"
                    value="{{ old('mail_username', $companyProfile->mail_username ?? 'noreply@fortiscasa.pt') }}"
                >
                @error('mail_username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="mail_password">Password SMTP</label>
                <input
                    type="password"
                    name="mail_password"
                    id="mail_password"
                    class="form-control @error('mail_password') is-invalid @enderror"
                    value=""
                    autocomplete="new-password"
                >
                @error('mail_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">
                    Deixa vazio para manter a password atual.
                </small>
            </div>

            <div class="col-md-6 form-group">
                <label for="mail_from_address">Email Remetente</label>
                <input
                    type="email"
                    name="mail_from_address"
                    id="mail_from_address"
                    class="form-control @error('mail_from_address') is-invalid @enderror"
                    value="{{ old('mail_from_address', $companyProfile->mail_from_address ?? 'noreply@fortiscasa.pt') }}"
                >
                @error('mail_from_address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="mail_from_name">Nome Remetente</label>
                <input
                    type="text"
                    name="mail_from_name"
                    id="mail_from_name"
                    class="form-control @error('mail_from_name') is-invalid @enderror"
                    value="{{ old('mail_from_name', $companyProfile->mail_from_name ?? ($companyProfile->company_name ?? 'Fortiscasa')) }}"
                >
                @error('mail_from_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="mail_default_cc">CC por defeito (orçamentos)</label>
                <input
                    type="email"
                    name="mail_default_cc"
                    id="mail_default_cc"
                    class="form-control @error('mail_default_cc') is-invalid @enderror"
                    value="{{ old('mail_default_cc', $companyProfile->mail_default_cc ?? '') }}"
                    maxlength="150"
                >
                @error('mail_default_cc')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="mail_default_bcc">BCC por defeito (orçamentos)</label>
                <input
                    type="email"
                    name="mail_default_bcc"
                    id="mail_default_bcc"
                    class="form-control @error('mail_default_bcc') is-invalid @enderror"
                    value="{{ old('mail_default_bcc', $companyProfile->mail_default_bcc ?? '') }}"
                    maxlength="150"
                >
                @error('mail_default_bcc')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</section>
