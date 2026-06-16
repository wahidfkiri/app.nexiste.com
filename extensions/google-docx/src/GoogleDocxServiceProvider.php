<?php

namespace NexusExtensions\GoogleDocx;

use Illuminate\Support\ServiceProvider;
use NexusExtensions\GoogleDocx\Services\GoogleDocxService;

class GoogleDocxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/google-docx.php', 'google-docx');

        $this->app->bind(GoogleDocxService::class, fn () => new GoogleDocxService());
        $this->app->bind('google-docx.service', fn ($app) => $app->make(GoogleDocxService::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'google-docx');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'google-docx');

        $this->publishes([
            __DIR__ . '/../config/google-docx.php' => config_path('google-docx.php'),
        ], 'google-docx-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'google-docx-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/google-docx'),
        ], 'google-docx-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/google-docx'),
        ], 'google-docx-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang' => lang_path('vendor/google-docx'),
        ], 'google-docx-lang');
    }
}
