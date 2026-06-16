<?php

namespace Modules\TrelloIntegration;

use Illuminate\Support\ServiceProvider;
use Modules\TrelloIntegration\Services\TrelloApiService;
use Modules\TrelloIntegration\Services\TrelloAuthService;
use Modules\TrelloIntegration\Services\TrelloSyncService;

class TrelloIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/trello-integration.php', 'trello-integration');

        $this->app->singleton(TrelloApiService::class, fn () => new TrelloApiService());
        $this->app->singleton(TrelloAuthService::class, fn ($app) => new TrelloAuthService($app->make(TrelloApiService::class)));
        $this->app->singleton(TrelloSyncService::class, fn ($app) => new TrelloSyncService(
            $app->make(TrelloApiService::class),
            $app->make(TrelloAuthService::class),
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'trello-integration');

        $this->publishes([
            __DIR__ . '/../config/trello-integration.php' => config_path('trello-integration.php'),
        ], 'trello-integration-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'trello-integration-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/trello-integration'),
        ], 'trello-integration-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/trello-integration'),
        ], 'trello-integration-assets');
    }
}
