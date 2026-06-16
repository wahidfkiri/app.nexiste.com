<?php

namespace Vendor\Extensions;

use Illuminate\Support\ServiceProvider;
use Vendor\Extensions\Services\ExtensionService;
use Vendor\Extensions\Repositories\ExtensionRepository;

class ExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/extensions.php', 'extensions');

        $this->app->bind(ExtensionRepository::class, fn() => new ExtensionRepository());

        $this->app->bind(ExtensionService::class, function ($app) {
            return new ExtensionService($app->make(ExtensionRepository::class));
        });

        $this->app->bind('extensions.service', fn($app) => $app->make(ExtensionService::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'extensions');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'extensions');

        $this->publishes([
            __DIR__ . '/../config/extensions.php' => config_path('extensions.php'),
        ], 'extensions-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'extensions-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/extensions'),
        ], 'extensions-views');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Vendor\Extensions\Console\Commands\SeedExtensionsCommand::class,
            ]);
        }
    }
}