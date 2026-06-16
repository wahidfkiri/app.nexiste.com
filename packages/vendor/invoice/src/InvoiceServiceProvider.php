<?php

namespace Vendor\Invoice;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/invoice.php', 'invoice');

        $this->app->bind('invoice.service', function ($app) {
            return new \Vendor\Invoice\Services\InvoiceService(
                new \Vendor\Invoice\Repositories\InvoiceRepository()
            );
        });
    }

    public function boot(): void
    {
        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');

        // Vues
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'invoice');

        // Traductions
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'invoice');

        // Publications
        $this->publishes([
            __DIR__ . '/../config/invoice.php'  => config_path('invoice.php'),
        ], 'invoice-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations'    => database_path('migrations'),
        ], 'invoice-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views'        => resource_path('views/vendor/invoice'),
        ], 'invoice-views');

        $this->publishes([
            __DIR__ . '/Resources/assets'       => public_path('vendor/invoice'),
        ], 'invoice-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang'         => resource_path('lang/vendor/invoice'),
        ], 'invoice-lang');

        // Observateurs
        \Vendor\Invoice\Models\Invoice::observe(\Vendor\Invoice\Observers\InvoiceObserver::class);
        \Vendor\Invoice\Models\Quote::observe(\Vendor\Invoice\Observers\QuoteObserver::class);
    }
}
