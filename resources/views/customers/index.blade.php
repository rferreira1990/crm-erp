@extends('layouts.admin')

@section('title', 'Clientes')

@section('page_header')
    <header class="page-header">
        <h2>Clientes</h2>

        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li>
                    <a href="{{ route('dashboard') }}">
                        <i class="bx bx-home-alt"></i>
                    </a>
                </li>
                <li><span>Clientes</span></li>
            </ol>
        </div>
    </header>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

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
                    @if ($customers->count())
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Tipo</th>
                                        <th>NIF</th>
                                        <th>Contacto</th>
                                        <th>Email</th>
                                        <th>Estado</th>
                                        <th>Ativo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customers as $customer)
                                        <tr>
                                            <td>{{ $customer->name }}</td>
                                            <td>{{ $customer->type === 'company' ? 'Empresa' : 'Particular' }}</td>
                                            <td>{{ $customer->nif ?: '-' }}</td>
                                            <td>{{ $customer->contact_person ?: ($customer->phone ?: $customer->mobile ?: '-') }}</td>
                                            <td>{{ $customer->email ?: '-' }}</td>
                                            <td>
                                                @if ($customer->status === 'active')
                                                    <span class="badge bg-success">Ativo</span>
                                                @elseif ($customer->status === 'inactive')
                                                    <span class="badge bg-secondary">Inativo</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Prospect</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($customer->is_active)
                                                    <span class="badge bg-success">Sim</span>
                                                @else
                                                    <span class="badge bg-danger">Não</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $customers->links() }}
                        </div>
                    @else
                        <p class="mb-0">Ainda não existem clientes registados.</p>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
