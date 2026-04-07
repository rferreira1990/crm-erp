@extends('layouts.admin')

@section('title', 'Ficha de Cliente')

@section('content')
@php
    $canEditCustomer = auth()->user()?->can('customers.edit');
    $canDeleteCustomer = auth()->user()?->can('customers.delete');
    $canViewReceivables = auth()->user()?->can('customers.view');
    $canManageReceivables = auth()->user()?->can('customers.edit');
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">{{ $customer->name }}</h2>
        <div class="text-muted">
            Codigo <strong>{{ $customer->code ?: '-' }}</strong>
            @if (!empty($customer->nif))
                | NIF <strong>{{ $customer->nif }}</strong>
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        @if ($customer->is_active)
            <span class="badge bg-success align-self-center">Ativo</span>
        @else
            <span class="badge bg-secondary align-self-center">Inativo</span>
        @endif

        @if ($canEditCustomer)
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary">Editar</a>
        @endif

        @if ($canDeleteCustomer)
            <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="js-confirm-form" data-confirm-message="Remover este cliente?">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Remover</button>
            </form>
        @endif

        @if ($canViewReceivables)
            <a href="{{ route('customer-receivables.index', ['customer_id' => $customer->id]) }}" class="btn btn-outline-info">
                Contas a receber
            </a>
        @endif

        @if ($canManageReceivables)
            <a href="{{ route('customer-receivables.create', ['customer_id' => $customer->id]) }}" class="btn btn-success">
                Nova conta a receber
            </a>
        @endif

        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Voltar</a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Existem erros de validacao.</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-12">
        <section class="card shadow-sm">
            <header class="card-header">
                <h2 class="card-title mb-0">Dados gerais</h2>
            </header>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <div>{{ $customer->type === 'company' ? 'Empresa' : 'Particular' }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Estado comercial</label>
                        <div>{{ ucfirst((string) $customer->status) }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <div>{{ $customer->email ?: '-' }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Contacto</label>
                        <div>{{ $customer->mobile ?: ($customer->phone ?: '-') }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Pessoa de contacto</label>
                        <div>{{ $customer->contact_person ?: '-' }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Desconto padrao</label>
                        <div>{{ number_format((float) $customer->default_discount, 2, ',', '.') }}%</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Prazo pagamento</label>
                        <div>{{ (int) $customer->payment_terms_days }} dias</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Origem</label>
                        <div>{{ $customer->source ?: '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Morada</label>
                        <div>
                            {{ $customer->address_line_1 ?: '-' }}
                            @if ($customer->address_line_2)
                                <br>{{ $customer->address_line_2 }}
                            @endif
                            <br>{{ trim(($customer->postal_code ?: '') . ' ' . ($customer->city ?: '')) ?: '-' }}
                            <br>{{ $customer->country ?: 'Portugal' }}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Ultimo contacto</label>
                        <div>{{ $customer->last_contact_at ? $customer->last_contact_at->format('d/m/Y H:i') : '-' }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Criado em</label>
                        <div>{{ $customer->created_at?->format('d/m/Y H:i') ?: '-' }}</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notas</label>
                        <div>{!! nl2br(e($customer->notes ?: '-')) !!}</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<section class="card shadow-sm">
    <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="card-title mb-0">Conta corrente</h2>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-light text-dark border">Debito: {{ number_format((float) $accountTotals['debit'], 2, ',', '.') }} EUR</span>
            <span class="badge bg-light text-dark border">Credito: {{ number_format((float) $accountTotals['credit'], 2, ',', '.') }} EUR</span>
            <span class="badge {{ (float) $accountTotals['balance'] > 0 ? 'bg-danger' : 'bg-success' }}">Saldo: {{ number_format((float) $accountTotals['balance'], 2, ',', '.') }} EUR</span>
            <span class="badge bg-warning text-dark">Vencido: {{ number_format((float) $accountTotals['overdue'], 2, ',', '.') }} EUR</span>
        </div>
    </header>

    <div class="card-body">
        <div class="row g-3 mb-3">
            @if ($canEditCustomer)
                <div class="col-xl-8">
                    <form method="POST" action="{{ route('customers.account-entries.store', $customer) }}" class="border rounded p-3 bg-light">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label for="entry_date" class="form-label">Data</label>
                                <input type="date" id="entry_date" name="entry_date" class="form-control" value="{{ old('entry_date', now()->toDateString()) }}" required>
                            </div>

                            <div class="col-md-3">
                                <label for="due_date" class="form-label">Vencimento</label>
                                <input type="date" id="due_date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                            </div>

                            <div class="col-md-3">
                                <label for="type" class="form-label">Tipo</label>
                                <select id="type" name="type" class="form-control" required>
                                    <option value="">Selecionar...</option>
                                    @foreach ($accountEntryTypes as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}" @selected(old('type') === $typeKey)>{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="amount" class="form-label">Valor</label>
                                <input type="number" id="amount" name="amount" class="form-control" min="0.01" step="0.01" value="{{ old('amount') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="description" class="form-label">Descricao</label>
                                <input type="text" id="description" name="description" class="form-control" maxlength="255" value="{{ old('description') }}" required>
                            </div>

                            <div class="col-md-3">
                                <label for="reference_type" class="form-label">Tipo referencia</label>
                                <input type="text" id="reference_type" name="reference_type" class="form-control" maxlength="100" value="{{ old('reference_type') }}" placeholder="fatura, recibo, nota...">
                            </div>

                            <div class="col-md-3">
                                <label for="reference_id" class="form-label">ID referencia</label>
                                <input type="number" id="reference_id" name="reference_id" class="form-control" min="1" step="1" value="{{ old('reference_id') }}">
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label">Notas</label>
                                <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Registar movimento</button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

            <div class="{{ $canEditCustomer ? 'col-xl-4' : 'col-12' }}">
                <form method="GET" action="{{ route('customers.show', $customer) }}" class="border rounded p-3">
                    <div class="row g-2">
                        <div class="col-md-6 {{ $canEditCustomer ? '' : 'col-xl-3' }}">
                            <label for="account_date_from" class="form-label">Periodo de</label>
                            <input type="date" id="account_date_from" name="account_date_from" class="form-control" value="{{ $accountFilters['account_date_from'] }}">
                        </div>

                        <div class="col-md-6 {{ $canEditCustomer ? '' : 'col-xl-3' }}">
                            <label for="account_date_to" class="form-label">ate</label>
                            <input type="date" id="account_date_to" name="account_date_to" class="form-control" value="{{ $accountFilters['account_date_to'] }}">
                        </div>

                        <div class="col-md-12 {{ $canEditCustomer ? '' : 'col-xl-6' }} d-flex gap-2 align-items-end">
                            <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary">Limpar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Descricao</th>
                        <th>Vencimento</th>
                        <th class="text-end">Debito</th>
                        <th class="text-end">Credito</th>
                        <th class="text-end">Saldo</th>
                        <th>Utilizador</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accountEntries as $entry)
                        @php
                            $isOverdue = $entry->due_date !== null
                                && $entry->due_date->isBefore(now()->startOfDay())
                                && (float) $entry->signedAmount() > 0;
                        @endphp
                        <tr>
                            <td>{{ $entry->entry_date?->format('d/m/Y') ?: '-' }}</td>
                            <td>
                                {{ $entry->typeLabel() }}
                                @if ($entry->isAutomatic())
                                    <div class="small">
                                        <span class="badge bg-info text-dark">Automatico</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                {{ $entry->description }}
                                @if (!empty($entry->reference_type))
                                    <div class="small text-muted">{{ $entry->reference_type }}@if($entry->reference_id) #{{ $entry->reference_id }}@endif</div>
                                @endif
                                @if ($entry->isFromCustomerReceivable() && $canViewReceivables)
                                    <div class="small mt-1">
                                        <a href="{{ route('customer-receivables.show', $entry->reference_id) }}" class="text-primary">
                                            Ver documento de conta a receber
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($entry->due_date)
                                    <span class="{{ $isOverdue ? 'text-danger fw-semibold' : '' }}">{{ $entry->due_date->format('d/m/Y') }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">{{ (float) $entry->debit_amount > 0 ? number_format((float) $entry->debit_amount, 2, ',', '.') : '-' }}</td>
                            <td class="text-end">{{ (float) $entry->credit_amount > 0 ? number_format((float) $entry->credit_amount, 2, ',', '.') : '-' }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $entry->running_balance, 2, ',', '.') }}</td>
                            <td>{{ $entry->user?->name ?: '-' }}</td>
                            <td>{!! nl2br(e($entry->notes ?: '-')) !!}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">Sem movimentos de conta corrente para os filtros selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
