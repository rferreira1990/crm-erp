@extends('layouts.admin')

@section('title', 'Convidar Utilizador')

@section('content')
<div class="row">
    <div class="col">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title mb-0">Convidar Utilizador</h2>
            </header>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Existem erros no formulario.</strong>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ route('users.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="invitee_name" class="form-label">Nome (opcional)</label>
                            <input
                                type="text"
                                name="invitee_name"
                                id="invitee_name"
                                class="form-control @error('invitee_name') is-invalid @enderror"
                                value="{{ old('invitee_name') }}"
                                maxlength="255"
                            >
                            @error('invitee_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="roles" class="form-label">Roles / Grupos</label>
                            <select
                                name="roles[]"
                                id="roles"
                                class="form-select @error('roles') is-invalid @enderror @error('roles.*') is-invalid @enderror"
                                multiple
                                size="8"
                                required
                            >
                                @php $selectedRoles = collect(old('roles', []))->map(fn ($role) => (string) $role); @endphp
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}" @selected($selectedRoles->contains($role->name))>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Seleciona uma ou varias roles (Ctrl/Cmd + clique).</div>
                            @error('roles')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('roles.*')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="permissions" class="form-label">Permissoes Diretas (opcional)</label>
                            <select
                                name="permissions[]"
                                id="permissions"
                                class="form-select @error('permissions') is-invalid @enderror @error('permissions.*') is-invalid @enderror"
                                multiple
                                size="8"
                            >
                                @php $selectedPermissions = collect(old('permissions', []))->map(fn ($permission) => (string) $permission); @endphp
                                @foreach ($permissions as $permission)
                                    <option value="{{ $permission->name }}" @selected($selectedPermissions->contains($permission->name))>
                                        {{ $permission->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">As permissoes diretas complementam as permissoes vindas das roles.</div>
                            @error('permissions')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('permissions.*')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                O sistema envia um link de convite para o email indicado.
                                O link expira em <strong>{{ $invitationExpiryHours }} horas</strong>.
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Enviar convite</button>
                        <a href="{{ route('users.index') }}" class="btn btn-light border">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
