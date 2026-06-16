<?php

namespace NexusExtensions\GoogleSheets;

use Illuminate\Support\ServiceProvider;
use NexusExtensions\GoogleSheets\Services\GoogleSheetsService;

class GoogleSheetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/google-sheets.php', 'google-sheets');

        $this->app->bind(GoogleSheetsService::class, fn () => new GoogleSheetsService());
        $this->app->bind('google-sheets.service', fn ($app) => $app->make(GoogleSheetsService::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'google-sheets');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'google-sheets');

        $this->publishes([
            __DIR__ . '/../config/google-sheets.php' => config_path('google-sheets.php'),
        ], 'google-sheets-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'google-sheets-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/google-sheets'),
        ], 'google-sheets-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/google-sheets'),
        ], 'google-sheets-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang' => lang_path('vendor/google-sheets'),
        ], 'google-sheets-lang');
    }
}
