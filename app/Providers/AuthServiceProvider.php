<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Budget;
use App\Models\DocumentSeries;
use App\Models\Item;
use App\Models\ItemFamily;
use App\Models\PaymentTerm;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\TaxExemptionReason;
use App\Models\TaxRate;
use App\Models\Unit;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkDailyReport;
use App\Policies\BrandPolicy;
use App\Policies\BudgetPolicy;
use App\Policies\DocumentSeriesPolicy;
use App\Policies\ItemFamilyPolicy;
use App\Policies\ItemPolicy;
use App\Policies\PaymentTermPolicy;
use App\Policies\PurchaseRequestPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TaxExemptionReasonPolicy;
use App\Policies\TaxRatePolicy;
use App\Policies\UnitPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkPolicy;
use App\Policies\WorkDailyReportPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Brand::class => BrandPolicy::class,
        Budget::class => BudgetPolicy::class,
        DocumentSeries::class => DocumentSeriesPolicy::class,
        Item::class => ItemPolicy::class,
        ItemFamily::class => ItemFamilyPolicy::class,
        PaymentTerm::class => PaymentTermPolicy::class,
        PurchaseRequest::class => PurchaseRequestPolicy::class,
        Supplier::class => SupplierPolicy::class,
        TaxExemptionReason::class => TaxExemptionReasonPolicy::class,
        TaxRate::class => TaxRatePolicy::class,
        Unit::class => UnitPolicy::class,
        User::class => UserPolicy::class,
        Work::class => WorkPolicy::class,
        WorkDailyReport::class => WorkDailyReportPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
