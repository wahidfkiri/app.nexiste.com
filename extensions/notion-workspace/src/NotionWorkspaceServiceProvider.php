<?php

namespace NexusExtensions\NotionWorkspace;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use NexusExtensions\NotionWorkspace\Services\NotionPermissionService;

class NotionWorkspaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/notion-workspace.php', 'notion-workspace');
        $this->app->bind(NotionPermissionService::class, fn () => new NotionPermissionService());
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'notion-workspace');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'notion-workspace');

        $this->publishes([
            __DIR__ . '/../config/notion-workspace.php' => config_path('notion-workspace.php'),
        ], 'notion-workspace-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'notion-workspace-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/notion-workspace'),
        ], 'notion-workspace-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/notion-workspace'),
        ], 'notion-workspace-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang' => lang_path('vendor/notion-workspace'),
        ], 'notion-workspace-lang');

        // Idempotent mais coûteux : on évite de rejouer le seeding des
        // permissions à chaque requête HTTP (voir ProjectsServiceProvider).
        if ($this->app->runningInConsole() || ! Cache::has('notion:permissions:synced')) {
            app(NotionPermissionService::class)->ensurePermissions();
            Cache::put('notion:permissions:synced', true, now()->addHours(12));
        }
    }
}
