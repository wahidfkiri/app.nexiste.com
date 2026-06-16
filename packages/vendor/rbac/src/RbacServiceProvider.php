<?php

namespace Vendor\Rbac;

use Illuminate\Support\ServiceProvider;
use Vendor\Rbac\Services\RbacService;
use Vendor\Rbac\Repositories\RbacRepository;
use Vendor\Rbac\Services\TenantRoleService;

class RbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/rbac.php', 'rbac');

        $this->app->bind(TenantRoleService::class, fn() => new TenantRoleService());

        $this->app->bind(RbacRepository::class, function ($app) {
            return new RbacRepository($app->make(TenantRoleService::class));
        });

        $this->app->bind(RbacService::class, function ($app) {
            return new RbacService($app->make(RbacRepository::class));
        });

        $this->app->bind('rbac.service', fn($app) => $app->make(RbacService::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'rbac');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'rbac');

        $this->publishes([
            __DIR__ . '/../config/rbac.php' => config_path('rbac.php'),
        ], 'rbac-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'rbac-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/rbac'),
        ], 'rbac-views');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Vendor\Rbac\Console\Commands\SeedRbacCommand::class,
            ]);
        }
    }
}
