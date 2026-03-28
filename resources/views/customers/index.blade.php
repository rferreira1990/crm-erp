@extends('layouts.admin')

@section('title', 'Clientes')

@section('content')
<header class="page-header">
    <h2>Clientes</h2>
</header>

<div class="row">
    <div class="col">
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Lista de Clientes</h2>

                @can('customers.create')
                    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                        Novo Cliente
                    </a>
                @endcan
            </header>

            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="GET" action="{{ route('customers.index') }}" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Pesquisar</label>
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Nome, código, NIF, email, telefone..."
                                value="{{ request('search') }}"
                            >
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select">
                                <option value="">Todos</option>
                                <option value="active" @selected(request('status') === 'active')>Ativo</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>Inativo</option>
                                <option value="prospect" @selected(request('status') === 'prospect')>Prospect</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tipo</label>
                            <select name="type" class="form-select">
                                <option value="">Todos</option>
                                <option value="private" @selected(request('type') === 'private')>Particular</option>
                                <option value="company" @selected(request('type') === 'company')>Empresa</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Ativo</label>
                            <select name="active" class="form-select">
                                <option value="">Todos</option>
                                <option value="1" @selected(request('active') === '1')>Sim</option>
                                <option value="0" @selected(request('active') === '0')>Não</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                            <a href="{{ route('customers.index') }}" class="btn btn-light border w-100">Limpar</a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-ecommerce-simple table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>NIF</th>
                                <th>Contacto</th>
                                <th>Cidade</th>
                                <th>Estado</th>
                                <th>Ativo</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                                <tr>
                                    <td>{{ $customer->code }}</td>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->type === 'company' ? 'Empresa' : 'Particular' }}</td>
                                    <td>{{ $customer->nif ?: '-' }}</td>
                                    <td>{{ $customer->mobile ?: ($customer->phone ?: '-') }}</td>
                                    <td>{{ $customer->city ?: '-' }}</td>
                                    <td>
                                        @if($customer->status === 'active')
                                            <span class="badge bg-success">Ativo</span>
                                        @elseif($customer->status === 'inactive')
                                            <span class="badge bg-secondary">Inativo</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Prospect</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($customer->is_active)
                                            <span class="badge bg-success">Sim</span>
                                        @else
                                            <span class="badge bg-danger">Não</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-light btn-sm border">
                                            Ver
                                        </a>

                                        @can('customers.edit')
                                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary btn-sm">
                                                Editar
                                            </a>
                                        @endcan

                                        @can('customers.delete')
                                            <a href="#modalDeleteCustomer{{ $customer->id }}" class="btn btn-danger btn-sm modal-basic">
                                                Eliminar
                                            </a>

                                            <div id="modalDeleteCustomer{{ $customer->id }}" class="modal-block modal-block-danger mfp-hide">
                                                <section class="card">
                                                    <header class="card-header">
                                                        <h2 class="card-title">Eliminar cliente</h2>
                                                    </header>

                                                    <div class="card-body">
                                                        <div class="modal-wrapper">
                                                            <div class="modal-icon">
                                                                <i class="fas fa-times-circle"></i>
                                                            </div>
                                                            <div class="modal-text">
                                                                <h4 class="font-weight-bold text-dark">Danger</h4>
                                                                <p class="mb-0">
                                                                    Tens a certeza que queres eliminar o cliente
                                                                    <strong>{{ $customer->name }}</strong>?
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <footer class="card-footer">
                                                        <div class="row">
                                                            <div class="col-md-12 text-end">
                                                                <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline-block">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                                                </form>

                                                                <button class="btn btn-default modal-dismiss">Cancelar</button>
                                                            </div>
                                                        </div>
                                                    </footer>
                                                </section>
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        Nenhum cliente encontrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $customers->links() }}
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
