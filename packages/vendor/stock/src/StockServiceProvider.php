<?php

namespace Vendor\Stock;

use Illuminate\Support\ServiceProvider;

class StockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/stock.php', 'stock');

        $this->app->bind('stock.service', function () {
            return new \Vendor\Stock\Services\StockService();
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'stock');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'stock');

        $this->publishes([
            __DIR__ . '/../config/stock.php' => config_path('stock.php'),
        ], 'stock-config');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/stock'),
        ], 'stock-assets');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/stock'),
        ], 'stock-views');

        $this->publishes([
            __DIR__ . '/Resources/lang' => resource_path('lang/vendor/stock'),
        ], 'stock-lang');
    }
}
