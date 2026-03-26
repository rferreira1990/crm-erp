<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;
    use BelongsToOwner;

    protected $fillable = [
        'owner_id',
        'code',
        'designation',
        'customer_id',
        'status',
        'budget_date',
        'zone',
        'project_name',
        'notes',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'created_by',
        'updated_by',

        'snapshot_generated_at',

        'snapshot_company_name',
        'snapshot_company_address_line_1',
        'snapshot_company_address_line_2',
        'snapshot_company_city',
        'snapshot_company_district',
        'snapshot_company_postal_code',
        'snapshot_company_postal_code_suffix',
        'snapshot_company_postal_designation',
        'snapshot_company_country_code',
        'snapshot_company_tax_number',
        'snapshot_company_phone',
        'snapshot_company_fax',
        'snapshot_company_contact_person',
        'snapshot_company_email',
        'snapshot_company_website',
        'snapshot_company_share_capital',
        'snapshot_company_registry_office',
        'snapshot_company_logo_path',
        'snapshot_company_bank_name',
        'snapshot_company_bank_iban',
        'snapshot_company_bank_bic_swift',

        'snapshot_customer_code',
        'snapshot_customer_name',
        'snapshot_customer_nif',
        'snapshot_customer_email',
        'snapshot_customer_phone',
        'snapshot_customer_mobile',
        'snapshot_customer_contact_person',
        'snapshot_customer_address_line_1',
        'snapshot_customer_address_line_2',
        'snapshot_customer_postal_code',
        'snapshot_customer_city',
        'snapshot_customer_country',
    ];

    protected $casts = [
        'budget_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'snapshot_generated_at' => 'datetime',
        'snapshot_company_share_capital' => 'decimal:2',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CREATED = 'created';
    public const STATUS_SENT = 'sent';
    public const STATUS_WAITING_RESPONSE = 'waiting_response';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    protected static function booted(): void
    {
        static::creating(function (Budget $budget) {
            if (empty($budget->code)) {
                $budget->code = static::generateSequentialCode();
            }

            if (empty($budget->status)) {
                $budget->status = self::STATUS_DRAFT;
            }
        });
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_CREATED,
            self::STATUS_SENT,
            self::STATUS_WAITING_RESPONSE,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
        ];
    }

    public static function generateSequentialCode(): string
    {
        $query = static::query()
            ->where('code', 'like', 'ORC-%');

        if (auth()->check()) {
            $query->where('owner_id', auth()->id());
        }

        $lastCode = $query
            ->orderByRaw("
                CAST(
                    CASE
                        WHEN code REGEXP '^ORC-[0-9]+$' THEN SUBSTRING(code, 5)
                        ELSE 0
                    END AS UNSIGNED
                ) DESC
            ")
            ->value('code');

        $nextNumber = 1;

        if ($lastCode && preg_match('/^ORC-(\d+)$/', $lastCode, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return 'ORC-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(BudgetEmailLog::class)->orderByDesc('sent_at')->orderByDesc('id');
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isDeletable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Rascunho',
            self::STATUS_CREATED => 'Criado',
            self::STATUS_SENT => 'Enviado',
            self::STATUS_WAITING_RESPONSE => 'Aguarda resposta',
            self::STATUS_ACCEPTED => 'Aceite',
            self::STATUS_REJECTED => 'Não aceite',
            default => ucfirst((string) $this->status),
        };
    }

    public function allowedNextStatuses(): array
    {
        return match ($this->status) {
            self::STATUS_DRAFT => [
                self::STATUS_CREATED,
            ],
            self::STATUS_CREATED => [
                self::STATUS_SENT,
                self::STATUS_WAITING_RESPONSE,
            ],
            self::STATUS_SENT => [
                self::STATUS_WAITING_RESPONSE,
                self::STATUS_ACCEPTED,
                self::STATUS_REJECTED,
            ],
            self::STATUS_WAITING_RESPONSE => [
                self::STATUS_ACCEPTED,
                self::STATUS_REJECTED,
            ],
            default => [],
        };
    }

    public function canChangeToStatus(string $newStatus): bool
    {
        return in_array($newStatus, $this->allowedNextStatuses(), true);
    }
      public function documentSeries()
    {
        return $this->belongsTo(DocumentSeries::class);
    }

    public function captureDocumentSnapshot(): void
    {
        $this->loadMissing([
            'customer',
            'owner.companyProfile',
        ]);

        $companyProfile = $this->owner?->companyProfile;
        $customer = $this->customer;

        $this->forceFill([
            'snapshot_generated_at' => now(),

            'snapshot_company_name' => $companyProfile?->company_name,
            'snapshot_company_address_line_1' => $companyProfile?->address_line_1,
            'snapshot_company_address_line_2' => $companyProfile?->address_line_2,
            'snapshot_company_city' => $companyProfile?->city,
            'snapshot_company_district' => $companyProfile?->district,
            'snapshot_company_postal_code' => $companyProfile?->postal_code,
            'snapshot_company_postal_code_suffix' => $companyProfile?->postal_code_suffix,
            'snapshot_company_postal_designation' => $companyProfile?->postal_designation,
            'snapshot_company_country_code' => $companyProfile?->country_code,
            'snapshot_company_tax_number' => $companyProfile?->tax_number,
            'snapshot_company_phone' => $companyProfile?->phone,
            'snapshot_company_fax' => $companyProfile?->fax,
            'snapshot_company_contact_person' => $companyProfile?->contact_person,
            'snapshot_company_email' => $companyProfile?->email,
            'snapshot_company_website' => $companyProfile?->website,
            'snapshot_company_share_capital' => $companyProfile?->share_capital,
            'snapshot_company_registry_office' => $companyProfile?->registry_office,
            'snapshot_company_logo_path' => $companyProfile?->logo_path,
            'snapshot_company_bank_name' => $companyProfile?->bank_name,
            'snapshot_company_bank_iban' => $companyProfile?->bank_iban,
            'snapshot_company_bank_bic_swift' => $companyProfile?->bank_bic_swift,

            'snapshot_customer_code' => $customer?->code,
            'snapshot_customer_name' => $customer?->name,
            'snapshot_customer_nif' => $customer?->nif,
            'snapshot_customer_email' => $customer?->email,
            'snapshot_customer_phone' => $customer?->phone,
            'snapshot_customer_mobile' => $customer?->mobile,
            'snapshot_customer_contact_person' => $customer?->contact_person,
            'snapshot_customer_address_line_1' => $customer?->address_line_1,
            'snapshot_customer_address_line_2' => $customer?->address_line_2,
            'snapshot_customer_postal_code' => $customer?->postal_code,
            'snapshot_customer_city' => $customer?->city,
            'snapshot_customer_country' => $customer?->country,
        ])->save();
    }
}
