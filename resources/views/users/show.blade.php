@extends('layouts.admin')

@section('title', 'Detalhe do Utilizador')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">{{ $user->name }}</h2>

                <div class="d-flex gap-2">
                    @can('users.edit')
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm">Editar</a>
                    @endcan

                    <a href="{{ route('users.index') }}" class="btn btn-light btn-sm border">Voltar</a>
                </div>
            </header>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-6"><strong>Nome:</strong> {{ $user->name }}</div>
                    <div class="col-md-6"><strong>Email:</strong> {{ $user->email }}</div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Estado:</strong>
                        @if ($user->is_active)
                            <span class="badge bg-success">Ativo</span>
                        @else
                            <span class="badge bg-secondary">Inativo</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Registado em:</strong> {{ $user->created_at?->format('d/m/Y H:i') }}
                    </div>
                </div>

                <div class="mb-3">
                    <strong>Roles:</strong><br>
                    @forelse ($user->roles as $role)
                        <span class="badge bg-info text-dark">{{ $role->name }}</span>
                    @empty
                        <span class="text-muted">Sem roles atribuidas.</span>
                    @endforelse
                </div>

                <div class="mb-3">
                    <strong>Permissoes diretas:</strong><br>
                    @forelse ($user->permissions as $permission)
                        <span class="badge bg-warning text-dark">{{ $permission->name }}</span>
                    @empty
                        <span class="text-muted">Sem permissoes diretas.</span>
                    @endforelse
                </div>

                @can('users.delete')
                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="btn btn-danger"
                            onclick="return confirm('Apagar este utilizador?');"
                        >
                            Remover
                        </button>
                    </form>
                @endcan
            </div>
        </section>
    </div>
</div>
@endsection
