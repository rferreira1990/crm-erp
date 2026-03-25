@extends('layouts.admin')

@section('title', 'Dados da Empresa')

@section('content')
    <section role="main" class="content-body">
        <header class="page-header">
            <h2>Dados da Empresa</h2>
        </header>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <section class="card mb-4">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Dados Gerais da Empresa</h2>

                <a href="{{ route('company-profile.edit') }}" class="btn btn-primary btn-sm">
                    Editar Dados
                </a>
            </header>

            <div class="card-body">
                @if(!$companyProfile || !$companyProfile->company_name)
                    <div class="alert alert-info mb-0">
                        Ainda não existem dados da empresa preenchidos.
                    </div>
                @else
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="text-primary small mb-1">Designação</label>
                            <div>{{ $companyProfile->company_name ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="text-primary small mb-1">Morada</label>
                            <div>{{ $companyProfile->address_line_1 ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-primary small mb-1">Localidade</label>
                            <div>{{ $companyProfile->city ?: '-' }}</div>
                        </div>

                        <div class="col-md-2">
                            <label class="text-primary small mb-1">Código Postal</label>
                            <div>{{ $companyProfile->postal_code ?: '-' }}</div>
                        </div>

                        <div class="col-md-1">
                            <label class="text-primary small mb-1">&nbsp;</label>
                            <div>{{ $companyProfile->postal_code_suffix ?: '-' }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="text-primary small mb-1">Designação Postal</label>
                            <div>{{ $companyProfile->postal_designation ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="text-primary small mb-1">Distrito</label>
                            <div>{{ $companyProfile->district ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-primary small mb-1">Telefone</label>
                            <div>{{ $companyProfile->phone ?: '-' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-primary small mb-1">Fax</label>
                            <div>{{ $companyProfile->fax ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-primary small mb-1">Contacto</label>
                            <div>{{ $companyProfile->contact_person ?: '-' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-primary small mb-1">Email</label>
                            <div>{{ $companyProfile->email ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="text-primary small mb-1">Código País</label>
                            <div>{{ $companyProfile->country_code ?: '-' }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="text-primary small mb-1">Nº Contribuinte</label>
                            <div>{{ $companyProfile->tax_number ?: '-' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-primary small mb-1">Site</label>
                            <div>{{ $companyProfile->website ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="text-primary small mb-1">Capital Social</label>
                            <div>
                                {{ $companyProfile->share_capital !== null ? number_format((float) $companyProfile->share_capital, 2, ',', '.') . ' €' : '-' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="text-primary small mb-1">Conservatória</label>
                            <div>{{ $companyProfile->registry_office ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <label class="text-primary small mb-2">Imagem do Logótipo</label>

                            @if($companyProfile->logo_path)
                                <div class="border rounded p-3 bg-light">
                                    <img
                                        src="{{ asset('storage/' . $companyProfile->logo_path) }}"
                                        alt="Logótipo da empresa"
                                        style="max-height: 100px; width: auto;"
                                    >
                                </div>
                            @else
                                <div>-</div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="card mb-4">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Dados Bancários (Fatura e Nota de Débito)</h2>

                <a href="{{ route('company-profile.edit') }}" class="btn btn-primary btn-sm">
                    Editar Dados
                </a>
            </header>

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="text-primary small mb-1">Banco</label>
                        <div>{{ $companyProfile->bank_name ?? '-' }}</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="text-primary small mb-1">IBAN</label>
                        <div>{{ $companyProfile->bank_iban ?? '-' }}</div>
                    </div>
                </div>

                <div class="row mb-0">
                    <div class="col-md-12">
                        <label class="text-primary small mb-1">BIC/SWIFT</label>
                        <div>{{ $companyProfile->bank_bic_swift ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Configuração de Email</h2>

                <a href="{{ route('company-profile.edit') }}" class="btn btn-primary btn-sm">
                    Editar Dados
                </a>
            </header>

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-primary small mb-1">Servidor SMTP</label>
                        <div>{{ $companyProfile->mail_host ?: '-' }}</div>
                    </div>

                    <div class="col-md-2">
                        <label class="text-primary small mb-1">Porta</label>
                        <div>{{ $companyProfile->mail_port ?: '-' }}</div>
                    </div>

                    <div class="col-md-2">
                        <label class="text-primary small mb-1">Encriptação</label>
                        <div>{{ $companyProfile->mail_encryption ?: '-' }}</div>
                    </div>

                    <div class="col-md-2">
                        <label class="text-primary small mb-1">Password</label>
                        <div>{{ $companyProfile->mail_password ? '********' : '-' }}</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-primary small mb-1">Utilizador SMTP</label>
                        <div>{{ $companyProfile->mail_username ?: '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="text-primary small mb-1">Email Remetente</label>
                        <div>{{ $companyProfile->mail_from_address ?: '-' }}</div>
                    </div>
                </div>

                <div class="row mb-0">
                    <div class="col-md-6">
                        <label class="text-primary small mb-1">Nome Remetente</label>
                        <div>{{ $companyProfile->mail_from_name ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </section>
    </section>
@endsection
