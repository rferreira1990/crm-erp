@extends('layouts.admin')

@section('title', 'Utilizadores')

@section('content')
<header class="page-header">
    <h2>Utilizadores</h2>
</header>

<div class="row">
    <div class="col">
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Lista de Utilizadores</h2>

                @can('users.create')
                    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                        Novo Utilizador
                    </a>
                @endcan
            </header>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form method="GET" action="{{ route('users.index') }}" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Pesquisar</label>
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                value="{{ $search }}"
                                placeholder="Nome, email ou funcao"
                            >
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select">
                                <option value="">Todos</option>
                                <option value="active" @selected($status === 'active')>Ativo</option>
                                <option value="inactive" @selected($status === 'inactive')>Inativo</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="">Todas</option>
                                @foreach ($roles as $roleName)
                                    <option value="{{ $roleName }}" @selected($role === $roleName)>{{ $roleName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                            <a href="{{ route('users.index') }}" class="btn btn-light border w-100">Limpar</a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-ecommerce-simple table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th>Funcao</th>
                                <th>Custo hora</th>
                                <th>Mao obra</th>
                                <th>Roles</th>
                                <th>Permissoes Diretas</th>
                                <th class="text-end">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if ($user->is_active)
                                            <span class="badge bg-success">Ativo</span>
                                        @else
                                            <span class="badge bg-secondary">Inativo</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->job_title ?: '-' }}</td>
                                    <td>{{ number_format((float) $user->hourly_cost, 2, ',', '.') }} &euro;</td>
                                    <td>
                                        @if ($user->is_labor_enabled)
                                            <span class="badge bg-success">Sim</span>
                                        @else
                                            <span class="badge bg-secondary">Nao</span>
                                        @endif
                                    </td>
                                    <td>
                                        @forelse ($user->roles as $role)
                                            <span class="badge bg-info text-dark">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-muted">Sem roles</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if ($user->permissions->count() > 0)
                                            <span class="badge bg-warning text-dark">{{ $user->permissions->count() }}</span>
                                        @else
                                            <span class="text-muted">Nenhuma</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-light btn-sm border">
                                            Ver
                                        </a>

                                        @can('users.edit')
                                            <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm">
                                                Editar
                                            </a>
                                        @endcan

                                        @can('users.delete')
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Apagar este utilizador?');"
                                                >
                                                    Apagar
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">Nenhum utilizador encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
