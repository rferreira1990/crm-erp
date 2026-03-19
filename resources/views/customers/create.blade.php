@extends('layouts.admin')

@section('title', 'Criar Cliente')

@section('page_header')
    <header class="page-header">
        <h2>Criar Cliente</h2>

        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li>
                    <a href="{{ route('dashboard') }}">
                        <i class="bx bx-home-alt"></i>
                    </a>
                </li>
                <li><a href="{{ route('customers.index') }}">Clientes</a></li>
                <li><span>Criar</span></li>
            </ol>
        </div>
    </header>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-10">
            <section class="card">
                <header class="card-header">
                    <h2 class="card-title">Novo Cliente</h2>
                </header>

                <div class="card-body">
                    <form action="{{ route('customers.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="name" class="form-label">Nome</label>
                                <input type="text" name="name" id="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="type" class="form-label">Tipo</label>
                                <select name="type" id="type"
                                    class="form-control @error('type') is-invalid @enderror" required>
                                    <option value="private" {{ old('type') === 'private' ? 'selected' : '' }}>Particular</option>
                                    <option value="company" {{ old('type') === 'company' ? 'selected' : '' }}>Empresa</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="nif" class="form-label">NIF (opcional)</label>
                                <input type="text" name="nif" id="nif"
                                    class="form-control @error('nif') is-invalid @enderror"
                                    value="{{ old('nif') }}">
                                @error('nif')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="phone" class="form-label">Telefone</label>
                                <input type="text" name="phone" id="phone"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="mobile" class="form-label">Telemóvel</label>
                                <input type="text" name="mobile" id="mobile"
                                    class="form-control @error('mobile') is-invalid @enderror"
                                    value="{{ old('mobile') }}">
                                @error('mobile')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="contact_person" class="form-label">Pessoa de contacto</label>
                                <input type="text" name="contact_person" id="contact_person"
                                    class="form-control @error('contact_person') is-invalid @enderror"
                                    value="{{ old('contact_person') }}">
                                @error('contact_person')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="address_line_1" class="form-label">Morada</label>
                                <input type="text" name="address_line_1" id="address_line_1"
                                    class="form-control @error('address_line_1') is-invalid @enderror"
                                    value="{{ old('address_line_1') }}">
                                @error('address_line_1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="address_line_2" class="form-label">Morada (linha 2)</label>
                                <input type="text" name="address_line_2" id="address_line_2"
                                    class="form-control @error('address_line_2') is-invalid @enderror"
                                    value="{{ old('address_line_2') }}">
                                @error('address_line_2')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="postal_code" class="form-label">Código postal</label>
                                <input type="text" name="postal_code" id="postal_code"
                                    class="form-control @error('postal_code') is-invalid @enderror"
                                    value="{{ old('postal_code') }}">
                                @error('postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">Cidade</label>
                                <input type="text" name="city" id="city"
                                    class="form-control @error('city') is-invalid @enderror"
                                    value="{{ old('city') }}">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="country" class="form-label">País</label>
                                <input type="text" name="country" id="country"
                                    class="form-control @error('country') is-invalid @enderror"
                                    value="{{ old('country', 'Portugal') }}">
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="default_discount" class="form-label">Desconto padrão (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="default_discount" id="default_discount"
                                    class="form-control @error('default_discount') is-invalid @enderror"
                                    value="{{ old('default_discount', 0) }}">
                                @error('default_discount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="payment_terms_days" class="form-label">Prazo pagamento (dias)</label>
                                <input type="number" min="0" max="3650" name="payment_terms_days" id="payment_terms_days"
                                    class="form-control @error('payment_terms_days') is-invalid @enderror"
                                    value="{{ old('payment_terms_days', 0) }}">
                                @error('payment_terms_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Estado</label>
                                <select name="status" id="status"
                                    class="form-control @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Ativo</option>
                                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inativo</option>
                                    <option value="prospect" {{ old('status') === 'prospect' ? 'selected' : '' }}>Prospect</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="source" class="form-label">Origem</label>
                                <input type="text" name="source" id="source"
                                    class="form-control @error('source') is-invalid @enderror"
                                    value="{{ old('source') }}"
                                    placeholder="Ex: indicação, site, telefone">
                                @error('source')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active"
                                        class="form-check-input" value="1"
                                        {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Cliente ativo
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 mb-3">
                                <label for="notes" class="form-label">Observações</label>
                                <textarea name="notes" id="notes" rows="4"
                                    class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Guardar cliente</button>
                            <a href="{{ route('customers.index') }}" class="btn btn-default border">Cancelar</a>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection
