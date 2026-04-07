@extends('layouts.admin')

@section('title', 'Ficha de Fornecedor')

@section('content')
@php
    $canUpdateSupplier = auth()->user()?->can('suppliers.update');
    $canDeleteSupplier = auth()->user()?->can('suppliers.delete');

    $contactMethodLabels = [
        'email' => 'Email',
        'phone' => 'Telefone',
        'mobile' => 'Telemovel',
    ];

    $supplierFiles = $supplier->files ?? collect();
    $filesByType = $supplierFiles->groupBy('type');
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">{{ $supplier->name }}</h2>
        <div class="text-muted">
            Codigo <strong>{{ $supplier->code }}</strong>
            @if (!empty($supplier->tax_number))
                | NIF <strong>{{ $supplier->tax_number }}</strong>
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        @if ($supplier->is_active)
            <span class="badge bg-success align-self-center">Ativo</span>
        @else
            <span class="badge bg-secondary align-self-center">Inativo</span>
        @endif

        @can('suppliers.update')
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary">Editar</a>

            <form method="POST" action="{{ route('suppliers.toggle-active', $supplier) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" value="{{ $supplier->is_active ? 0 : 1 }}">
                <button type="submit" class="btn btn-outline-warning">
                    {{ $supplier->is_active ? 'Desativar' : 'Ativar' }}
                </button>
            </form>
        @endcan

        @if ($canDeleteSupplier)
            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="js-confirm-form" data-confirm-message="Remover este fornecedor?">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Remover</button>
            </form>
        @endif

        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Voltar</a>
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
    <div class="col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <strong>Logotipo</strong>
            </div>
            <div class="card-body">
                @if ($supplier->logo_url)
                    <div class="mb-3">
                        <img
                            src="{{ $supplier->logo_url }}"
                            alt="Logotipo fornecedor"
                            class="supplier-logo-preview"
                        >
                    </div>
                @else
                    <div class="text-muted mb-3">Sem logotipo associado.</div>
                @endif

                @if ($canUpdateSupplier)
                    <form method="POST" action="{{ route('suppliers.logo.store', $supplier) }}" enctype="multipart/form-data" class="mb-2">
                        @csrf
                        <label for="logo" class="form-label">Atualizar logotipo</label>
                        <input type="file" name="logo" id="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*" required>
                        <button type="submit" class="btn btn-sm btn-primary mt-2">Carregar</button>
                    </form>

                    @if ($supplier->logo_path)
                        <form method="POST" action="{{ route('suppliers.logo.destroy', $supplier) }}" class="js-confirm-form" data-confirm-message="Remover logotipo deste fornecedor?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Remover logotipo</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Catalogos, imagens e outros anexos</strong>
                <span class="badge bg-light text-dark border">{{ $supplierFiles->count() }}</span>
            </div>
            <div class="card-body">
                @if ($canUpdateSupplier)
                    <form method="POST" action="{{ route('suppliers.files.store', $supplier) }}" enctype="multipart/form-data" class="border rounded p-3 bg-light mb-3">
                        @csrf
                        <label for="files" class="form-label">Adicionar anexos</label>
                        <input
                            type="file"
                            name="files[]"
                            id="files"
                            class="form-control"
                            multiple
                            accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z"
                            required
                        >
                        <div class="form-text">Maximo 10 ficheiros por envio, ate 20 MB por ficheiro.</div>
                        <button type="submit" class="btn btn-sm btn-primary mt-2">Carregar anexos</button>
                    </form>
                @endif

                @if ($supplierFiles->isEmpty())
                    <div class="text-muted">Sem anexos associados.</div>
                @else
                    @foreach (['catalog' => 'Catalogos (PDF)', 'image' => 'Imagens', 'document' => 'Documentos', 'archive' => 'Arquivos'] as $typeKey => $typeLabel)
                        @php
                            $typedFiles = $filesByType->get($typeKey, collect());
                        @endphp
                        @if ($typedFiles->isNotEmpty())
                            <div class="mb-3">
                                <div class="fw-semibold mb-2">{{ $typeLabel }}</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>MIME</th>
                                                <th>Tamanho</th>
                                                <th>Carregado em</th>
                                                <th class="text-end">Acoes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($typedFiles as $file)
                                                <tr>
                                                    <td>{{ $file->original_name }}</td>
                                                    <td>{{ $file->mime_type }}</td>
                                                    <td>{{ $file->readable_size }}</td>
                                                    <td>{{ $file->created_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                                    <td class="text-end">
                                                        <a href="{{ route('suppliers.files.show', [$supplier, $file]) }}" class="btn btn-sm btn-outline-primary">
                                                            Abrir
                                                        </a>
                                                        @if ($canUpdateSupplier)
                                                            <form method="POST" action="{{ route('suppliers.files.destroy', [$supplier, $file]) }}" class="d-inline js-confirm-form" data-confirm-message="Remover este anexo?">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <strong>Dados gerais</strong>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <div>{{ $supplier->email ?: '-' }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Telefone</label>
                        <div>{{ $supplier->phone ?: '-' }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Telemovel</label>
                        <div>{{ $supplier->mobile ?: '-' }}</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Pessoa de contacto</label>
                        <div>{{ $supplier->contact_person ?: '-' }}</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Website</label>
                        <div>{{ $supplier->website ?: '-' }}</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Referencia externa</label>
                        <div>{{ $supplier->external_reference ?: '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Morada</label>
                        <div>{{ $supplier->address ?: '-' }}</div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Codigo postal</label>
                        <div>{{ $supplier->postal_code ?: '-' }}</div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Cidade</label>
                        <div>{{ $supplier->city ?: '-' }}</div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Pais</label>
                        <div>{{ $supplier->country ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <strong>Condicoes</strong>
            </div>

            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label">Condicao de pagamento</label>
                    <div>{{ $supplier->paymentTerm?->displayLabel() ?: '-' }}</div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Taxa IVA por defeito</label>
                    <div>
                        @if ($supplier->defaultTaxRate)
                            {{ $supplier->defaultTaxRate->name }} ({{ number_format((float) $supplier->defaultTaxRate->percent, 2, ',', '.') }}%)
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Desconto por defeito</label>
                    <div>{{ number_format((float) $supplier->default_discount_percent, 2, ',', '.') }}%</div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Lead time</label>
                    <div>{{ $supplier->lead_time_days !== null ? $supplier->lead_time_days . ' dias' : '-' }}</div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Valor minimo encomenda</label>
                    <div>{{ $supplier->minimum_order_value !== null ? number_format((float) $supplier->minimum_order_value, 2, ',', '.') . ' EUR' : '-' }}</div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Portes gratis a partir de</label>
                    <div>{{ $supplier->free_shipping_threshold !== null ? number_format((float) $supplier->free_shipping_threshold, 2, ',', '.') . ' EUR' : '-' }}</div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Metodo pagamento preferido</label>
                    <div>{{ $supplier->preferred_payment_method ?: '-' }}</div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Email habitual encomenda</label>
                    <div>{{ $supplier->habitual_order_email ?: '-' }}</div>
                </div>

                <div class="mb-0">
                    <label class="form-label">Metodo contacto preferido</label>
                    <div>{{ $contactMethodLabels[$supplier->preferred_contact_method ?? ''] ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <strong>Instrucoes de entrega</strong>
            </div>

            <div class="card-body">
                {!! nl2br(e($supplier->delivery_instructions ?: '-')) !!}
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <strong>Notas por defeito para compras</strong>
            </div>

            <div class="card-body">
                {!! nl2br(e($supplier->default_notes_for_purchases ?: '-')) !!}
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <strong>Observacoes internas</strong>
            </div>

            <div class="card-body">
                {!! nl2br(e($supplier->notes ?: '-')) !!}
            </div>
        </div>
    </div>
</div>

<section class="card shadow-sm mb-3">
    <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <strong>Conta corrente</strong>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-light text-dark border">Debito: {{ number_format((float) $accountTotals['debit'], 2, ',', '.') }} EUR</span>
            <span class="badge bg-light text-dark border">Credito: {{ number_format((float) $accountTotals['credit'], 2, ',', '.') }} EUR</span>
            <span class="badge {{ (float) $accountTotals['balance'] > 0 ? 'bg-danger' : 'bg-success' }}">
                Saldo: {{ number_format((float) $accountTotals['balance'], 2, ',', '.') }} EUR
            </span>
            <span class="badge bg-warning text-dark">Vencido: {{ number_format((float) $accountTotals['overdue'], 2, ',', '.') }} EUR</span>
        </div>
    </header>

    <div class="card-body">
        <div class="row g-3 mb-3">
            @if ($canUpdateSupplier)
                <div class="col-xl-8">
                    <form method="POST" action="{{ route('suppliers.account-entries.store', $supplier) }}" class="border rounded p-3 bg-light">
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
                                <input type="text" id="reference_type" name="reference_type" class="form-control" maxlength="100" value="{{ old('reference_type') }}" placeholder="fatura, documento, nota...">
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

            <div class="{{ $canUpdateSupplier ? 'col-xl-4' : 'col-12' }}">
                <form method="GET" action="{{ route('suppliers.show', $supplier) }}" class="border rounded p-3">
                    <div class="row g-2">
                        <div class="col-md-6 {{ $canUpdateSupplier ? '' : 'col-xl-3' }}">
                            <label for="account_date_from" class="form-label">Periodo de</label>
                            <input type="date" id="account_date_from" name="account_date_from" class="form-control" value="{{ $accountFilters['account_date_from'] }}">
                        </div>

                        <div class="col-md-6 {{ $canUpdateSupplier ? '' : 'col-xl-3' }}">
                            <label for="account_date_to" class="form-label">ate</label>
                            <input type="date" id="account_date_to" name="account_date_to" class="form-control" value="{{ $accountFilters['account_date_to'] }}">
                        </div>

                        <div class="col-md-12 {{ $canUpdateSupplier ? '' : 'col-xl-6' }} d-flex gap-2 align-items-end">
                            <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                            <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-outline-secondary">Limpar</a>
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
                                @if ($entry->isFromDirectPurchase() && auth()->user()?->can('purchases.view'))
                                    <div class="small mt-1">
                                        <a href="{{ route('purchase-direct-purchases.show', $entry->reference_id) }}" class="text-primary">
                                            Ver compra direta associada
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

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Contactos</strong>
        <span class="badge bg-light text-dark border">{{ $supplier->contacts->count() }}</span>
    </div>

    <div class="card-body">
        @if ($canUpdateSupplier)
            <form method="POST" action="{{ route('suppliers.contacts.store', $supplier) }}" class="border rounded p-3 mb-4 bg-light">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="new_contact_name" class="form-label">Nome</label>
                        <input
                            type="text"
                            name="name"
                            id="new_contact_name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="col-md-2">
                        <label for="new_contact_role" class="form-label">Funcao</label>
                        <input
                            type="text"
                            name="role"
                            id="new_contact_role"
                            class="form-control @error('role') is-invalid @enderror"
                            value="{{ old('role') }}"
                            maxlength="100"
                        >
                    </div>

                    <div class="col-md-2">
                        <label for="new_contact_department" class="form-label">Departamento</label>
                        <input
                            type="text"
                            name="department"
                            id="new_contact_department"
                            class="form-control @error('department') is-invalid @enderror"
                            value="{{ old('department') }}"
                            maxlength="100"
                        >
                    </div>

                    <div class="col-md-2">
                        <label for="new_contact_email" class="form-label">Email</label>
                        <input
                            type="email"
                            name="email"
                            id="new_contact_email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            maxlength="150"
                        >
                    </div>

                    <div class="col-md-1">
                        <label for="new_contact_phone" class="form-label">Telefone</label>
                        <input
                            type="text"
                            name="phone"
                            id="new_contact_phone"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone') }}"
                            maxlength="30"
                        >
                    </div>

                    <div class="col-md-2">
                        <label for="new_contact_mobile" class="form-label">Telemovel</label>
                        <input
                            type="text"
                            name="mobile"
                            id="new_contact_mobile"
                            class="form-control @error('mobile') is-invalid @enderror"
                            value="{{ old('mobile') }}"
                            maxlength="30"
                        >
                    </div>

                    <div class="col-md-8">
                        <label for="new_contact_notes" class="form-label">Notas</label>
                        <textarea
                            name="notes"
                            id="new_contact_notes"
                            rows="2"
                            class="form-control @error('notes') is-invalid @enderror"
                        >{{ old('notes') }}</textarea>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                name="is_primary"
                                id="new_contact_is_primary"
                                value="1"
                                class="form-check-input @error('is_primary') is-invalid @enderror"
                                @checked(old('is_primary'))
                            >
                            <label for="new_contact_is_primary" class="form-check-label">Principal</label>
                        </div>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                name="is_active"
                                id="new_contact_is_active"
                                value="1"
                                class="form-check-input @error('is_active') is-invalid @enderror"
                                @checked(old('is_active', true))
                            >
                            <label for="new_contact_is_active" class="form-check-label">Ativo</label>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Adicionar contacto</button>
                    </div>
                </div>
            </form>
        @endif

        @if ($supplier->contacts->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Funcao</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Telemovel</th>
                            <th>Principal</th>
                            <th>Ativo</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($supplier->contacts as $contact)
                            <tr>
                                <td>{{ $contact->name }}</td>
                                <td>
                                    {{ $contact->role ?: '-' }}
                                    @if (!empty($contact->department))
                                        <div class="small text-muted">{{ $contact->department }}</div>
                                    @endif
                                </td>
                                <td>{{ $contact->email ?: '-' }}</td>
                                <td>{{ $contact->phone ?: '-' }}</td>
                                <td>{{ $contact->mobile ?: '-' }}</td>
                                <td>
                                    @if ($contact->is_primary)
                                        <span class="badge bg-primary">Sim</span>
                                    @else
                                        <span class="badge bg-light text-dark">Nao</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($contact->is_active)
                                        <span class="badge bg-success">Sim</span>
                                    @else
                                        <span class="badge bg-secondary">Nao</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($canUpdateSupplier)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#editContact{{ $contact->id }}"
                                            aria-expanded="false"
                                            aria-controls="editContact{{ $contact->id }}"
                                        >
                                            Editar
                                        </button>

                                        <form
                                            method="POST"
                                            action="{{ route('suppliers.contacts.destroy', [$supplier, $contact]) }}"
                                            class="d-inline js-confirm-form"
                                            data-confirm-message="Remover este contacto?"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>

                            @if ($canUpdateSupplier)
                                <tr class="collapse" id="editContact{{ $contact->id }}">
                                    <td colspan="8" class="bg-light">
                                        <form method="POST" action="{{ route('suppliers.contacts.update', [$supplier, $contact]) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="row g-2">
                                                <div class="col-md-3">
                                                    <label class="form-label">Nome</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $contact->name }}" maxlength="150" required>
                                                </div>

                                                <div class="col-md-2">
                                                    <label class="form-label">Funcao</label>
                                                    <input type="text" name="role" class="form-control" value="{{ $contact->role }}" maxlength="100">
                                                </div>

                                                <div class="col-md-2">
                                                    <label class="form-label">Departamento</label>
                                                    <input type="text" name="department" class="form-control" value="{{ $contact->department }}" maxlength="100">
                                                </div>

                                                <div class="col-md-2">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" name="email" class="form-control" value="{{ $contact->email }}" maxlength="150">
                                                </div>

                                                <div class="col-md-1">
                                                    <label class="form-label">Telefone</label>
                                                    <input type="text" name="phone" class="form-control" value="{{ $contact->phone }}" maxlength="30">
                                                </div>

                                                <div class="col-md-2">
                                                    <label class="form-label">Telemovel</label>
                                                    <input type="text" name="mobile" class="form-control" value="{{ $contact->mobile }}" maxlength="30">
                                                </div>

                                                <div class="col-md-8">
                                                    <label class="form-label">Notas</label>
                                                    <textarea name="notes" rows="2" class="form-control">{{ $contact->notes }}</textarea>
                                                </div>

                                                <div class="col-md-2 d-flex align-items-end">
                                                    <div class="form-check">
                                                        <input
                                                            type="checkbox"
                                                            name="is_primary"
                                                            value="1"
                                                            class="form-check-input"
                                                            id="contact_primary_{{ $contact->id }}"
                                                            @checked($contact->is_primary)
                                                        >
                                                        <label for="contact_primary_{{ $contact->id }}" class="form-check-label">Principal</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-2 d-flex align-items-end">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <div class="form-check">
                                                        <input
                                                            type="checkbox"
                                                            name="is_active"
                                                            value="1"
                                                            class="form-check-input"
                                                            id="contact_active_{{ $contact->id }}"
                                                            @checked($contact->is_active)
                                                        >
                                                        <label for="contact_active_{{ $contact->id }}" class="form-check-label">Ativo</label>
                                                    </div>
                                                </div>

                                                <div class="col-12 d-flex justify-content-end">
                                                    <button type="submit" class="btn btn-sm btn-primary">Guardar contacto</button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-muted">Este fornecedor ainda nao tem contactos associados.</div>
        @endif
    </div>
</div>
@endsection
