<?php

namespace NexusExtensions\GoogleDrive;

use Illuminate\Support\ServiceProvider;
use NexusExtensions\GoogleDrive\Services\GoogleDriveService;

class GoogleDriveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/google-drive.php', 'google-drive');

        $this->app->bind(GoogleDriveService::class, fn () => new GoogleDriveService());
        $this->app->bind('google-drive.service', fn ($app) => $app->make(GoogleDriveService::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'google-drive');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'google-drive');

        $this->publishes([
            __DIR__ . '/../config/google-drive.php' => config_path('google-drive.php'),
        ], 'google-drive-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'google-drive-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/google-drive'),
        ], 'google-drive-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/google-drive'),
        ], 'google-drive-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang' => lang_path('vendor/google-drive'),
        ], 'google-drive-lang');
    }
}
