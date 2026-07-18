<?php

namespace App\Providers;

use App\Models\Material;
use App\Models\Tenant;
use App\Models\TaskTemplate;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Cashier::useCustomerModel(Tenant::class);

        // morphMap() (not enforceMorphMap()) - the latter forces every other
        // polymorphic relation in the app (e.g. User::notifications(), which
        // Laravel's own HasDatabaseNotifications trait sets up as a morph
        // relation on the notifiable model) to also be registered here, or it
        // throws. This only aliases Recipe's two subject types.
        Relation::morphMap([
            'task_template' => TaskTemplate::class,
            'material' => Material::class,
        ]);
    }
}
