@extends('layouts.admin')

@section('title', 'Detalhe do Cliente')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">{{ $customer->name }}</h2>

                <div class="d-flex gap-2">
                    @can('customers.edit')
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary btn-sm">
                            Editar
                        </a>
                    @endcan

                    <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm border">
                        Voltar
                    </a>
                </div>
            </header>

            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-4"><strong>Código:</strong> {{ $customer->code ?: '-' }}</div>
                    <div class="col-md-4"><strong>Tipo:</strong> {{ $customer->type === 'company' ? 'Empresa' : 'Particular' }}</div>
                    <div class="col-md-4"><strong>Status:</strong> {{ ucfirst($customer->status) }}</div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4"><strong>NIF:</strong> {{ $customer->nif ?: '-' }}</div>
                    <div class="col-md-4"><strong>Email:</strong> {{ $customer->email ?: '-' }}</div>
                    <div class="col-md-4"><strong>Contacto:</strong> {{ $customer->mobile ?: ($customer->phone ?: '-') }}</div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4"><strong>Pessoa de contacto:</strong> {{ $customer->contact_person ?: '-' }}</div>
                    <div class="col-md-4"><strong>Desconto padrão:</strong> {{ number_format($customer->default_discount, 2, ',', '.') }}%</div>
                    <div class="col-md-4"><strong>Prazo pagamento:</strong> {{ $customer->payment_terms_days }} dias</div>
                </div>

                <div class="mb-3">
                    <strong>Morada:</strong><br>
                    {{ $customer->address_line_1 ?: '-' }}<br>
                    @if($customer->address_line_2)
                        {{ $customer->address_line_2 }}<br>
                    @endif
                    {{ $customer->postal_code ?: '' }} {{ $customer->city ?: '' }}<br>
                    {{ $customer->country ?: 'Portugal' }}
                </div>

                <div class="mb-3">
                    <strong>Origem:</strong> {{ $customer->source ?: '-' }}
                </div>

                <div class="mb-3">
                    <strong>Último contacto:</strong>
                    {{ $customer->last_contact_at ? $customer->last_contact_at->format('d/m/Y H:i') : '-' }}
                </div>

                <div class="mb-3">
                    <strong>Notas:</strong><br>
                    {!! nl2br(e($customer->notes ?: '-')) !!}
                </div>

               @can('customers.delete')
                    <a href="#modalDeleteCustomer{{ $customer->id }}" class="btn btn-danger modal-basic">
                        Remover
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

                                            <button type="submit" class="btn btn-danger">
                                                Eliminar
                                            </button>
                                        </form>

                                        <button class="btn btn-default modal-dismiss">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </footer>
                        </section>
                    </div>
                @endcan
            </div>
        </section>
    </div>
</div>
@endsection
