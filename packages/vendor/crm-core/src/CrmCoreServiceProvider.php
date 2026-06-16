<?php

namespace Vendor\CrmCore;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Vendor\CrmCore\Http\Middleware\TenantMiddleware;
use Vendor\CrmCore\Http\Middleware\TenantOwnerMiddleware;

class CrmCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/crm-core.php', 'crm-core');
    }

    public function boot(Router $router): void
    {
        // Charger les migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        
        // Enregistrer les middlewares - Méthode 1
        $router->aliasMiddleware('tenant', TenantMiddleware::class);
        $router->aliasMiddleware('tenant.owner', TenantOwnerMiddleware::class);
        
        // OU Méthode 2 (alternative)
        // $this->app['router']->aliasMiddleware('tenant', TenantMiddleware::class);
        // $this->app['router']->aliasMiddleware('tenant.owner', TenantOwnerMiddleware::class);
        
        // Publier la configuration
        $this->publishes([
            __DIR__ . '/../config/crm-core.php' => config_path('crm-core.php'),
        ], 'crm-core-config');
        
        // Publier les migrations
        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'crm-core-migrations');
    }
}