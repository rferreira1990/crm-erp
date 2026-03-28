@php
    $assignedRoles = collect(old('roles', $assignedRoles ?? []))->map(fn ($role) => (string) $role);
    $directPermissions = collect(old('permissions', $directPermissions ?? []))->map(fn ($permission) => (string) $permission);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Nome</label>
        <input
            type="text"
            name="name"
            id="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $user->name) }}"
            required
        >
        @error('name')
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
            value="{{ old('email', $user->email) }}"
            required
        >
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label">
            Password
            @if ($user->exists)
                <small class="text-muted">(deixar vazio para manter)</small>
            @endif
        </label>
        <input
            type="password"
            name="password"
            id="password"
            class="form-control @error('password') is-invalid @enderror"
            @if (! $user->exists) required @endif
        >
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Confirmar Password</label>
        <input
            type="password"
            name="password_confirmation"
            id="password_confirmation"
            class="form-control"
            @if (! $user->exists) required @endif
        >
    </div>

    <div class="col-12">
        <div class="form-check">
            <input
                type="checkbox"
                name="is_active"
                id="is_active"
                value="1"
                class="form-check-input"
                @checked((bool) old('is_active', $user->exists ? $user->is_active : true))
            >
            <label for="is_active" class="form-check-label">Utilizador ativo</label>
        </div>
        @error('is_active')
            <div class="text-danger small mt-1">{{ $message }}</div>
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
            @foreach ($roles as $role)
                <option value="{{ $role->name }}" @selected($assignedRoles->contains($role->name))>
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
            @foreach ($permissions as $permission)
                <option value="{{ $permission->name }}" @selected($directPermissions->contains($permission->name))>
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
</div>
