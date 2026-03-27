<?php

namespace App\Providers;

use App\Models\Budget;
use App\Models\DocumentSeries;
use App\Models\Item;
use App\Models\PaymentTerm;
use App\Models\Brand;
use App\Models\ItemFamily;
use App\Models\Unit;
use App\Models\TaxRate;
use App\Models\TaxExemptionReason;

use App\Policies\BrandPolicy;
use App\Policies\ItemFamilyPolicy;
use App\Policies\UnitPolicy;
use App\Policies\TaxRatePolicy;
use App\Policies\TaxExemptionReasonPolicy;
use App\Policies\BudgetPolicy;
use App\Policies\DocumentSeriesPolicy;
use App\Policies\ItemPolicy;
use App\Policies\PaymentTermPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Budget::class => BudgetPolicy::class,
        Item::class => ItemPolicy::class,
        PaymentTerm::class => PaymentTermPolicy::class,
        DocumentSeries::class => DocumentSeriesPolicy::class,
        Brand::class => BrandPolicy::class,
        ItemFamily::class => ItemFamilyPolicy::class,
        Unit::class => UnitPolicy::class,
        TaxRate::class => TaxRatePolicy::class,
        TaxExemptionReason::class => TaxExemptionReasonPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
