<?php

namespace App\Providers;

use App\Models\Integration;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\Lead;
use App\Models\Project;
use App\Observers\ClientObserver;
use App\Observers\TaskObserver;
use App\Observers\LeadObserver;
use App\Observers\ProjectObserver;
use App\Observers\InvoiceObserver;
use App\Repositories\Format\GetDateFormat;
use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\DuskServiceProvider;
use Laravel\Cashier\Cashier;
use App\Services\DeleteData\DeleteDataService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Cashier::ignoreMigrations();
        Client::observe(ClientObserver::class);
        Task::observe(TaskObserver::class);
        Lead::observe(LeadObserver::class);
        Project::observe(ProjectObserver::class);
        Invoice::observe(InvoiceObserver::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local', 'testing')) {
            $this->app->register(DuskServiceProvider::class);
        }
        $this->app->singleton(GetDateFormat::class);
        $this->app->singleton(DeleteDataService::class, function ($app) {
            logger('Binding DeleteDataService'); // Debug statement
            return new DeleteDataService();
        });
        $this->app->bind(\App\Services\DataImport\DataImportService::class, function ($app) {
            return new \App\Services\DataImport\DataImportService();
        });
    }
}
