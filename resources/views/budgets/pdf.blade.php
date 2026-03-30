@php
    $resolvedTemplate = in_array(($template ?? 'commercial'), ['commercial', 'technical'], true)
        ? $template
        : 'commercial';

    $resolvedVatMode = in_array(($vatMode ?? 'with_vat'), ['with_vat', 'without_vat_with_notice'], true)
        ? $vatMode
        : 'with_vat';
@endphp

@include('budgets.pdf.' . $resolvedTemplate, [
    'budget' => $budget,
    'companyProfile' => $companyProfile ?? null,
    'template' => $resolvedTemplate,
    'vatMode' => $resolvedVatMode,
    'showVatValues' => $showVatValues ?? ($resolvedVatMode === 'with_vat'),
    'showVatNotice' => $showVatNotice ?? ($resolvedVatMode === 'without_vat_with_notice'),
    'vatNoticeText' => $vatNoticeText ?? 'Ao valor apresentado acresce IVA à taxa legal em vigor.',
])
