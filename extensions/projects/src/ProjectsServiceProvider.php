<?php

namespace NexusExtensions\Projects;

use Illuminate\Support\ServiceProvider;
use NexusExtensions\Projects\Services\ProjectPermissionService;

class ProjectsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/projects.php', 'projects');

        $this->app->bind(ProjectPermissionService::class, fn () => new ProjectPermissionService());
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'projects');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'projects');

        $this->publishes([
            __DIR__ . '/../config/projects.php' => config_path('projects.php'),
        ], 'projects-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'projects-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/projects'),
        ], 'projects-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/projects'),
        ], 'projects-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang' => lang_path('vendor/projects'),
        ], 'projects-lang');

        app(ProjectPermissionService::class)->ensurePermissions();
    }
}
