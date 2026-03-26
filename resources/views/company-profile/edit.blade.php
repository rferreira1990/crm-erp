@extends('layouts.admin')

@section('title', 'Editar Dados da Empresa')

@section('content')
    <div class="row">
        <div class="col-xl-8">
            <form method="POST" action="{{ route('company-profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @include('company-profile.partials.form')

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        Gravar
                    </button>

                    <a href="{{ route('company-profile.show') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

        <div class="col-xl-4 mt-4 mt-xl-0">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Teste de email</h4>
                </div>

                <div class="card-body">
                    <p class="text-muted mb-3">
                        Este teste usa a configuração SMTP atualmente gravada nos dados da empresa.
                        Guarda primeiro as alterações e depois faz o teste.
                    </p>

                    <form method="POST" action="{{ route('company-profile.test-email') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="test_recipient_email" class="form-label">Enviar teste para</label>
                            <input
                                type="email"
                                name="test_recipient_email"
                                id="test_recipient_email"
                                class="form-control @if($errors->testEmail->has('test_recipient_email')) is-invalid @endif"
                                value="{{ old('test_recipient_email', auth()->user()?->email) }}"
                                maxlength="150"
                                required
                            >

                            @if($errors->testEmail->has('test_recipient_email'))
                                <div class="invalid-feedback">
                                    {{ $errors->testEmail->first('test_recipient_email') }}
                                </div>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-outline-primary w-100">
                            Enviar email de teste
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
