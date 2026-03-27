<?php

namespace App\Providers;

use App\Models\Budget;
use App\Models\DocumentSeries;
use App\Models\Item;
use App\Models\PaymentTerm;
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
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
