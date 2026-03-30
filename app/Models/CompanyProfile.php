<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyProfile extends Model
{
    protected $fillable = [
        'owner_id',
        'company_name',
        'address_line_1',
        'address_line_2',
        'city',
        'district',
        'postal_code',
        'postal_code_suffix',
        'postal_designation',
        'country_code',
        'tax_number',
        'phone',
        'fax',
        'contact_person',
        'email',
        'website',
        'share_capital',
        'registry_office',
        'logo_path',
        'bank_name',
        'bank_iban',
        'bank_bic_swift',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'mail_default_cc',
        'mail_default_bcc',
        'budget_default_pdf_template',
        'budget_default_vat_mode',
    ];

    protected $casts = [
        'share_capital' => 'decimal:2',
        'mail_password' => 'encrypted',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getFullPostalCodeAttribute(): ?string
    {
        if (!$this->postal_code) {
            return null;
        }

        if (!$this->postal_code_suffix) {
            return $this->postal_code;
        }

        return $this->postal_code . '-' . $this->postal_code_suffix;
    }
}
