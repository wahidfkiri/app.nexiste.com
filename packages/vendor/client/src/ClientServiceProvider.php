<?php

namespace Vendor\Client;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Vendor\Client\Http\Middleware\ClientMiddleware;

class ClientServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Fusionner la configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/client.php', 'client'
        );

        // Enregistrer les bindings du package
        $this->app->bind('client.repository', function ($app) {
            return new \Vendor\Client\Repositories\ClientRepository();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Charger les migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Charger les routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');

        // Charger les vues
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'client');

        // Charger les traductions
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'client');

        // Publier les fichiers de configuration
        $this->publishes([
            __DIR__ . '/../config/client.php' => config_path('client.php'),
        ], 'client-config');

        // Publier les migrations
        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'client-migrations');

        // Charger les vues
    $this->loadViewsFrom(__DIR__ . '/Resources/views', 'client');
    
    // Charger les traductions
    $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'client');
    
    // Publier les vues
    $this->publishes([
        __DIR__ . '/Resources/views' => resource_path('views/vendor/client'),
    ], 'client-views');
    
    // Publier les traductions
    $this->publishes([
        __DIR__ . '/Resources/lang' => resource_path('lang/vendor/client'),
    ], 'client-lang');

        // Publier les vues
        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/client'),
        ], 'client-views');

        // Publier les assets (CSS, JS)
        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/client'),
        ], 'client-assets');

        // Enregistrer les commandes
        // if ($this->app->runningInConsole()) {
        //     $this->commands([
        //         \Vendor\Client\Console\Commands\InstallClientPackage::class,
        //         \Vendor\Client\Console\Commands\CreateClient::class,
        //     ]);
        // }

        // Enregistrer les middlewares
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('client.auth', ClientMiddleware::class);

        // Enregistrer les événements et listeners
        $this->registerEvents();

        // Enregistrer les policies
        $this->registerPolicies();
    }

    /**
     * Enregistrer les événements du package
     */
    protected function registerEvents(): void
    {
        $this->app['events']->listen(
            \Vendor\Client\Events\ClientCreated::class,
            \Vendor\Client\Listeners\SendClientCreatedNotification::class
        );

        $this->app['events']->listen(
            \Vendor\Client\Events\ClientUpdated::class,
            \Vendor\Client\Listeners\LogClientUpdate::class
        );

        $this->app['events']->listen(
            \Vendor\Client\Events\ClientDeleted::class,
            \Vendor\Client\Listeners\CleanClientRelations::class
        );
    }

    /**
     * Enregistrer les policies
     */
    protected function registerPolicies(): void
    {
        $this->app['auth']->provider('eloquent', function ($app, $config) {
            return new \Illuminate\Auth\EloquentUserProvider($app['hash'], $config['model']);
        });
    }
}