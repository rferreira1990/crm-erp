@extends('layouts.admin')

@section('title', 'Ficha de Orçamento')

@section('content')
    @php
        $canUpdateBudget = auth()->user()?->can('budgets.update');
        $canDeleteBudget = auth()->user()?->can('budgets.delete');
        $isEditable = $budget->isEditable();
        $canEditLines = $canUpdateBudget && $isEditable;

        $budgetNumber = str_replace('ORC-', '', (string) $budget->code);
        $statusLabel = method_exists($budget, 'statusLabel') ? $budget->statusLabel() : ucfirst((string) $budget->status);

        $subtotalValue = (float) $budget->subtotal;
        $taxValue = (float) $budget->tax_total;
        $totalValue = (float) $budget->total;

        $statusActions = [];

        if ($canUpdateBudget) {
            if ($budget->canChangeToStatus('created')) {
                $statusActions[] = ['status' => 'created', 'label' => 'Finalizar orçamento', 'class' => 'btn btn-sm budget-primary-btn'];
            }

            if ($budget->canChangeToStatus('sent')) {
                $statusActions[] = ['status' => 'sent', 'label' => 'Marcar como enviado', 'class' => 'btn btn-sm budget-primary-btn'];
            }

            if ($budget->canChangeToStatus('waiting_response')) {
                $statusActions[] = ['status' => 'waiting_response', 'label' => 'Aguarda resposta', 'class' => 'btn btn-sm btn-outline-warning'];
            }

            if ($budget->canChangeToStatus('accepted')) {
                $statusActions[] = ['status' => 'accepted', 'label' => 'Aceite', 'class' => 'btn btn-sm btn-outline-success'];
            }

            if ($budget->canChangeToStatus('rejected')) {
                $statusActions[] = ['status' => 'rejected', 'label' => 'Não aceite', 'class' => 'btn btn-sm btn-outline-danger'];
            }
        }

        $companyProfile = $budget->owner?->companyProfile;

        $hasMailConfig = $canUpdateBudget
            && in_array($budget->status, [\App\Models\Budget::STATUS_CREATED, \App\Models\Budget::STATUS_SENT, \App\Models\Budget::STATUS_WAITING_RESPONSE], true)
            && !empty($companyProfile?->mail_host)
            && !empty($companyProfile?->mail_port)
            && !empty($companyProfile?->mail_username)
            && !empty($companyProfile?->mail_password)
            && !empty($companyProfile?->mail_encryption)
            && !empty($companyProfile?->mail_from_address)
            && !empty($companyProfile?->mail_from_name);

        $defaultRecipientName = old('recipient_name', $budget->customer?->contact_person ?: $budget->customer?->name ?: '');
        $defaultRecipientEmail = old('recipient_email', $budget->customer?->email ?: '');
        $defaultEmailNotes = old('email_notes', '');

        $newLineTaxRateSelectId = 'new-line-tax-rate-id';
        $newLineTaxReasonWrapperId = 'new-line-tax-reason-wrapper';
        $selectedNewLineTaxRate = $taxRates->firstWhere('id', (int) old('tax_rate_id'));
        $newLineIsExempt = $selectedNewLineTaxRate ? (bool) $selectedNewLineTaxRate->is_exempt : false;

        $hasEmailLogs = $budget->relationLoaded('emailLogs')
            ? $budget->emailLogs->isNotEmpty()
            : false;
    @endphp

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1">Ficha de Orçamento</h2>
            <div class="text-muted">
                Documento {{ $budget->code }}
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('budgets.pdf', $budget) }}" class="btn btn-outline-primary">
                Gerar PDF
            </a>

            @if ($hasMailConfig)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendBudgetEmailModal">
                    {{ $hasEmailLogs ? 'Reenviar por email' : 'Enviar por email' }}
                </button>
            @endif

            @if ($canDeleteBudget && $budget->isDeletable())
                <form method="POST" action="{{ route('budgets.destroy', $budget) }}" onsubmit="return confirm('Apagar este orçamento?');">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-outline-danger">
                        Apagar
                    </button>
                </form>
            @endif

            <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary">
                Voltar
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {!! session('error') !!}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">
                Não foi possível guardar o orçamento. Corrige os seguintes erros:
            </div>

            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="budget-sheet-card">
        <div class="budget-sheet-tab">GERAL</div>

        <div class="budget-sheet-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div class="h5 mb-0">Ficha de Orçamento</div>

                @if (count($statusActions) > 0)
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($statusActions as $action)
                            <form method="POST" action="{{ route('budgets.change-status', $budget) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $action['status'] }}">

                                <button type="submit" class="{{ $action['class'] }}">
                                    {{ $action['label'] }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($canEditLines)
                <form method="POST" action="{{ route('budgets.update', $budget) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-4">

                        <div class="col-lg-2">
                            <div class="budget-field">
                                <label class="budget-field-label">Nº</label>
                                <div class="budget-field-readonly">
                                    {{ ltrim($budgetNumber, '0') !== '' ? ltrim($budgetNumber, '0') : '0' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="budget-field">
                                <label for="budget_date" class="budget-field-label">Data</label>
                                <input
                                    type="date"
                                    name="budget_date"
                                    id="budget_date"
                                    class="form-control @error('budget_date') is-invalid @enderror"
                                    value="{{ old('budget_date', $budget->budget_date?->format('Y-m-d') ?? now()->toDateString()) }}"
                                    required
                                >
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="budget-field">
                                <label for="valid_until" class="budget-field-label">Validade</label>
                                <input
                                    type="date"
                                    name="valid_until"
                                    id="valid_until"
                                    class="form-control @error('valid_until') is-invalid @enderror"
                                    value="{{ old('valid_until', $budget->valid_until?->format('Y-m-d')) }}"
                                >
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="budget-field">
                                <label class="budget-field-label">Cliente</label>
                                <div class="budget-field-readonly">
                                    {{ $budget->customer->name ?? '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="budget-field">
                                <label for="designation" class="budget-field-label">Designação</label>
                                <input
                                    type="text"
                                    name="designation"
                                    id="designation"
                                    class="form-control @error('designation') is-invalid @enderror"
                                    value="{{ old('designation', $budget->designation) }}"
                                    maxlength="255"
                                >
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="budget-field">
                                <label for="zone" class="budget-field-label">Zona</label>
                                <input
                                    type="text"
                                    name="zone"
                                    id="zone"
                                    class="form-control @error('zone') is-invalid @enderror"
                                    value="{{ old('zone', $budget->zone) }}"
                                    maxlength="255"
                                >
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="budget-field">
                                <label for="external_reference" class="budget-field-label">Referência externa</label>
                                <input
                                    type="text"
                                    name="external_reference"
                                    id="external_reference"
                                    class="form-control @error('external_reference') is-invalid @enderror"
                                    value="{{ old('external_reference', $budget->external_reference) }}"
                                    maxlength="255"
                                >
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="budget-field">
                                <label for="payment_term_id" class="budget-field-label">Condição de pagamento</label>
                                <select
                                    name="payment_term_id"
                                    id="payment_term_id"
                                    class="form-select @error('payment_term_id') is-invalid @enderror"
                                >
                                    <option value="">Selecionar</option>

                                    @foreach ($paymentTerms as $paymentTerm)
                                        <option
                                            value="{{ $paymentTerm->id }}"
                                            {{ (int) old('payment_term_id', $budget->payment_term_id) === (int) $paymentTerm->id ? 'selected' : '' }}
                                        >
                                            {{ $paymentTerm->displayLabel() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="budget-field">
                                <label for="project_name" class="budget-field-label">Projeto</label>
                                <input
                                    type="text"
                                    name="project_name"
                                    id="project_name"
                                    class="form-control @error('project_name') is-invalid @enderror"
                                    value="{{ old('project_name', $budget->project_name) }}"
                                    maxlength="255"
                                >
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="budget-field">
                                <label for="notes" class="budget-field-label">Observações</label>
                                <textarea
                                    name="notes"
                                    id="notes"
                                    rows="4"
                                    class="form-control @error('notes') is-invalid @enderror"
                                >{{ old('notes', $budget->notes) }}</textarea>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <button type="submit" class="btn btn-outline-primary">
                                Guardar cabeçalho
                            </button>
                        </div>
                    </div>
                </form>
            @else
                <div class="row g-4">

                    <div class="col-lg-2">
                        <div class="budget-field">
                            <label class="budget-field-label">Nº</label>
                            <div class="budget-field-readonly">
                                {{ ltrim($budgetNumber, '0') !== '' ? ltrim($budgetNumber, '0') : '0' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-2">
                        <div class="budget-field">
                            <label class="budget-field-label">Data</label>
                            <div class="budget-field-readonly">
                                {{ $budget->budget_date?->format('Y-m-d') ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-2">
                        <div class="budget-field">
                            <label class="budget-field-label">Validade</label>
                            <div class="budget-field-readonly">
                                {{ $budget->valid_until?->format('Y-m-d') ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="budget-field">
                            <label class="budget-field-label">Cliente</label>
                            <div class="budget-field-readonly">
                                {{ $budget->customer->name ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="budget-field">
                            <label class="budget-field-label">Designação</label>
                            <div class="budget-field-readonly">
                                {{ $budget->designation ?: '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="budget-field">
                            <label class="budget-field-label">Zona</label>
                            <div class="budget-field-readonly">
                                {{ $budget->zone ?: '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="budget-field">
                            <label class="budget-field-label">Referência externa</label>
                            <div class="budget-field-readonly">
                                {{ $budget->external_reference ?: '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="budget-field">
                            <label class="budget-field-label">Condição de pagamento</label>
                            <div class="budget-field-readonly">
                                {{ $budget->paymentTerm?->displayLabel() ?: '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="budget-field">
                            <label class="budget-field-label">Projeto</label>
                            <div class="budget-field-readonly">
                                {{ $budget->project_name ?: '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="budget-field">
                            <label class="budget-field-label">Observações</label>
                            <div class="budget-field-readonly" style="min-height: 120px;">
                                {!! nl2br(e($budget->notes ?: '—')) !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="border-top">
            <button
                class="budget-section-toggle"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#budget-details-section"
                aria-expanded="false"
                aria-controls="#budget-details-section"
            >
                <span class="budget-chevron">▼</span>
                <span>Detalhes do Orçamento</span>
            </button>

            <div id="budget-details-section" class="collapse">
                <div class="budget-section-content">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="budget-field">
                                <label class="budget-field-label">Vendedor</label>
                                <div class="budget-field-readonly">
                                    {{ $budget->creator->name ?? '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="budget-field">
                                <label class="budget-field-label">Situação</label>
                                <div class="budget-field-readonly">
                                    <span class="budget-status-badge budget-status-{{ $budget->status }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="budget-field">
                                <label class="budget-field-label">Data da Situação</label>
                                <div class="budget-field-readonly">
                                    {{ $budget->updated_at?->format('Y-m-d') ?? $budget->created_at?->format('Y-m-d') ?? '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="budget-field">
                                <label class="budget-field-label">Cliente</label>
                                <div class="budget-field-readonly">
                                    {{ $budget->customer->name ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (!$hasMailConfig && in_array($budget->status, [\App\Models\Budget::STATUS_CREATED, \App\Models\Budget::STATUS_SENT, \App\Models\Budget::STATUS_WAITING_RESPONSE], true))
            <div class="alert alert-warning m-3">
                Para enviar por email, tens de completar primeiro a configuração SMTP nos dados da empresa.
            </div>
        @endif
    </div>

    <div class="budget-sheet-card">
        <div class="budget-articles-header">
            <div class="budget-articles-title">Artigos</div>
            <div class="budget-articles-subtitle">Preços sem IVA incluído</div>
        </div>

        <div class="budget-sheet-body pt-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 budget-articles-table">
                    <thead>
                        <tr>
                            <th style="min-width: 220px;">Artigo</th>
                            <th style="min-width: 260px;">Designação</th>
                            <th style="min-width: 90px;">Qtd.</th>
                            <th style="min-width: 100px;">Unidade</th>
                            <th style="min-width: 130px;">Preço Unitário</th>
                            <th style="min-width: 90px;">%Desc.</th>
                            <th style="min-width: 90px;">%IVA</th>
                            <th style="min-width: 130px;">Valor</th>
                            @if ($canEditLines)
                                <th style="min-width: 220px;">Ações</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @if ($canEditLines)
                            <tr class="budget-articles-search-row">
                                <td colspan="9">
                                    <form method="POST" action="{{ route('budgets.items.store', $budget) }}" class="row g-2 align-items-end">
                                        @csrf

                                        <div class="col-xl-4 col-lg-5">
                                            <label for="item_id" class="form-label mb-1">Pesquisar Artigo</label>
                                            <select
                                                name="item_id"
                                                id="item_id"
                                                class="form-select @error('item_id') is-invalid @enderror"
                                                required
                                            >
                                                <option value="">Selecionar artigo</option>
                                                @foreach ($availableItems as $item)
                                                    <option
                                                        value="{{ $item->id }}"
                                                        {{ old('item_id') == $item->id ? 'selected' : '' }}
                                                    >
                                                        {{ $item->code }} - {{ $item->name }}
                                                        @if ($item->sale_price !== null)
                                                            | {{ number_format((float) $item->sale_price, 2, ',', '.') }} €
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-xl-1 col-lg-2">
                                            <label for="quantity" class="form-label mb-1">Qtd.</label>
                                            <input
                                                type="number"
                                                name="quantity"
                                                id="quantity"
                                                class="form-control @error('quantity') is-invalid @enderror"
                                                value="{{ old('quantity', 1) }}"
                                                min="0.001"
                                                step="0.001"
                                                required
                                            >
                                        </div>

                                        <div class="col-xl-1 col-lg-2">
                                            <label for="discount_percent" class="form-label mb-1">%Desc.</label>
                                            <input
                                                type="number"
                                                name="discount_percent"
                                                id="discount_percent"
                                                class="form-control @error('discount_percent') is-invalid @enderror"
                                                value="{{ old('discount_percent', 0) }}"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                            >
                                        </div>

                                        <div
                                            class="col-xl-3 col-lg-4 tax-reason-wrapper"
                                            id="{{ $newLineTaxReasonWrapperId }}"
                                            style="{{ $newLineIsExempt ? '' : 'display:none;' }}"
                                        >
                                            <label for="tax_exemption_reason_id" class="form-label mb-1">Motivo isenção</label>
                                            <select
                                                name="tax_exemption_reason_id"
                                                id="tax_exemption_reason_id"
                                                class="form-select tax-exemption-reason-select @error('tax_exemption_reason_id') is-invalid @enderror"
                                            >
                                                <option value="">Motivo isenção</option>

                                                @foreach ($taxExemptionReasons as $reason)
                                                    <option
                                                        value="{{ $reason->id }}"
                                                        {{ (int) old('tax_exemption_reason_id') === (int) $reason->id ? 'selected' : '' }}
                                                    >
                                                        {{ $reason->code }} - {{ $reason->description }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12 d-flex flex-wrap gap-2">
                                            <button type="submit" class="btn budget-primary-btn">
                                                Adicionar artigo
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif

                        @forelse ($budget->items as $line)
                            @php
                                $collapseId = 'line-notes-' . $line->id;
                                $taxReasonWrapperId = 'tax-reason-wrapper-' . $line->id;
                                $taxRateSelectId = 'tax-rate-id-' . $line->id;

                                $currentTaxRate = $taxRates->firstWhere('id', $line->tax_rate_id);
                                $currentIsExempt = $currentTaxRate
                                    ? (bool) $currentTaxRate->is_exempt
                                    : ((float) $line->tax_percent === 0.0 && !empty($line->tax_exemption_reason));
                            @endphp

                            <tr>
                                @if ($canEditLines)
                                    <td>
                                        <form method="POST" action="{{ route('budgets.items.update', [$budget, $line]) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="fw-semibold">{{ $line->item_code ?: '—' }}</div>
                                            <div class="budget-muted-line">
                                                {{ $line->item_type === 'service' ? 'Serviço' : 'Artigo' }}
                                            </div>
                                    </td>

                                    <td>
                                            <div class="fw-semibold">{{ $line->item_name }}</div>
                                            @if ($line->description)
                                                <div class="budget-muted-line">{{ $line->description }}</div>
                                            @endif
                                    </td>

                                    <td>
                                            <input
                                                type="number"
                                                name="quantity"
                                                class="form-control form-control-sm"
                                                value="{{ number_format((float) $line->quantity, 3, '.', '') }}"
                                                min="0.001"
                                                step="0.001"
                                                required
                                            >
                                    </td>

                                    <td>
                                            {{ $line->unit_name ?: '—' }}
                                    </td>

                                    <td>
                                            <input
                                                type="number"
                                                name="unit_price"
                                                class="form-control form-control-sm"
                                                value="{{ number_format((float) $line->unit_price, 2, '.', '') }}"
                                                min="0"
                                                step="0.01"
                                                required
                                            >
                                    </td>

                                    <td>
                                            <input
                                                type="number"
                                                name="discount_percent"
                                                class="form-control form-control-sm"
                                                value="{{ number_format((float) $line->discount_percent, 2, '.', '') }}"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                            >
                                    </td>

                                    <td>
                                            <div class="d-flex flex-column gap-2">
                                                <select
                                                    name="tax_rate_id"
                                                    id="{{ $taxRateSelectId }}"
                                                    class="form-select form-select-sm tax-rate-select"
                                                    data-target="#{{ $taxReasonWrapperId }}"
                                                    required
                                                >
                                                    @foreach ($taxRates as $taxRate)
                                                        <option
                                                            value="{{ $taxRate->id }}"
                                                            data-is-exempt="{{ $taxRate->is_exempt ? '1' : '0' }}"
                                                            data-default-reason-id="{{ $taxRate->exemption_reason_id }}"
                                                            {{ (int) $line->tax_rate_id === (int) $taxRate->id ? 'selected' : '' }}
                                                        >
                                                            {{ number_format((float) $taxRate->percent, 2, ',', '.') }}%
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <div
                                                    id="{{ $taxReasonWrapperId }}"
                                                    class="tax-reason-wrapper"
                                                    style="{{ $currentIsExempt ? '' : 'display:none;' }}"
                                                >
                                                    <select
                                                        name="tax_exemption_reason_id"
                                                        class="form-select form-select-sm tax-exemption-reason-select"
                                                    >
                                                        <option value="">Motivo isenção</option>

                                                        @foreach ($taxExemptionReasons as $reason)
                                                            <option
                                                                value="{{ $reason->id }}"
                                                                {{ (int) $line->tax_exemption_reason_id === (int) $reason->id ? 'selected' : '' }}
                                                            >
                                                                {{ $reason->code }} - {{ $reason->description }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                    </td>

                                    <td class="fw-semibold">
                                            {{ number_format((float) $line->total, 2, ',', '.') }} €
                                    </td>

                                    <td>
                                            <div class="d-flex flex-column gap-2">
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                                        Guardar
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#{{ $collapseId }}"
                                                        aria-expanded="false"
                                                        aria-controls="{{ $collapseId }}"
                                                    >
                                                        ▼
                                                    </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('budgets.items.destroy', [$budget, $line]) }}"
                                            onsubmit="return confirm('Remover esta linha do orçamento?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Remover
                                            </button>
                                        </form>
                                                </div>

                                                @if (!empty($line->notes))
                                                    <div class="budget-muted-line">
                                                        Com observações
                                                    </div>
                                                @endif
                                            </div>
                                    </td>
                                @else
                                    <td>
                                        <div class="fw-semibold">{{ $line->item_code ?: '—' }}</div>
                                        <div class="budget-muted-line">
                                            {{ $line->item_type === 'service' ? 'Serviço' : 'Artigo' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $line->item_name }}</div>
                                        @if ($line->description)
                                            <div class="budget-muted-line">{{ $line->description }}</div>
                                        @endif
                                    </td>

                                    <td>{{ number_format((float) $line->quantity, 3, ',', '.') }}</td>
                                    <td>{{ $line->unit_name ?: '—' }}</td>
                                    <td>{{ number_format((float) $line->unit_price, 2, ',', '.') }} €</td>
                                    <td>{{ number_format((float) $line->discount_percent, 2, ',', '.') }}%</td>
                                    <td>
                                        {{ number_format((float) $line->tax_percent, 2, ',', '.') }}%
                                        @if ($line->tax_exemption_reason)
                                            <div class="budget-muted-line">{{ $line->tax_exemption_reason }}</div>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ number_format((float) $line->total, 2, ',', '.') }} €</td>
                                @endif
                            </tr>

                            @if ($canEditLines)
                                <tr class="collapse" id="{{ $collapseId }}">
                                    <td colspan="9" class="bg-light">
                                        <form method="POST" action="{{ route('budgets.items.update', [$budget, $line]) }}">
                                            @csrf
                                            @method('PUT')

                                            <input type="hidden" name="quantity" value="{{ number_format((float) $line->quantity, 3, '.', '') }}">
                                            <input type="hidden" name="unit_price" value="{{ number_format((float) $line->unit_price, 2, '.', '') }}">
                                            <input type="hidden" name="discount_percent" value="{{ number_format((float) $line->discount_percent, 2, '.', '') }}">
                                            <input type="hidden" name="tax_rate_id" value="{{ (int) $line->tax_rate_id }}">
                                            <input type="hidden" name="tax_exemption_reason_id" value="{{ (int) $line->tax_exemption_reason_id }}">

                                            <label for="notes-{{ $line->id }}" class="form-label mb-1">
                                                Observações da linha
                                            </label>

                                            <textarea
                                                name="notes"
                                                id="notes-{{ $line->id }}"
                                                rows="3"
                                                class="form-control"
                                                placeholder="Escreve aqui as observações desta linha"
                                            >{{ $line->notes }}</textarea>

                                            <div class="mt-2">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    Guardar observações
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @elseif (!empty($line->notes))
                                <tr class="table-light">
                                    <td colspan="8">
                                        <strong>Observações:</strong>
                                        <div class="budget-line-note">{{ $line->notes }}</div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $canEditLines ? 9 : 8 }}" class="text-center text-muted py-4">
                                    Este orçamento ainda não tem artigos adicionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="budget-sheet-card">
        <div class="budget-sheet-body pb-0">
            <div class="h5 mb-4">Valores Totais</div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="budget-total-box">
                        <div class="budget-total-label">Valor s/IVA</div>
                        <div class="h4 mb-0">{{ number_format($subtotalValue, 2, ',', '.') }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="budget-total-box">
                        <div class="budget-total-label">Valor IVA</div>
                        <div class="h4 mb-0">{{ number_format($taxValue, 2, ',', '.') }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="budget-total-box">
                        <div class="budget-total-label">Valor Total</div>
                        <div class="h4 mb-0">{{ number_format($totalValue, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="budget-total-strip">
            <span>Valor Total</span>
            <span>{{ number_format($totalValue, 2, ',', '.') }}</span>
        </div>
    </div>

    <div class="budget-sheet-card">
        <div class="budget-sheet-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div class="h5 mb-0">Histórico de emails</div>
                <div class="text-muted small">
                    Registo de todos os envios e reenvios deste orçamento
                </div>
            </div>

            @if ($budget->relationLoaded('emailLogs') && $budget->emailLogs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 170px;">Data / Hora</th>
                                <th style="min-width: 180px;">Enviado por</th>
                                <th style="min-width: 180px;">Destinatário</th>
                                <th style="min-width: 220px;">Email</th>
                                <th style="min-width: 240px;">Assunto</th>
                                <th style="min-width: 280px;">Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($budget->emailLogs as $log)
                                <tr>
                                    <td>{{ $log->sent_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                                    <td>{{ $log->sender?->name ?? '—' }}</td>
                                    <td>{{ $log->recipient_name ?: '—' }}</td>
                                    <td>{{ $log->recipient_email }}</td>
                                    <td>{{ $log->subject ?: '—' }}</td>
                                    <td>{!! nl2br(e($log->message ?: '—')) !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-muted">
                    Ainda não existe histórico de emails para este orçamento.
                </div>
            @endif
        </div>
    </div>

    @if ($hasMailConfig)
        <div class="modal fade" id="sendBudgetEmailModal" tabindex="-1" aria-labelledby="sendBudgetEmailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sendBudgetEmailModalLabel">
                            {{ $hasEmailLogs ? 'Reenviar orçamento por email' : 'Enviar orçamento por email' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <form method="POST" action="{{ route('budgets.send-email', $budget) }}">
                        @csrf

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="recipient_name" class="form-label">Nome do destinatário</label>
                                    <input
                                        type="text"
                                        name="recipient_name"
                                        id="recipient_name"
                                        class="form-control @error('recipient_name') is-invalid @enderror"
                                        value="{{ $defaultRecipientName }}"
                                        maxlength="150"
                                    >
                                    @error('recipient_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="recipient_email" class="form-label">Email do destinatário</label>
                                    <input
                                        type="email"
                                        name="recipient_email"
                                        id="recipient_email"
                                        class="form-control @error('recipient_email') is-invalid @enderror"
                                        value="{{ $defaultRecipientEmail }}"
                                        maxlength="150"
                                        required
                                    >
                                    @error('recipient_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="email_notes" class="form-label">Observações no email</label>
                                    <textarea
                                        name="email_notes"
                                        id="email_notes"
                                        rows="6"
                                        class="form-control @error('email_notes') is-invalid @enderror"
                                        placeholder="Escreve aqui uma mensagem adicional para o cliente..."
                                    >{{ $defaultEmailNotes }}</textarea>
                                    @error('email_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info mb-0">
                                        O orçamento será enviado em anexo em PDF.
                                        @if ($hasEmailLogs)
                                            Este envio ficará registado como <strong>novo reenvio</strong> no histórico.
                                        @else
                                            Após envio com sucesso, o estado passa para <strong>Enviado</strong>.
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Cancelar
                            </button>

                            <button type="submit" class="btn btn-primary">
                                {{ $hasEmailLogs ? 'Confirmar reenvio' : 'Confirmar envio' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@if ($canEditLines || session('open_send_email_modal') || $errors->has('recipient_name') || $errors->has('recipient_email') || $errors->has('email_notes'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.tax-rate-select').forEach(function (select) {
                    const targetSelector = select.getAttribute('data-target');
                    const wrapper = document.querySelector(targetSelector);

                    if (wrapper) {
                        const reasonSelect = wrapper.querySelector('.tax-exemption-reason-select');

                        const toggleReasonField = function () {
                            const selectedOption = select.options[select.selectedIndex];
                            const isExempt = selectedOption?.dataset?.isExempt === '1';
                            const defaultReasonId = selectedOption?.dataset?.defaultReasonId || '';

                            wrapper.style.display = isExempt ? 'block' : 'none';

                            if (!isExempt && reasonSelect) {
                                reasonSelect.value = '';
                            }

                            if (isExempt && reasonSelect && !reasonSelect.value && defaultReasonId) {
                                reasonSelect.value = defaultReasonId;
                            }
                        };

                        select.addEventListener('change', toggleReasonField);
                        toggleReasonField();
                    }
                });

                @if (session('open_send_email_modal') || $errors->has('recipient_name') || $errors->has('recipient_email') || $errors->has('email_notes'))
                    const sendEmailModalElement = document.getElementById('sendBudgetEmailModal');

                    if (sendEmailModalElement && typeof bootstrap !== 'undefined') {
                        const sendEmailModal = new bootstrap.Modal(sendEmailModalElement);
                        sendEmailModal.show();
                    }
                @endif
            });
        </script>
    @endpush
@endif
