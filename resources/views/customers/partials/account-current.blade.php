@php
    $canManageCustomerAccount = auth()->user()?->can('customers.edit');
    $selectedType = old('type', \App\Models\CustomerAccountEntry::TYPE_DEBIT);
@endphp

<section class="card mt-3" id="conta-corrente">
    <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="card-title mb-0">Conta corrente</h2>
        <span class="badge bg-light text-dark border">{{ $accountEntries->count() }} movimento(s)</span>
    </header>

    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="border rounded p-2 h-100">
                    <div class="small text-muted">Total debito</div>
                    <div class="fw-semibold">{{ number_format((float) $accountTotals['debit'], 2, ',', '.') }} &euro;</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2 h-100">
                    <div class="small text-muted">Total credito</div>
                    <div class="fw-semibold">{{ number_format((float) $accountTotals['credit'], 2, ',', '.') }} &euro;</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2 h-100">
                    <div class="small text-muted">Saldo</div>
                    <div class="fw-semibold {{ (float) $accountTotals['balance'] >= 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $accountTotals['balance'], 2, ',', '.') }} &euro;</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2 h-100">
                    <div class="small text-muted">Vencido</div>
                    <div class="fw-semibold text-danger">{{ number_format((float) $accountTotals['overdue'], 2, ',', '.') }} &euro;</div>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('customers.show', $customer) }}" class="row g-2 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="account_date_from">Data de</label>
                <input type="date" id="account_date_from" name="account_date_from" class="form-control form-control-sm" value="{{ $accountFilters['account_date_from'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="account_date_to">Data ate</label>
                <input type="date" id="account_date_to" name="account_date_to" class="form-control form-control-sm" value="{{ $accountFilters['account_date_to'] }}">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
                <a href="{{ route('customers.show', $customer) }}#conta-corrente" class="btn btn-sm btn-light border">Limpar</a>
            </div>
        </form>

        @if ($canManageCustomerAccount)
            <div class="border rounded p-3 bg-light mb-3">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-danger js-customer-account-type" data-type="{{ \App\Models\CustomerAccountEntry::TYPE_DEBIT }}">Novo debito</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary js-customer-account-type" data-type="{{ \App\Models\CustomerAccountEntry::TYPE_CREDIT }}">Novo credito</button>
                    <button type="button" class="btn btn-sm btn-outline-success js-customer-account-type" data-type="{{ \App\Models\CustomerAccountEntry::TYPE_PAYMENT }}">Registar recebimento</button>
                </div>

                <form method="POST" action="{{ route('customers.account-entries.store', $customer) }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label" for="customer_account_entry_date">Data</label>
                            <input type="date" id="customer_account_entry_date" name="entry_date" class="form-control form-control-sm @error('entry_date') is-invalid @enderror" value="{{ old('entry_date', now()->toDateString()) }}" required>
                            @error('entry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="customer_account_entry_type">Tipo</label>
                            <select id="customer_account_entry_type" name="type" class="form-select form-select-sm @error('type') is-invalid @enderror" required>
                                @foreach ($accountEntryTypes as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}" @selected($selectedType === $typeKey)>{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="customer_account_entry_amount">Valor</label>
                            <input type="number" id="customer_account_entry_amount" name="amount" class="form-control form-control-sm @error('amount') is-invalid @enderror" min="0.01" step="0.01" value="{{ old('amount') }}" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="customer_account_entry_due_date">Vencimento</label>
                            <input type="date" id="customer_account_entry_due_date" name="due_date" class="form-control form-control-sm @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                            @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="customer_account_entry_description">Descricao</label>
                            <input type="text" id="customer_account_entry_description" name="description" class="form-control form-control-sm @error('description') is-invalid @enderror" maxlength="255" value="{{ old('description') }}" required>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="customer_account_reference_type">Referencia tipo</label>
                            <input type="text" id="customer_account_reference_type" name="reference_type" class="form-control form-control-sm @error('reference_type') is-invalid @enderror" maxlength="100" value="{{ old('reference_type') }}">
                            @error('reference_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="customer_account_reference_id">Referencia id</label>
                            <input type="number" id="customer_account_reference_id" name="reference_id" class="form-control form-control-sm @error('reference_id') is-invalid @enderror" min="1" step="1" value="{{ old('reference_id') }}">
                            @error('reference_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="customer_account_notes">Notas</label>
                            <input type="text" id="customer_account_notes" name="notes" class="form-control form-control-sm @error('notes') is-invalid @enderror" maxlength="5000" value="{{ old('notes') }}">
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-primary w-100">Guardar movimento</button>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descricao</th>
                        <th>Tipo</th>
                        <th>Vencimento</th>
                        <th class="text-end">Debito</th>
                        <th class="text-end">Credito</th>
                        <th class="text-end">Saldo</th>
                        <th>Utilizador</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accountEntries as $entry)
                        <tr>
                            <td>{{ $entry->entry_date?->format('d/m/Y') ?: '-' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $entry->description }}</div>
                                @if ($entry->notes)
                                    <div class="small text-muted">{{ $entry->notes }}</div>
                                @endif
                            </td>
                            <td>{{ $entry->typeLabel() }}</td>
                            <td>{{ $entry->due_date?->format('d/m/Y') ?: '-' }}</td>
                            <td class="text-end">{{ (float) $entry->debit_amount > 0 ? number_format((float) $entry->debit_amount, 2, ',', '.') : '-' }}</td>
                            <td class="text-end">{{ (float) $entry->credit_amount > 0 ? number_format((float) $entry->credit_amount, 2, ',', '.') : '-' }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $entry->running_balance, 2, ',', '.') }}</td>
                            <td>{{ $entry->user?->name ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-muted">Sem movimentos de conta corrente para os filtros selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

@push('scripts')
    <script src="{{ asset('porto/js/pages/customer-account-current.js') }}"></script>
@endpush
